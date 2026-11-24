<?php

declare(strict_types=1);

use App\Domain\Business\ReadinessAcademy;
use App\Models\BusinessAcademyAttempt;
use App\Models\CommandOperation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BusinessAuthorityFixture as AuthorityFixture;

/*
 * The Business Academy (BRS v3.0 RDY-R07): the core modules of the business's stage, each with
 * flashcards and a 10-question test scored on the server. 8 of 10 passes; a failed test reopens
 * 24 hours later with a new draw. Passing the stage's core counts toward Ready (RDY-R05).
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-10 10:00', 'Africa/Kigali'));
    $authority = AuthorityFixture::make('organization', 1, 'COMPANY-001', 1);
    $this->user = $authority['users'][0];
    $this->business = AuthorityFixture::configure($authority)['data']['business']['id'];
    /** @var array<string, array{stage: int, en: array{title: string, summary: string, cards: list<array{0: string, 1: string}>, questions: list<array{0: string, 1: list<string>, 2: int}>}}> $modules */
    $modules = require resource_path('academy/business.php');
    $this->modules = $modules;
    $this->props = function (string $route, array $parameters = [], ?User $user = null): array {
        return $this->actingAs($user ?? $this->user)->get(route($route, ['business' => $this->business, ...$parameters]))->assertOk()
            ->viewData('page')['props'];
    };
    /** Answers the drawn test, the first `$right` questions correctly. */
    $this->answer = function (string $module, array $test, int $right): array {
        $answers = [];
        foreach ($test as $place => $question) {
            $key = $this->modules[$module]['en']['questions'][$question['id']][2];
            $answers[$question['id']] = $place < $right ? $key : ($key + 1) % 3;
        }

        return $answers;
    };
    $this->send = function (string $module, int $attempt, array $answers, ?string $requestId = null): TestResponse {
        $revision = ($this->props)('business.academy.module', ['module' => $module])['identity_context_revision'];

        return $this->actingAs($this->user)->post(route('business.academy.submit', ['business' => $this->business, 'module' => $module]), [
            'request_id' => $requestId ?? (string) Str::uuid(), 'identity_context_revision' => $revision, 'attempt' => $attempt, 'answers' => $answers]);
    };
});

it('lists the Foundation core with its tests open and the next stage as a preview', function (): void {
    $business = $this->business;

    $this->actingAs($this->user)->get(route('business.academy', ['business' => $business]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('business/academy')
            ->where('stage', null)->where('level', 1)->where('pass_mark', 8)->where('questions', 10)
            ->where('core', [
                ['key' => 'bookkeeping', 'title' => 'Keeping your books', 'summary' => 'Record every sale and every payment on the day it happens.', 'cards' => 9,
                    'status' => ['state' => 'open', 'attempts' => 0, 'best' => null, 'retake_at' => null],
                    'link' => ['url' => "/business/{$business}/academy/bookkeeping", 'method' => 'get']],
                ['key' => 'business_money', 'title' => 'Business money, tax and RSSB',
                    'summary' => 'Run money through business accounts and keep tax and RSSB current.', 'cards' => 8,
                    'status' => ['state' => 'open', 'attempts' => 0, 'best' => null, 'retake_at' => null],
                    'link' => ['url' => "/business/{$business}/academy/business_money", 'method' => 'get']],
            ])
            ->where('preview.0.key', 'management_accounts')->where('preview.0.status', null)->where('preview.1.key', 'forecasting')
            ->where('links.close', ['url' => "/business/{$business}/readiness", 'method' => 'get']));
});

it('shows a module\'s cards and the 10 questions drawn for its next attempt, with no answer key', function (): void {
    $props = ($this->props)('business.academy.module', ['module' => 'bookkeeping']);

    expect($props)->toMatchArray(['key' => 'bookkeeping', 'role' => 'core', 'level' => 1, 'attempt' => 1, 'last' => null, 'pass_mark' => 8])
        ->and($props['cards'])->toHaveCount(9)
        ->and($props['cards'][0])->toBe(['front' => 'Books', 'back' => 'Your books are the record of every franc that comes in and goes out of the business, written in one place.'])
        ->and($props['test'])->toHaveCount(10)
        ->and($props['test'])->toBe(ReadinessAcademy::draw($this->modules['bookkeeping']['en'], ReadinessAcademy::seed($this->business, 'bookkeeping', 1)))
        ->and(json_encode($props['test']))->not->toContain('"answer"')
        ->and($props['links']['submit'])->toBe(['url' => "/business/{$this->business}/academy/bookkeeping", 'method' => 'post']);
});

it('scores a passed test on the server, records it once and counts it toward the syllabus', function (): void {
    $test = ($this->props)('business.academy.module', ['module' => 'bookkeeping'])['test'];
    $answers = ($this->answer)('bookkeeping', $test, 8);
    $requestId = (string) Str::uuid();

    ($this->send)('bookkeeping', 1, $answers, $requestId)->assertRedirect(route('business.academy.module', ['business' => $this->business, 'module' => 'bookkeeping']));
    ($this->send)('bookkeeping', 1, $answers, $requestId)->assertRedirect();

    $attempt = BusinessAcademyAttempt::query()->sole();
    expect($attempt->only(['module', 'attempt', 'score', 'passed', 'policy_version']))
        ->toBe(['module' => 'bookkeeping', 'attempt' => 1, 'score' => 8, 'passed' => true, 'policy_version' => 'brs-3.0-rdy-r07'])
        ->and($attempt->questions)->toBe(array_column($test, 'id'));
    $operation = CommandOperation::query()->where('command', 'business.academy.test.submit')->sole();
    expect(json_encode($operation->getAttributes()))->not->toContain('"answers":{');

    $props = ($this->props)('business.academy.module', ['module' => 'bookkeeping']);
    expect($props)->toMatchArray(['attempt' => null, 'test' => null, 'status' => ['state' => 'passed', 'attempts' => 1, 'best' => 8, 'retake_at' => null]])
        ->and($props['last'])->toMatchArray(['module' => 'bookkeeping', 'attempt' => 1, 'score' => 8, 'passed' => true]);
    expect(($this->props)('business.readiness')['syllabus'])->toBe(['passed' => 1, 'total' => 2])
        ->and(($this->props)('business.readiness')['links']['academy'])->toBe(['url' => "/business/{$this->business}/academy", 'method' => 'get']);

    ($this->send)('bookkeeping', 2, $answers)->assertSessionHasErrors(['form' => 'You have already passed this module.']);
});

it('holds a failed test for 24 hours, then draws it again for the next attempt', function (): void {
    $test = ($this->props)('business.academy.module', ['module' => 'business_money'])['test'];
    ($this->send)('business_money', 1, ($this->answer)('business_money', $test, 7))->assertRedirect();

    $props = ($this->props)('business.academy.module', ['module' => 'business_money']);
    expect($props)->toMatchArray(['attempt' => null, 'test' => null,
        'status' => ['state' => 'waiting', 'attempts' => 1, 'best' => 7, 'retake_at' => '2026-11-11T08:00:00Z']])
        ->and($props['last'])->toMatchArray(['score' => 7, 'passed' => false]);
    ($this->send)('business_money', 2, ($this->answer)('business_money', $test, 10))
        ->assertSessionHasErrors(['form' => 'You can retake this test 24 hours after your last attempt.']);

    $this->travel(24)->hours();
    $retake = ($this->props)('business.academy.module', ['module' => 'business_money']);
    expect($retake['attempt'])->toBe(2)->and($retake['test'])->not->toBe($test);
    ($this->send)('business_money', 1, ($this->answer)('business_money', $retake['test'], 10))
        ->assertSessionHasErrors(['form' => 'This test was already answered. Reload the page for your next attempt.']);
    ($this->send)('business_money', 2, ($this->answer)('business_money', $retake['test'], 10))->assertRedirect();
    expect(($this->props)('business.academy.module', ['module' => 'business_money'])['status'])
        ->toBe(['state' => 'passed', 'attempts' => 2, 'best' => 10, 'retake_at' => null]);
});

it('marks each unanswered question and records nothing', function (): void {
    $test = ($this->props)('business.academy.module', ['module' => 'bookkeeping'])['test'];
    $answers = ($this->answer)('bookkeeping', $test, 10);
    $missing = $test[0]['id'];
    unset($answers[$missing]);

    ($this->send)('bookkeeping', 1, $answers)->assertSessionHasErrors(["answers.{$missing}" => 'Choose one answer.']);
    expect(BusinessAcademyAttempt::query()->count())->toBe(0);
});

it('has no test for a preview module and finds no module outside the syllabus', function (): void {
    $preview = ($this->props)('business.academy.module', ['module' => 'forecasting']);

    expect($preview)->toMatchArray(['role' => 'preview', 'status' => null, 'attempt' => null, 'test' => null, 'last' => null])
        ->and($preview['cards'])->toHaveCount(8);
    $this->actingAs($this->user)->get(route('business.academy.module', ['business' => $this->business, 'module' => 'capital_markets']))->assertNotFound();
    $this->actingAs($this->user)->post(route('business.academy.submit', ['business' => $this->business, 'module' => 'forecasting']), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => $preview['identity_context_revision'], 'attempt' => 1, 'answers' => []])
        ->assertNotFound();
    expect(BusinessAcademyAttempt::query()->count())->toBe(0);
});

it('lets nobody without the Business authority read the Academy or take a test', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('business.academy', ['business' => $this->business]))->assertForbidden();
    $this->actingAs($stranger)->post(route('business.academy.submit', ['business' => $this->business, 'module' => 'bookkeeping']), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 0, 'attempt' => 1, 'answers' => []])->assertNotFound();
    expect(BusinessAcademyAttempt::query()->count())->toBe(0);
});
