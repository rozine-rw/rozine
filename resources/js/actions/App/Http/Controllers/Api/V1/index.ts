import IdentityController from './IdentityController'
import IdentityManagementController from './IdentityManagementController'

const V1 = {
    IdentityController: Object.assign(IdentityController, IdentityController),
    IdentityManagementController: Object.assign(IdentityManagementController, IdentityManagementController),
}

export default V1