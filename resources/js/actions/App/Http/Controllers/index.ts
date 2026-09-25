import Api from './Api'
import AuditOperationsController from './AuditOperationsController'
import SiteController from './SiteController'
import PulseController from './PulseController'
import DashboardController from './DashboardController'
import RoleHomeController from './RoleHomeController'
import StaffHomeController from './StaffHomeController'
import AuditorProfileController from './AuditorProfileController'
import AuditorProcedureController from './AuditorProcedureController'
import AuditorEngagementController from './AuditorEngagementController'
import AuditorJobsController from './AuditorJobsController'
import BusinessApplicationController from './BusinessApplicationController'
import IdentityManagementController from './IdentityManagementController'
import RoleBookmarkController from './RoleBookmarkController'
import Settings from './Settings'

const Controllers = {
    Api: Object.assign(Api, Api),
    AuditOperationsController: Object.assign(AuditOperationsController, AuditOperationsController),
    SiteController: Object.assign(SiteController, SiteController),
    PulseController: Object.assign(PulseController, PulseController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    RoleHomeController: Object.assign(RoleHomeController, RoleHomeController),
    StaffHomeController: Object.assign(StaffHomeController, StaffHomeController),
    AuditorProfileController: Object.assign(AuditorProfileController, AuditorProfileController),
    AuditorProcedureController: Object.assign(AuditorProcedureController, AuditorProcedureController),
    AuditorEngagementController: Object.assign(AuditorEngagementController, AuditorEngagementController),
    AuditorJobsController: Object.assign(AuditorJobsController, AuditorJobsController),
    BusinessApplicationController: Object.assign(BusinessApplicationController, BusinessApplicationController),
    IdentityManagementController: Object.assign(IdentityManagementController, IdentityManagementController),
    RoleBookmarkController: Object.assign(RoleBookmarkController, RoleBookmarkController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers