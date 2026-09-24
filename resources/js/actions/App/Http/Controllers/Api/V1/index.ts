import IdentityController from './IdentityController'
import StaffAccessController from './StaffAccessController'
import IdentityManagementController from './IdentityManagementController'
import RoleBookmarkController from './RoleBookmarkController'
import AuditorJobsController from './AuditorJobsController'
import AuditorProfileController from './AuditorProfileController'

const V1 = {
    IdentityController: Object.assign(IdentityController, IdentityController),
    StaffAccessController: Object.assign(StaffAccessController, StaffAccessController),
    IdentityManagementController: Object.assign(IdentityManagementController, IdentityManagementController),
    RoleBookmarkController: Object.assign(RoleBookmarkController, RoleBookmarkController),
    AuditorJobsController: Object.assign(AuditorJobsController, AuditorJobsController),
    AuditorProfileController: Object.assign(AuditorProfileController, AuditorProfileController),
}

export default V1