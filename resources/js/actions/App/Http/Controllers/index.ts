import AuditSealVerificationController from './AuditSealVerificationController'
import Api from './Api'
import AuditDisputeController from './AuditDisputeController'
import BusinessRepaymentController from './BusinessRepaymentController'
import BusinessHomeController from './BusinessHomeController'
import BusinessNoticesController from './BusinessNoticesController'
import BusinessWalletController from './BusinessWalletController'
import StaffDisbursementController from './StaffDisbursementController'
import AuditOperationsController from './AuditOperationsController'
import AuditorNoticesController from './AuditorNoticesController'
import StaffApplicationReleaseController from './StaffApplicationReleaseController'
import BusinessPublicationController from './BusinessPublicationController'
import InvestorDealsController from './InvestorDealsController'
import InvestorWalletController from './InvestorWalletController'
import InvestorRiskAcknowledgementController from './InvestorRiskAcknowledgementController'
import InvestorNoticesController from './InvestorNoticesController'
import InvestorPrimaryController from './InvestorPrimaryController'
import InvestorHoldingController from './InvestorHoldingController'
import InvestorVerificationController from './InvestorVerificationController'
import StaffInvestorDirectoryController from './StaffInvestorDirectoryController'
import StaffBusinessDirectoryController from './StaffBusinessDirectoryController'
import StaffAuditorDirectoryController from './StaffAuditorDirectoryController'
import StaffDashboardController from './StaffDashboardController'
import StaffDirectoryController from './StaffDirectoryController'
import StaffActivityController from './StaffActivityController'
import StaffReconciliationController from './StaffReconciliationController'
import StaffMessagingController from './StaffMessagingController'
import StaffLedgerController from './StaffLedgerController'
import StaffExceptionsController from './StaffExceptionsController'
import StaffPrimaryMarketController from './StaffPrimaryMarketController'
import StaffPaymentsController from './StaffPaymentsController'
import StaffCoverageController from './StaffCoverageController'
import StaffComplaintController from './StaffComplaintController'
import StaffInvestorVerificationController from './StaffInvestorVerificationController'
import ChangeFeedController from './ChangeFeedController'
import SiteController from './SiteController'
import BusinessAuditReportController from './BusinessAuditReportController'
import EmailVerificationCodeController from './EmailVerificationCodeController'
import PulseController from './PulseController'
import DashboardController from './DashboardController'
import EnterAppController from './EnterAppController'
import RoleHomeController from './RoleHomeController'
import AuditorHomeController from './AuditorHomeController'
import StaffHomeController from './StaffHomeController'
import AuditorProfileController from './AuditorProfileController'
import InvestorProfileController from './InvestorProfileController'
import InvestorPortfolioController from './InvestorPortfolioController'
import InvestorComplaintController from './InvestorComplaintController'
import InvestorMarketController from './InvestorMarketController'
import AuditorProcedureController from './AuditorProcedureController'
import AuditorEngagementController from './AuditorEngagementController'
import AuditorJobsController from './AuditorJobsController'
import AuditorPortfolioController from './AuditorPortfolioController'
import BusinessContactController from './BusinessContactController'
import BusinessComplaintController from './BusinessComplaintController'
import BusinessApplicationController from './BusinessApplicationController'
import IdentityManagementController from './IdentityManagementController'
import RoleBookmarkController from './RoleBookmarkController'
import Settings from './Settings'
import StaffSectionController from './StaffSectionController'
import StaffStagingMailTesterController from './StaffStagingMailTesterController'

const Controllers = {
    AuditSealVerificationController: Object.assign(AuditSealVerificationController, AuditSealVerificationController),
    Api: Object.assign(Api, Api),
    AuditDisputeController: Object.assign(AuditDisputeController, AuditDisputeController),
    BusinessRepaymentController: Object.assign(BusinessRepaymentController, BusinessRepaymentController),
    BusinessHomeController: Object.assign(BusinessHomeController, BusinessHomeController),
    BusinessNoticesController: Object.assign(BusinessNoticesController, BusinessNoticesController),
    BusinessWalletController: Object.assign(BusinessWalletController, BusinessWalletController),
    StaffDisbursementController: Object.assign(StaffDisbursementController, StaffDisbursementController),
    AuditOperationsController: Object.assign(AuditOperationsController, AuditOperationsController),
    AuditorNoticesController: Object.assign(AuditorNoticesController, AuditorNoticesController),
    StaffApplicationReleaseController: Object.assign(StaffApplicationReleaseController, StaffApplicationReleaseController),
    BusinessPublicationController: Object.assign(BusinessPublicationController, BusinessPublicationController),
    InvestorDealsController: Object.assign(InvestorDealsController, InvestorDealsController),
    InvestorWalletController: Object.assign(InvestorWalletController, InvestorWalletController),
    InvestorRiskAcknowledgementController: Object.assign(InvestorRiskAcknowledgementController, InvestorRiskAcknowledgementController),
    InvestorNoticesController: Object.assign(InvestorNoticesController, InvestorNoticesController),
    InvestorPrimaryController: Object.assign(InvestorPrimaryController, InvestorPrimaryController),
    InvestorHoldingController: Object.assign(InvestorHoldingController, InvestorHoldingController),
    InvestorVerificationController: Object.assign(InvestorVerificationController, InvestorVerificationController),
    StaffInvestorDirectoryController: Object.assign(StaffInvestorDirectoryController, StaffInvestorDirectoryController),
    StaffBusinessDirectoryController: Object.assign(StaffBusinessDirectoryController, StaffBusinessDirectoryController),
    StaffAuditorDirectoryController: Object.assign(StaffAuditorDirectoryController, StaffAuditorDirectoryController),
    StaffDashboardController: Object.assign(StaffDashboardController, StaffDashboardController),
    StaffDirectoryController: Object.assign(StaffDirectoryController, StaffDirectoryController),
    StaffActivityController: Object.assign(StaffActivityController, StaffActivityController),
    StaffReconciliationController: Object.assign(StaffReconciliationController, StaffReconciliationController),
    StaffMessagingController: Object.assign(StaffMessagingController, StaffMessagingController),
    StaffLedgerController: Object.assign(StaffLedgerController, StaffLedgerController),
    StaffExceptionsController: Object.assign(StaffExceptionsController, StaffExceptionsController),
    StaffPrimaryMarketController: Object.assign(StaffPrimaryMarketController, StaffPrimaryMarketController),
    StaffPaymentsController: Object.assign(StaffPaymentsController, StaffPaymentsController),
    StaffCoverageController: Object.assign(StaffCoverageController, StaffCoverageController),
    StaffComplaintController: Object.assign(StaffComplaintController, StaffComplaintController),
    StaffInvestorVerificationController: Object.assign(StaffInvestorVerificationController, StaffInvestorVerificationController),
    ChangeFeedController: Object.assign(ChangeFeedController, ChangeFeedController),
    SiteController: Object.assign(SiteController, SiteController),
    BusinessAuditReportController: Object.assign(BusinessAuditReportController, BusinessAuditReportController),
    EmailVerificationCodeController: Object.assign(EmailVerificationCodeController, EmailVerificationCodeController),
    PulseController: Object.assign(PulseController, PulseController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    EnterAppController: Object.assign(EnterAppController, EnterAppController),
    RoleHomeController: Object.assign(RoleHomeController, RoleHomeController),
    AuditorHomeController: Object.assign(AuditorHomeController, AuditorHomeController),
    StaffHomeController: Object.assign(StaffHomeController, StaffHomeController),
    AuditorProfileController: Object.assign(AuditorProfileController, AuditorProfileController),
    InvestorProfileController: Object.assign(InvestorProfileController, InvestorProfileController),
    InvestorPortfolioController: Object.assign(InvestorPortfolioController, InvestorPortfolioController),
    InvestorComplaintController: Object.assign(InvestorComplaintController, InvestorComplaintController),
    InvestorMarketController: Object.assign(InvestorMarketController, InvestorMarketController),
    AuditorProcedureController: Object.assign(AuditorProcedureController, AuditorProcedureController),
    AuditorEngagementController: Object.assign(AuditorEngagementController, AuditorEngagementController),
    AuditorJobsController: Object.assign(AuditorJobsController, AuditorJobsController),
    AuditorPortfolioController: Object.assign(AuditorPortfolioController, AuditorPortfolioController),
    BusinessContactController: Object.assign(BusinessContactController, BusinessContactController),
    BusinessComplaintController: Object.assign(BusinessComplaintController, BusinessComplaintController),
    BusinessApplicationController: Object.assign(BusinessApplicationController, BusinessApplicationController),
    IdentityManagementController: Object.assign(IdentityManagementController, IdentityManagementController),
    RoleBookmarkController: Object.assign(RoleBookmarkController, RoleBookmarkController),
    Settings: Object.assign(Settings, Settings),
    StaffSectionController: Object.assign(StaffSectionController, StaffSectionController),
    StaffStagingMailTesterController: Object.assign(StaffStagingMailTesterController, StaffStagingMailTesterController),
}

export default Controllers