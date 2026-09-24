import IdentityController from './IdentityController'
import StaffAccessController from './StaffAccessController'
import IdentityManagementController from './IdentityManagementController'
import RoleBookmarkController from './RoleBookmarkController'
import AuditorProfileController from './AuditorProfileController'

const V1 = {
    IdentityController: Object.assign(IdentityController, IdentityController),
    StaffAccessController: Object.assign(StaffAccessController, StaffAccessController),
    IdentityManagementController: Object.assign(IdentityManagementController, IdentityManagementController),
    RoleBookmarkController: Object.assign(RoleBookmarkController, RoleBookmarkController),
    AuditorProfileController: Object.assign(AuditorProfileController, AuditorProfileController),
}

export default V1