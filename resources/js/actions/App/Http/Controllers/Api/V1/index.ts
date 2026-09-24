import IdentityController from './IdentityController'
import StaffAccessController from './StaffAccessController'
import IdentityManagementController from './IdentityManagementController'
import RoleBookmarkController from './RoleBookmarkController'

const V1 = {
    IdentityController: Object.assign(IdentityController, IdentityController),
    StaffAccessController: Object.assign(StaffAccessController, StaffAccessController),
    IdentityManagementController: Object.assign(IdentityManagementController, IdentityManagementController),
    RoleBookmarkController: Object.assign(RoleBookmarkController, RoleBookmarkController),
}

export default V1