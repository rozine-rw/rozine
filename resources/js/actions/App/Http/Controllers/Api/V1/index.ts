import BusinessApplicationController from './BusinessApplicationController'
import IdentityController from './IdentityController'
import StaffAccessController from './StaffAccessController'
import IdentityManagementController from './IdentityManagementController'
import RoleBookmarkController from './RoleBookmarkController'
import AuditorProcedureController from './AuditorProcedureController'
import AuditorEngagementController from './AuditorEngagementController'
import AuditorJobsController from './AuditorJobsController'
import AuditorProfileController from './AuditorProfileController'

const V1 = {
    BusinessApplicationController: Object.assign(BusinessApplicationController, BusinessApplicationController),
    IdentityController: Object.assign(IdentityController, IdentityController),
    StaffAccessController: Object.assign(StaffAccessController, StaffAccessController),
    IdentityManagementController: Object.assign(IdentityManagementController, IdentityManagementController),
    RoleBookmarkController: Object.assign(RoleBookmarkController, RoleBookmarkController),
    AuditorProcedureController: Object.assign(AuditorProcedureController, AuditorProcedureController),
    AuditorEngagementController: Object.assign(AuditorEngagementController, AuditorEngagementController),
    AuditorJobsController: Object.assign(AuditorJobsController, AuditorJobsController),
    AuditorProfileController: Object.assign(AuditorProfileController, AuditorProfileController),
}

export default V1