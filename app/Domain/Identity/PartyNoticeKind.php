<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * The in-app notices a Party receives (BRS v2.0 NTF-R01), each from one recorded event. A kind
 * names the facts it carries; the client words them from its catalogs, and the notice opens the
 * screen that already holds the record. A verification result, a refunded raise, a sent withdrawal
 * an issued Holding, a Business's approved application and its expired raise, and an Audit
 * Partner's accreditation decision and job offers are also e-mailed. A Business's notices belong to its entity Party.
 */
enum PartyNoticeKind: string
{
    case VerificationApproved = 'verification.approved';
    case VerificationRejected = 'verification.rejected';
    case DepositReceived = 'wallet.deposit_received';
    case WithdrawalSent = 'wallet.withdrawal_sent';
    case WithdrawalReturned = 'wallet.withdrawal_returned';
    case RaiseRefunded = 'primary.refunded';
    case ReportPublished = 'primary.report_published';
    case RaiseFunded = 'primary.raise_funded';
    case HoldingIssued = 'primary.holding_issued';
    case PasswordChanged = 'security.password_changed';
    case PayoutAccountLinked = 'security.payout_account_linked';
    case PayoutAccountUnlinked = 'security.payout_account_unlinked';
    case NewDevice = 'security.new_device';
    case BusinessDepositReceived = 'business.deposit_received';
    case BusinessApplicationApproved = 'business.application_approved';
    case BusinessRaiseExpired = 'business.raise_expired';
    case BusinessRaiseFunded = 'business.raise_funded';
    case BusinessDisbursementSent = 'business.disbursement_sent';
    case BusinessWithdrawalSent = 'business.withdrawal_sent';
    case BusinessWithdrawalReturned = 'business.withdrawal_returned';
    case BusinessReportSealed = 'business.report_sealed';
    case BusinessReportReviewClosing = 'business.report_review_closing';
    case AuditorAccreditationApproved = 'auditor.accreditation_approved';
    case AuditorAccreditationRejected = 'auditor.accreditation_rejected';
    case AuditorJobOffered = 'auditor.job_offered';

    public function mailed(): bool
    {
        return in_array($this, [self::VerificationApproved, self::VerificationRejected, self::RaiseRefunded, self::WithdrawalSent,
            self::BusinessApplicationApproved, self::BusinessRaiseExpired, self::AuditorAccreditationApproved, self::AuditorAccreditationRejected,
            self::AuditorJobOffered, self::NewDevice, self::ReportPublished, self::HoldingIssued], true);
    }

    /**
     * The category Admin Messaging History files the notice under, after the design's message
     * categories.
     *
     * @return 'onboarding'|'wallet'|'fundraising'|'portfolio'|'reporting'|'security'|'underwriting'|'payments'|'audits'
     */
    public function category(): string
    {
        return match ($this) {
            self::VerificationApproved, self::VerificationRejected => 'onboarding',
            self::DepositReceived, self::WithdrawalSent, self::WithdrawalReturned, self::BusinessDepositReceived, self::BusinessWithdrawalSent,
            self::BusinessWithdrawalReturned => 'wallet',
            self::RaiseRefunded, self::RaiseFunded, self::BusinessRaiseExpired, self::BusinessRaiseFunded => 'fundraising',
            self::HoldingIssued => 'portfolio',
            self::ReportPublished, self::BusinessReportSealed, self::BusinessReportReviewClosing => 'reporting',
            self::PasswordChanged, self::PayoutAccountLinked, self::PayoutAccountUnlinked, self::NewDevice => 'security',
            self::BusinessApplicationApproved => 'underwriting',
            self::BusinessDisbursementSent => 'payments',
            self::AuditorAccreditationApproved, self::AuditorAccreditationRejected, self::AuditorJobOffered => 'audits',
        };
    }
}
