<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\SelectActiveRole;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Operations\ReadChanges;
use App\Domain\Operations\ChangeCursor;
use App\Domain\Operations\ChangeScope;
use App\Models\BusinessProfile;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;

use function Pest\Laravel\actingAs;

/*
 * The beacon read (S4-E): each read authorizes afresh and returns only the caller's own audiences,
 * so no subject id or revision of another audience can leak. Reads inside one test share its
 * transaction, which a reader always sees; the committed-horizon path is proven under
 * tests/Concurrency.
 */

function change(ChangeScope $scope, string $topic, string $subject, ?int $revision = null): void
{
    DB::transaction(fn () => app(ChangeFeed::class)->record($scope, $topic, $subject, $revision));
}

/** @param  list<string>  $roles */
function staffUser(array $roles): User
{
    $staff = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Review the applications queue.', (string) Str::uuid(), $roles);

    return $staff->refresh();
}

/** @return array{user: User, business: string} */
function businessMember(string $code = 'COMPANY-001'): array
{
    $fixture = BusinessAuthorityFixture::make('organization', companyCode: $code);
    BusinessAuthorityFixture::configure($fixture);

    return ['user' => $fixture['users'][0]->refresh(), 'business' => BusinessProfile::query()->where('entity_party_id', $fixture['entity'])->sole()->id];
}

/** @return array<string, mixed> */
function readChanges(User $user, string $topics, ?string $after, string $route = 'changes.index'): array
{
    return actingAs($user)->getJson(route($route, array_filter(['topics' => $topics, 'after' => $after])))->assertOk()->json();
}

it('reads only the caller\'s own wallet, business and queue changes', function (): void {
    $investor = InvestorWalletFixture::investor();
    $other = InvestorWalletFixture::investor();
    $member = businessMember();
    $stranger = businessMember('COMPANY-002');
    $staff = staffUser(['approver']);
    $cursors = array_map(fn (User $user): string => app(ReadChanges::class)->cursor($user->id), [$investor['user'], $member['user'], $staff]);

    change(ChangeScope::party($investor['party']->id), 'wallet', 'mywallet');
    change(ChangeScope::party($other['party']->id), 'wallet', 'otherwallet');
    change(ChangeScope::business($member['business']), 'campaign', 'mycampaign', 2);
    change(ChangeScope::business($stranger['business']), 'campaign', 'othercampaign', 2);
    change(ChangeScope::staffQueue('applications'), 'staff_queue', 'applications');

    $wallet = readChanges($investor['user'], 'wallet,campaign', $cursors[0]);
    $campaign = readChanges($member['user'], 'wallet,campaign', $cursors[1]);
    $queue = readChanges($staff, 'staff_queue', $cursors[2], 'staff.changes.index');

    expect($wallet)->toMatchArray(['contract_version' => 'change-feed-v1', 'reset' => false, 'poll_after_ms' => 10_000,
        'changes' => [['topic' => 'wallet', 'subject' => 'mywallet', 'revision' => 1]]])
        ->and($campaign['changes'])->toBe([['topic' => 'campaign', 'subject' => 'mycampaign', 'revision' => 2]])
        ->and($queue['changes'])->toBe([['topic' => 'staff_queue', 'subject' => 'applications', 'revision' => 1]])
        ->and(json_encode([$wallet, $campaign, $queue]))->not->toContain('otherwallet')->not->toContain('othercampaign')
        ->not->toContain($other['party']->id)->not->toContain($stranger['business']);
});

it('reads only the caller\'s own purchases, deriving its Party whether or not it asks for its wallet', function (): void {
    $investor = InvestorWalletFixture::investor();
    $other = InvestorWalletFixture::investor();
    $member = businessMember();
    $cursors = array_map(fn (User $user): string => app(ReadChanges::class)->cursor($user->id), [$investor['user'], $investor['user'], $member['user']]);

    change(ChangeScope::party($investor['party']->id), 'purchase', 'myreservation');
    change(ChangeScope::party($investor['party']->id), 'wallet', 'mywallet');
    change(ChangeScope::party($other['party']->id), 'purchase', 'otherreservation');

    $purchase = readChanges($investor['user'], 'purchase', $cursors[0]);
    $both = readChanges($investor['user'], 'purchase,wallet', $cursors[1]);

    expect($purchase['changes'])->toBe([['topic' => 'purchase', 'subject' => 'myreservation', 'revision' => 1]])
        ->and($both['changes'])->toBe([['topic' => 'purchase', 'subject' => 'myreservation', 'revision' => 1], ['topic' => 'wallet', 'subject' => 'mywallet', 'revision' => 1]])
        ->and(readChanges($member['user'], 'purchase', $cursors[2]))->toMatchArray(['changes' => [], 'reset' => false])
        ->and(json_encode([$purchase, $both]))->not->toContain('otherreservation')->not->toContain($other['party']->id);
});

it('leaves out every topic the caller\'s current authority cannot read', function (): void {
    $investor = InvestorWalletFixture::investor();
    $member = businessMember();
    $analyst = staffUser(['analyst']);
    foreach ([[$investor['user'], 'campaign', 'changes.index'], [$member['user'], 'wallet', 'changes.index'],
        [$member['user'], 'purchase', 'changes.index'], [$analyst, 'staff_queue', 'staff.changes.index'],
        [$investor['user'], 'staff_queue', 'staff.changes.index']] as [$user, $topics, $route]) {
        $cursor = app(ReadChanges::class)->cursor($user->id);
        change(ChangeScope::party($investor['party']->id), 'wallet', 'w'.Str::lower(Str::random(6)));
        change(ChangeScope::party($investor['party']->id), 'purchase', 'r'.Str::lower(Str::random(6)));
        change(ChangeScope::business($member['business']), 'campaign', 'c'.Str::lower(Str::random(6)), 2);
        change(ChangeScope::staffQueue('applications'), 'staff_queue', 'applications');

        expect(readChanges($user, $topics, $cursor, $route))->toMatchArray(['changes' => [], 'reset' => false]);
    }
});

it('stops reading a Business the caller can no longer view', function (): void {
    $fixture = BusinessAuthorityFixture::make('organization', 2, requiredSignatories: 1);
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->where('entity_party_id', $fixture['entity'])->sole()->id;
    $leaver = $fixture['users'][1];
    $cursor = app(ReadChanges::class)->cursor($leaver->id);
    change(ChangeScope::business($business), 'campaign', 'mycampaign', 2);
    $before = readChanges($leaver, 'campaign', $cursor);
    $fixture['terms']['people'] = array_slice($fixture['terms']['people'], 0, 1);
    BusinessAuthorityFixture::configure($fixture, 1);
    change(ChangeScope::business($business), 'campaign', 'othercampaign', 2);

    expect($before['changes'])->toBe([['topic' => 'campaign', 'subject' => 'mycampaign', 'revision' => 2]])
        ->and(readChanges($leaver, 'campaign', $before['next_cursor'])['changes'])->toBe([]);
});

it('delivers each change once and only the latest revision of a subject', function (): void {
    $investor = InvestorWalletFixture::investor();
    $party = ChangeScope::party($investor['party']->id);
    $cursor = app(ReadChanges::class)->cursor($investor['user']->id);
    change($party, 'wallet', 'w1', 2);
    change($party, 'wallet', 'w1', 3);
    change($party, 'wallet', 'w2', 1);

    $first = readChanges($investor['user'], 'wallet', $cursor);
    $again = readChanges($investor['user'], 'wallet', $first['next_cursor']);

    expect($first['changes'])->toBe([['topic' => 'wallet', 'subject' => 'w1', 'revision' => 3], ['topic' => 'wallet', 'subject' => 'w2', 'revision' => 1]])
        ->and($again)->toMatchArray(['changes' => [], 'reset' => false]);
});

it('ignores a revision no newer than one already delivered or rendered', function (): void {
    $investor = InvestorWalletFixture::investor();
    $party = ChangeScope::party($investor['party']->id);
    change($party, 'wallet', 'rendered', 5);
    $cursor = app(ReadChanges::class)->cursor($investor['user']->id);
    change($party, 'wallet', 'rendered', 4);
    change($party, 'wallet', 'w1', 3);
    $first = readChanges($investor['user'], 'wallet', $cursor);
    change($party, 'wallet', 'w1', 2);
    change($party, 'wallet', 'w1', 3);
    $stale = readChanges($investor['user'], 'wallet', $first['next_cursor']);
    change($party, 'wallet', 'w1', 4);

    expect($first['changes'])->toBe([['topic' => 'wallet', 'subject' => 'w1', 'revision' => 3]])
        ->and($stale['changes'])->toBe([])
        ->and(readChanges($investor['user'], 'wallet', $stale['next_cursor'])['changes'])->toBe([['topic' => 'wallet', 'subject' => 'w1', 'revision' => 4]]);
});

it('starts a reader without a cursor at the current horizon', function (): void {
    $investor = InvestorWalletFixture::investor();
    change(ChangeScope::party($investor['party']->id), 'wallet', 'before');
    $bootstrap = readChanges($investor['user'], 'wallet', null);
    change(ChangeScope::party($investor['party']->id), 'wallet', 'after');

    expect($bootstrap)->toMatchArray(['changes' => [], 'reset' => false])
        ->and(ChangeCursor::parse($bootstrap['next_cursor']))->not->toBeNull()
        ->and(readChanges($investor['user'], 'wallet', $bootstrap['next_cursor'])['changes'])->toBe([['topic' => 'wallet', 'subject' => 'after', 'revision' => 1]]);
});

it('resets a reader whose cursor is unknown or has expired', function (Closure $cursor): void {
    $this->freezeSecond();
    $investor = InvestorWalletFixture::investor();
    $after = $cursor(app(ReadChanges::class)->cursor($investor['user']->id));
    $this->travel(24)->hours();
    $this->travel(1)->second();
    change(ChangeScope::party($investor['party']->id), 'wallet', 'w1');
    $read = readChanges($investor['user'], 'wallet', $after);

    expect($read)->toMatchArray(['changes' => [], 'reset' => true])
        ->and(readChanges($investor['user'], 'wallet', $read['next_cursor']))->toMatchArray(['changes' => [], 'reset' => false]);
})->with([
    'expired' => [fn (string $cursor): string => $cursor],
    'malformed' => [fn (): string => 'not-a-cursor'],
    'another version' => [fn (string $cursor): string => '2'.substr($cursor, 1)],
]);

it('keeps a cursor within its lifetime', function (): void {
    $this->freezeSecond();
    $investor = InvestorWalletFixture::investor();
    $cursor = app(ReadChanges::class)->cursor($investor['user']->id);
    $this->travel(24)->hours();
    change(ChangeScope::party($investor['party']->id), 'wallet', 'w1');

    expect(readChanges($investor['user'], 'wallet', $cursor))->toMatchArray(['reset' => false, 'changes' => [['topic' => 'wallet', 'subject' => 'w1', 'revision' => 1]]]);
});

it('resets a reader whose identity context changed, and reads no Party topic once Investor is no longer active', function (string $topic): void {
    $investor = InvestorWalletFixture::investor();
    RoleMembership::factory()->for($investor['party'])->active()->create(['role' => 'business']);
    $cursor = app(ReadChanges::class)->cursor($investor['user']->id);
    app(SelectActiveRole::class)->handle($investor['user']->id, 'business', 1, (string) Str::uuid());
    change(ChangeScope::party($investor['party']->id), $topic, 'w1');
    $read = readChanges($investor['user']->refresh(), $topic, $cursor);
    change(ChangeScope::party($investor['party']->id), $topic, 'w2');

    expect($read)->toMatchArray(['changes' => [], 'reset' => true])
        ->and(ChangeCursor::parse($read['next_cursor'])?->context)->toBe(2)
        ->and(readChanges($investor['user'], $topic, $read['next_cursor']))->toMatchArray(['changes' => [], 'reset' => false]);
})->with(['wallet', 'purchase']);

it('resets a reader too far behind to catch up in one read', function (): void {
    $investor = InvestorWalletFixture::investor();
    $cursor = app(ReadChanges::class)->cursor($investor['user']->id);
    DB::insert("INSERT INTO change_feed (party_id, topic, subject, revision) SELECT ?, 'wallet', 'w' || g, 1 FROM generate_series(1, ?) g",
        [$investor['party']->id, ReadChanges::LIMIT + 1]);

    expect(readChanges($investor['user'], 'wallet', $cursor))->toMatchArray(['changes' => [], 'reset' => true]);
});

it('serves the same contract to an API token with the audience read ability', function (): void {
    $investor = InvestorWalletFixture::investor();
    $cursor = app(ReadChanges::class)->cursor($investor['user']->id);
    change(ChangeScope::party($investor['party']->id), 'wallet', 'w1');
    Sanctum::actingAs($investor['user'], ['investor:read']);

    $this->getJson(route('api.v1.changes.index', ['topics' => 'wallet', 'after' => $cursor]))->assertOk()
        ->assertJsonPath('changes', [['topic' => 'wallet', 'subject' => 'w1', 'revision' => 1]])->assertJsonPath('reset', false);
    $this->getJson(route('api.v1.changes.index', ['topics' => 'purchase', 'after' => $cursor]))->assertOk()->assertJsonPath('changes', []);
    $this->getJson(route('api.v1.changes.index', ['topics' => 'wallet,campaign']))->assertForbidden();

    Sanctum::actingAs($investor['user'], ['business:read']);
    $this->getJson(route('api.v1.changes.index', ['topics' => 'wallet']))->assertForbidden();
    $this->getJson(route('api.v1.changes.index', ['topics' => 'purchase']))->assertForbidden();
});

it('validates topics per route, requires a session and is never cached', function (): void {
    $investor = InvestorWalletFixture::investor();

    $this->getJson(route('changes.index', ['topics' => 'wallet']))->assertUnauthorized();
    $this->actingAs($investor['user']);
    $response = $this->getJson(route('changes.index', ['topics' => 'wallet']))->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private');
    foreach ([[], ['topics' => ''], ['topics' => 'staff_queue'], ['topics' => 'wallet,'], ['topics' => 'wallet', 'after' => str_repeat('1', 101)]] as $query) {
        $this->getJson(route('changes.index', $query))->assertUnprocessable();
    }
    $this->getJson(route('staff.changes.index', ['topics' => 'wallet']))->assertUnprocessable()->assertJsonValidationErrors('topics');
    $this->getJson(route('staff.changes.index', ['topics' => 'purchase']))->assertUnprocessable()->assertJsonValidationErrors('topics');
});

it('limits each account\'s beacon reads', function (): void {
    $investor = InvestorWalletFixture::investor();
    $this->actingAs($investor['user']);
    for ($read = 0; $read < 30; $read++) {
        $this->getJson(route('changes.index', ['topics' => 'wallet']))->assertOk();
    }

    $this->getJson(route('changes.index', ['topics' => 'wallet']))->assertTooManyRequests();
});
