<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\Account\OneTimeCode;
use App\Notifications\Account\OneTimeCodePurpose;
use App\Notifications\Auditor\AccreditationDecided;
use App\Notifications\Auditor\AuditJobType;
use App\Notifications\Auditor\JobOffered;
use App\Notifications\Business\ApplicationDecided;
use App\Notifications\Business\ApplicationOutcome;
use App\Notifications\Business\DisbursementSent;
use App\Notifications\Business\RaiseExpired as BusinessRaiseExpired;
use App\Notifications\Business\RepaymentReminder;
use App\Notifications\Business\RepaymentStage;
use App\Notifications\Business\ReportingWindowOpen;
use App\Notifications\Investor\InvestmentConfirmed;
use App\Notifications\Investor\PaymentDelayed;
use App\Notifications\Investor\PayoutReceived;
use App\Notifications\Investor\RaiseExpired as InvestorRaiseExpired;
use App\Notifications\Investor\ReportPublished;
use App\Notifications\Investor\VerificationDecided;
use App\Notifications\Investor\WalletTransfer;
use App\Notifications\Investor\WalletTransferCompleted;
use App\Notifications\RozineMail;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;

/**
 * Each Rozine email's subject, audience and content. Amounts are whole francs and every date is
 * shown in Kigali; the plain-text part is asserted because it carries exactly what a reader sees.
 */
function rozineMailRecipient(): User
{
    return (new User)->forceFill(['id' => 7, 'name' => 'Aline Uwase', 'email' => 'aline.uwase@example.test']);
}

/**
 * @return array{0: MailMessage, 1: string, 2: string}
 */
function rozineMail(RozineMail $notification): array
{
    $mail = $notification->toMail(rozineMailRecipient());

    return [
        $mail,
        (string) $mail->render(),
        (string) app(Markdown::class)->renderText((string) $mail->markdown, $mail->data()),
    ];
}

function kigali(string $moment): CarbonImmutable
{
    return CarbonImmutable::parse($moment, 'Africa/Kigali');
}

test('a Rozine email is delivered by mail to the account it names', function () {
    Notification::fake();
    $user = User::factory()->create();

    $user->notify(new OneTimeCode('482913', 10));

    Notification::assertSentTo($user, OneTimeCode::class, function (OneTimeCode $notification, array $channels): bool {
        return $channels === ['mail'] && $notification->code === '482913';
    });
});

test('the sign-up code email shows the code and its expiry but keeps it out of the subject', function () {
    [$mail, $html, $text] = rozineMail(new OneTimeCode('482913', 10));

    expect($mail->subject)->toBe('Your Rozine verification code')
        ->and($mail->subject)->not->toContain('482913')
        ->and($html)->toContain('class="code-value"')
        ->and($html)->toContain('brand-tile-account')
        ->and($text)->toContain('Confirm your email')
        ->and($text)->toContain('One-time code: 482913')
        ->and($text)->toContain('Expires in 10 minutes. Never share it');
});

test('the business registration code email names the business and the RDB contact', function () {
    [$mail, $html, $text] = rozineMail(new OneTimeCode('105822', 15, OneTimeCodePurpose::BusinessRegistration, 'Karongi Freight Ltd'));

    expect($mail->subject)->toBe('Your code to register Karongi Freight Ltd on Rozine')
        ->and($html)->toContain('brand-tile-business')
        ->and($html)->toContain('For business')
        ->and($text)->toContain('Verify your business')
        ->and($text)->toContain('registered for this business at the Rwanda Development Board')
        ->and($text)->toContain('One-time code: 105822');
});

test('a one-time code must be six digits and a business code must name its business', function (Closure $make, string $code) {
    expect($make)->toThrow(InvalidArgumentException::class, $code);
})->with([
    'too short' => [fn () => new OneTimeCode('48291', 10), 'MAIL_ONE_TIME_CODE_NOT_SIX_DIGITS'],
    'not digits' => [fn () => new OneTimeCode('48291a', 10), 'MAIL_ONE_TIME_CODE_NOT_SIX_DIGITS'],
    'no business' => [fn () => new OneTimeCode('482913', 10, OneTimeCodePurpose::BusinessRegistration), 'MAIL_ONE_TIME_CODE_BUSINESS_REQUIRED'],
    'blank business' => [fn () => new OneTimeCode('482913', 10, OneTimeCodePurpose::BusinessRegistration, '  '), 'MAIL_ONE_TIME_CODE_BUSINESS_REQUIRED'],
]);

test('an approved identity verification welcomes the Investor', function () {
    [$mail, $html, $text] = rozineMail(VerificationDecided::approved('https://rozine.test/investor'));

    expect($mail->subject)->toBe('You are verified on Rozine')
        ->and($html)->toContain('brand-tile-investor')
        ->and($html)->toContain('notice-success')
        ->and($text)->toContain('KYC status: Verified')
        ->and($text)->toContain('Start exploring: https://rozine.test/investor');
});

test('a rejected identity verification gives the Compliance reason', function () {
    [$mail, $html, $text] = rozineMail(VerificationDecided::rejected('the photo of your ID is blurred', 'https://rozine.test/investor'));

    expect($mail->subject)->toBe('Your identity details need another look')
        ->and($html)->toContain('notice-warning')
        ->and($text)->toContain('Compliance could not verify these details: the photo of your ID is blurred')
        ->and($text)->toContain('Update your details: https://rozine.test/investor');
});

test('an investment receipt shows the francs in and back and states the risk of loss', function () {
    [$mail, , $text] = rozineMail(new InvestmentConfirmed('Karongi Freight Ltd', '150000', 30, '24840', '174840', kigali('2027-04-07'), 'RZ-PRI-7Q4K2M', 'https://rozine.test/investor'));

    expect($mail->subject)->toBe('Investment confirmed: Karongi Freight Ltd')
        ->and($text)->toContain('You invested RWF 150,000 in Karongi Freight Ltd.')
        ->and($text)->toContain('Notes: 30 notes')
        ->and($text)->toContain('Expected return: RWF 24,840')
        ->and($text)->toContain('Maturity value: RWF 174,840')
        ->and($text)->toContain('Maturity date: 7 Apr 2027')
        ->and($text)->toContain('Reference: RZ-PRI-7Q4K2M')
        ->and($text)->toContain('Returns are projected, not guaranteed. You can lose some or all of the money you invest.');
});

test('a single note is counted in the singular', function () {
    [, , $text] = rozineMail(new InvestmentConfirmed('Karongi Freight Ltd', '5000', 1, '830', '5830', kigali('2027-04-07'), 'RZ-PRI-1', 'https://rozine.test/investor'));

    expect($text)->toContain('Notes: 1 note')
        ->and($text)->not->toContain('1 notes');
});

test('a deposit and a withdrawal each name their account and the balance left', function () {
    $completedAt = CarbonImmutable::parse('2026-10-07 12:42:00', 'UTC');

    [$deposit, , $depositText] = rozineMail(new WalletTransferCompleted(WalletTransfer::Deposit, '200000', 'MTN MoMo ·· 4821', 'RZ-DEP-1', '212500', $completedAt, 'https://rozine.test/wallet'));
    [$withdrawal, , $withdrawalText] = rozineMail(new WalletTransferCompleted(WalletTransfer::Withdrawal, '50000', 'Bank of Kigali ·· 0193', 'RZ-WDR-1', '162500', $completedAt, 'https://rozine.test/wallet'));

    expect($deposit->subject)->toBe('Deposit received: RWF 200,000')
        ->and($depositText)->toContain('RWF 200,000 from MTN MoMo ·· 4821 is in your Rozine wallet.')
        ->and($depositText)->toContain('From: MTN MoMo ·· 4821')
        ->and($depositText)->toContain('Completed: 7 Oct 2026, 14:42 CAT')
        ->and($depositText)->toContain('Available balance: RWF 212,500')
        ->and($withdrawal->subject)->toBe('Withdrawal sent: RWF 50,000')
        ->and($withdrawalText)->toContain('RWF 50,000 is on its way to Bank of Kigali ·· 0193.')
        ->and($withdrawalText)->toContain('To: Bank of Kigali ·· 0193');
});

test('a payout shows its split as posted and the next payment date', function () {
    [$mail, , $text] = rozineMail(new PayoutReceived('Karongi Freight Ltd', '29140', '25000', '4140', 2, 6, kigali('2026-12-07'), 'https://rozine.test/holding'));

    expect($mail->subject)->toBe('Payout received from Karongi Freight Ltd')
        ->and($text)->toContain('RWF 29,140 from Karongi Freight Ltd is in your wallet. This is payment 2 of 6.')
        ->and($text)->toContain('Principal: RWF 25,000')
        ->and($text)->toContain('Return: RWF 4,140')
        ->and($text)->toContain('Next payment: 7 Dec 2026');
});

test('the final payout says the holding is fully repaid', function () {
    [, , $text] = rozineMail(new PayoutReceived('Karongi Freight Ltd', '29140', '25000', '4140', 6, 6, null, 'https://rozine.test/holding'));

    expect($text)->toContain('Next payment: Fully repaid');
});

test('a delayed payment warns the Investor with the days overdue', function (int $days, string $label) {
    [$mail, $html, $text] = rozineMail(new PaymentDelayed('Karongi Freight Ltd', '29140', kigali('2026-10-07'), $days, 'https://rozine.test/holding'));

    expect($mail->subject)->toBe('Payment delayed: Karongi Freight Ltd')
        ->and($html)->toContain('notice-warning')
        ->and($text)->toContain("Payment delayed · {$label}")
        ->and($text)->toContain('The repayment of RWF 29,140 from Karongi Freight Ltd, due on 7 Oct 2026, has not arrived yet.');
})->with([
    'one day' => [1, '1 day overdue'],
    'several days' => [4, '4 days overdue'],
]);

test('a published report names its period and Audit Partner', function () {
    [$mail, , $text] = rozineMail(new ReportPublished('Karongi Freight Ltd', 'September 2026', 'Jean Habimana, CPA', 'https://rozine.test/report'));

    expect($mail->subject)->toBe('Karongi Freight Ltd published its September 2026 report')
        ->and($text)->toContain('Verified · co-signed by Audit Partner Jean Habimana, CPA')
        ->and($text)->toContain("both parties' notes")
        ->and($text)->toContain('Read the report: https://rozine.test/report');
});

test('an expired raise refunds the Investor in full with no fee', function () {
    [$mail, , $text] = rozineMail(new InvestorRaiseExpired('Karongi Freight Ltd', '150000', 'https://rozine.test/deals'));

    expect($mail->subject)->toBe('Refunded: the Karongi Freight Ltd raise closed')
        ->and($text)->toContain('did not reach its target within 30 days')
        ->and($text)->toContain('Returned to your wallet: RWF 150,000')
        ->and($text)->toContain('Fee: RWF 0');
});

test('an application decision gives its outcome and, unless approved, the reason', function (ApplicationOutcome $outcome, ?string $reason, string $subject, string $tone, string $line) {
    [$mail, $html, $text] = rozineMail(new ApplicationDecided($outcome, 'Karongi Freight Ltd', '12000000', 6, 'https://rozine.test/business', $reason));

    expect($mail->subject)->toBe($subject)
        ->and($html)->toContain('brand-tile-business')
        ->and($html)->toContain('button-cell-business')
        ->and($html)->toContain("notice-{$tone}")
        ->and($text)->toContain($line)
        ->and($text)->toContain('Amount: RWF 12,000,000')
        ->and($text)->toContain('Tenor: 6 months');
})->with([
    'approved' => [ApplicationOutcome::Approved, null, 'Your application is approved', 'success', 'Approved for listing'],
    'returned' => [ApplicationOutcome::Returned, 'a statement page is missing', 'Your application needs changes', 'warning', 'Returned: a statement page is missing'],
    'declined' => [ApplicationOutcome::Declined, 'cash flow is below 1.3× the repayment', 'Your application was not approved', 'danger', 'Declined: cash flow is below 1.3× the repayment'],
]);

test('a one-month tenor is shown in the singular', function () {
    [, , $text] = rozineMail(new ApplicationDecided(ApplicationOutcome::Approved, 'Karongi Freight Ltd', '5000000', 1, 'https://rozine.test/business'));

    expect($text)->toContain('Tenor: 1 month')
        ->and($text)->not->toContain('1 months');
});

test('a returned or declined application must carry its reason', function (ApplicationOutcome $outcome, ?string $reason) {
    expect(fn () => new ApplicationDecided($outcome, 'Karongi Freight Ltd', '12000000', 6, 'https://rozine.test/business', $reason))
        ->toThrow(InvalidArgumentException::class, 'MAIL_APPLICATION_REASON_REQUIRED');
})->with([
    'returned without reason' => [ApplicationOutcome::Returned, null],
    'declined with a blank reason' => [ApplicationOutcome::Declined, ' '],
]);

test('a disbursement names the account paid and the first repayment', function () {
    [$mail, , $text] = rozineMail(new DisbursementSent('Karongi Freight Ltd', '12000000', 'Bank of Kigali ·· 7710', 'RZ-DSB-1', '2185000', kigali('2026-11-07'), 'https://rozine.test/business'));

    expect($mail->subject)->toBe('Funds sent: RWF 12,000,000')
        ->and($text)->toContain('Investors fully funded Karongi Freight Ltd. We sent RWF 12,000,000 to Bank of Kigali ·· 7710.')
        ->and($text)->toContain('First repayment: RWF 2,185,000')
        ->and($text)->toContain('First repayment due: 7 Nov 2026');
});

test('a repayment reminder warns before the due date and escalates after it', function () {
    [$upcoming, $upcomingHtml, $upcomingText] = rozineMail(new RepaymentReminder(RepaymentStage::Upcoming, 'Karongi Freight Ltd', '2185000', kigali('2026-11-07'), 1, 6, 'https://rozine.test/business'));
    [$overdue, $overdueHtml, $overdueText] = rozineMail(new RepaymentReminder(RepaymentStage::Overdue, 'Karongi Freight Ltd', '2185000', kigali('2026-11-07'), 1, 6, 'https://rozine.test/business', 3));

    expect($upcoming->subject)->toBe('Repayment due on 7 Nov 2026')
        ->and($upcomingHtml)->not->toContain('notice-danger')
        ->and($upcomingText)->toContain('Repayment coming up')
        ->and($upcomingText)->toContain('Instalment: 1 of 6')
        ->and($overdue->subject)->toBe('Repayment overdue: Karongi Freight Ltd')
        ->and($overdueHtml)->toContain('notice-danger')
        ->and($overdueText)->toContain('3 days overdue')
        ->and($overdueText)->toContain('Pay it now to protect your rating and standing');
});

test('the reporting window lists what to submit and when it closes', function () {
    [$mail, , $text] = rozineMail(new ReportingWindowOpen('Karongi Freight Ltd', 'September 2026', kigali('2026-10-07'), 'https://rozine.test/business'));

    expect($mail->subject)->toBe('Your September 2026 report is due by 7 Oct 2026')
        ->and($text)->toContain('Add a short note, up to 100 characters.')
        ->and($text)->toContain('Add up to five photographs.')
        ->and($text)->toContain("Submit it for your Audit Partner's co-signature.");
});

test('an expired raise tells the Business investors were refunded and no fee applies', function () {
    [$mail, , $text] = rozineMail(new BusinessRaiseExpired('Karongi Freight Ltd', '12000000', '7450000', 'https://rozine.test/business'));

    expect($mail->subject)->toBe('Your raise closed without full funding')
        ->and($text)->toContain('Target: RWF 12,000,000')
        ->and($text)->toContain('Committed by investors: RWF 7,450,000')
        ->and($text)->toContain('Fee: RWF 0');
});

test('a job offer gives the Audit Partner its type, place and deadline in Kigali time', function (AuditJobType $type, string $subject, string $kind) {
    [$mail, $html, $text] = rozineMail(new JobOffered($type, 'Karongi Freight Ltd', 'Karongi, Western Province', CarbonImmutable::parse('2026-10-08 16:00:00', 'UTC'), 'https://rozine.test/auditor/jobs'));

    expect($mail->subject)->toBe($subject)
        ->and($html)->toContain('brand-tile-auditor')
        ->and($html)->toContain('button-cell-auditor')
        ->and($text)->toContain("Job type: {$kind}")
        ->and($text)->toContain('Respond by: 8 Oct 2026, 18:00 CAT')
        ->and($text)->toContain('the job is offered to another Audit Partner');
})->with([
    'routine' => [AuditJobType::Routine, 'New audit job: Karongi Freight Ltd', 'Routine audit'],
    'flash' => [AuditJobType::Flash, 'Flash Audit offered: Karongi Freight Ltd', 'Flash Audit'],
]);

test('a Flash Audit offer states its 24-hour clock', function () {
    [, $html, $text] = rozineMail(new JobOffered(AuditJobType::Flash, 'Karongi Freight Ltd', 'Karongi', kigali('2026-10-07 20:00'), 'https://rozine.test/auditor/jobs'));

    expect($html)->toContain('notice-danger')
        ->and($text)->toContain('Flash Audit · runs on a 24-hour clock');
});

test('an accreditation decision activates the Audit Partner or gives the reason', function () {
    [$approved, , $approvedText] = rozineMail(AccreditationDecided::approved('https://rozine.test/auditor'));
    [$rejected, , $rejectedText] = rozineMail(AccreditationDecided::rejected('the practising certificate has expired', 'https://rozine.test/auditor/profile'));

    expect($approved->subject)->toBe('Your accreditation is active')
        ->and($approvedText)->toContain('ICPAR licence verified · Active')
        ->and($rejected->subject)->toBe('Your accreditation needs another look')
        ->and($rejectedText)->toContain('We could not verify your accreditation: the practising certificate has expired');
});

test('amounts are whole francs with grouped digits', function (string $amount, string $shown) {
    [, , $text] = rozineMail(new InvestorRaiseExpired('Karongi Freight Ltd', $amount, 'https://rozine.test/deals'));

    expect($text)->toContain("Returned to your wallet: {$shown}");
})->with([
    'zero' => ['0', 'RWF 0'],
    'leading zeros' => ['000', 'RWF 0'],
    'hundreds' => ['950', 'RWF 950'],
    'thousands' => ['5000', 'RWF 5,000'],
    'millions' => ['1500000', 'RWF 1,500,000'],
]);

test('an amount that is not whole francs is refused rather than mailed', function (string $amount) {
    expect(fn () => rozineMail(new InvestorRaiseExpired('Karongi Freight Ltd', $amount, 'https://rozine.test/deals')))
        ->toThrow(InvalidArgumentException::class, 'MAIL_AMOUNT_NOT_WHOLE_FRANCS');
})->with(['decimal' => '1500.50', 'negative' => '-100', 'grouped' => '1,500', 'empty' => '']);
