<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mail template previews (local and testing only)
|--------------------------------------------------------------------------
|
| One synthetic instance of every Rozine email, keyed by its template name,
| for the mail preview route in routes/preview.php. Names, amounts, codes and
| links are invented; nothing here is a real person, business or payment.
|
*/

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
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Notification;

$business = 'Karongi Freight Ltd';
$on = fn (string $date): CarbonImmutable => CarbonImmutable::parse($date, 'Africa/Kigali');
$link = fn (string $path): string => url($path);

/** @var array<string, Closure(): Notification> */
return [
    'account.verify-email' => fn () => new VerifyEmail,
    'account.reset-password' => fn () => new ResetPassword('synthetic-reset-token'),
    'account.one-time-code' => fn () => new OneTimeCode('482913', 10),
    'account.one-time-code-business' => fn () => new OneTimeCode('482913', 10, OneTimeCodePurpose::BusinessRegistration, $business),

    'investor.verification-approved' => fn () => VerificationDecided::approved($link('/investor')),
    'investor.verification-rejected' => fn () => VerificationDecided::rejected('the name on your ID does not match your account', $link('/investor')),
    'investor.investment-confirmed' => fn () => new InvestmentConfirmed($business, '150000', 30, '24840', '174840', $on('2027-04-07'), 'RZ-PRI-7Q4K2M', $link('/investor')),
    'investor.deposit-received' => fn () => new WalletTransferCompleted(WalletTransfer::Deposit, '200000', 'MTN MoMo ·· 4821', 'RZ-DEP-3H8N1C', '212500', $on('2026-10-07 14:42'), $link('/investor/wallet')),
    'investor.withdrawal-sent' => fn () => new WalletTransferCompleted(WalletTransfer::Withdrawal, '50000', 'Bank of Kigali ·· 0193', 'RZ-WDR-9T2V6P', '162500', $on('2026-10-07 16:05'), $link('/investor/wallet')),
    'investor.payout-received' => fn () => new PayoutReceived($business, '29140', '25000', '4140', 2, 6, $on('2026-12-07'), $link('/investor')),
    'investor.payout-final' => fn () => new PayoutReceived($business, '29140', '25000', '4140', 6, 6, null, $link('/investor')),
    'investor.payment-delayed' => fn () => new PaymentDelayed($business, '29140', $on('2026-10-07'), 4, $link('/investor')),
    'investor.report-published' => fn () => new ReportPublished($business, 'September 2026', 'Jean Habimana, CPA', $link('/investor')),
    'investor.raise-expired' => fn () => new InvestorRaiseExpired($business, '150000', $link('/investor/deals')),

    'business.application-approved' => fn () => new ApplicationDecided(ApplicationOutcome::Approved, $business, '12000000', 6, $link('/business')),
    'business.application-returned' => fn () => new ApplicationDecided(ApplicationOutcome::Returned, $business, '12000000', 6, $link('/business'), 'the September bank statement is missing its last page'),
    'business.application-declined' => fn () => new ApplicationDecided(ApplicationOutcome::Declined, $business, '12000000', 6, $link('/business'), 'monthly cash flow covers 0.9× the repayment; 1.3× is required'),
    'business.disbursement-sent' => fn () => new DisbursementSent($business, '12000000', 'Bank of Kigali ·· 7710', 'RZ-DSB-5K1W8D', '2185000', $on('2026-11-07'), $link('/business')),
    'business.repayment-upcoming' => fn () => new RepaymentReminder(RepaymentStage::Upcoming, $business, '2185000', $on('2026-11-07'), 1, 6, $link('/business')),
    'business.repayment-overdue' => fn () => new RepaymentReminder(RepaymentStage::Overdue, $business, '2185000', $on('2026-11-07'), 1, 6, $link('/business'), 3, '359'),
    'business.reporting-window-open' => fn () => new ReportingWindowOpen($business, 'September 2026', $on('2026-10-07'), $link('/business')),
    'business.raise-expired' => fn () => new BusinessRaiseExpired($business, '12000000', '7450000', $link('/business')),

    'auditor.job-offered' => fn () => new JobOffered(AuditJobType::Routine, $business, 'Karongi, Western Province', $on('2026-10-08 18:00'), $link('/auditor/jobs')),
    'auditor.flash-audit-offered' => fn () => new JobOffered(AuditJobType::Flash, $business, 'Karongi, Western Province', $on('2026-10-07 20:00'), $link('/auditor/jobs')),
    'auditor.accreditation-approved' => fn () => AccreditationDecided::approved($link('/auditor')),
    'auditor.accreditation-rejected' => fn () => AccreditationDecided::rejected('the practising certificate has expired', $link('/auditor/profile')),
];
