<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditReportWindow;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditSealingFixture;

use function Pest\Laravel\actingAs;

/*
 * The Jobs page's monthly section (design L245–318): each monthly visit the partner holds is a
 * window to open, a report on the clock, or a disputed report under desk review.
 */

beforeEach(function (): void {
    // Early in the month, while last month's window (sealed by the 7th) is still open.
    $this->travelTo(now('UTC')->startOfMonth()->addDays(3)->setTime(10, 0));
    $this->withoutVite();
});

/** @return array{windows: list<array<string, mixed>>, reports: list<array<string, mixed>>, desk_reviews: list<array<string, mixed>>}|null */
function auditorMonthly(User $user): ?array
{
    $monthly = [];
    actingAs($user)->get('/auditor/jobs')->assertOk()->assertInertia(function (Assert $page) use (&$monthly): Assert {
        $monthly = $page->toArray()['props']['monthly'];

        return $page->component('auditor/jobs');
    });

    return $monthly;
}

it('shows an accepted monthly visit with no report yet as a window to open, linked to its file', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $user = $fixture['partners'][0]['user'];
    $assignment = AuditAssignmentFixture::request($fixture, 'routine');
    expect(auditorMonthly($user))->toBeNull();

    AuditAssignmentFixture::respond($user, $assignment);
    $period = now('Africa/Kigali')->startOfMonth()->subMonth()->format('Y-m');

    expect(auditorMonthly($user))->toBe(['windows' => [['id' => $assignment->id, 'note_title' => 'Synthetic business', 'note_id' => null,
        'business' => 'Synthetic business', 'month' => $period.'-01', 'link' => ['url' => '/auditor/jobs/'.$assignment->id, 'method' => 'get']]],
        'reports' => [], 'desk_reviews' => []]);
});

it('puts a started monthly report on the clock, then awaiting co-signature, then under desk review once disputed', function (): void {
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    $user = $fixture['user'];
    $report = $fixture['report'];
    $period = $report->binding['period'];
    $card = ['id' => $report->id, 'business' => 'Synthetic business', 'sector' => 'retail', 'note_title' => $fixture['application']->draft['title'],
        'due_on' => app(AuditReportWindow::class)->dueAt($period), 'status' => 'in_progress', 'inflow' => null, 'outflow' => null, 'cover' => null,
        'link' => ['url' => '/auditor/reports/'.$report->id, 'method' => 'get']];
    expect(auditorMonthly($user))->toBe(['windows' => [], 'reports' => [$card], 'desk_reviews' => []]);

    AuditSealingFixture::seal($fixture);
    expect(auditorMonthly($user))->toBe(['windows' => [], 'reports' => [[...$card, 'status' => 'awaiting_cosign']], 'desk_reviews' => []]);

    $business = ['business' => $fixture['audit']['business'], 'report' => $report->id];
    actingAs($fixture['audit']['authority']['users'][0])->post(route('business.audit-reports.dispute', $business), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => 1,
        'report_revision' => $report->revision + 1, 'mandate_version' => 1, 'digest' => $fixture['fields']['digest'],
        'proof_files' => [UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\nSynthetic monthly proof\n%%EOF")],
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('code', 'REPORT_DISPUTED');

    expect(auditorMonthly($user))->toBe(['windows' => [], 'reports' => [], 'desk_reviews' => [['id' => $report->id, 'business' => 'Synthetic business',
        'month' => $period.'-01', 'status' => 'disputed', 'link' => ['url' => '/auditor/reports/'.$report->id, 'method' => 'get']]]]);
});

it('marks a monthly report still open after its window closes as overdue', function (): void {
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    $this->travelTo(now()->parse((string) app(AuditReportWindow::class)->dueAt($fixture['report']->binding['period']))->addSecond());

    expect(auditorMonthly($fixture['user'])['reports'][0]['status'])->toBe('overdue');
});
