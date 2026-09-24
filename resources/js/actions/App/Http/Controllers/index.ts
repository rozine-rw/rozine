import Api from './Api'
import SiteController from './SiteController'
import PulseController from './PulseController'
import DashboardController from './DashboardController'
import RoleHomeController from './RoleHomeController'
import StaffHomeController from './StaffHomeController'
import IdentityManagementController from './IdentityManagementController'
import RoleBookmarkController from './RoleBookmarkController'
import Settings from './Settings'

const Controllers = {
    Api: Object.assign(Api, Api),
    SiteController: Object.assign(SiteController, SiteController),
    PulseController: Object.assign(PulseController, PulseController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    RoleHomeController: Object.assign(RoleHomeController, RoleHomeController),
    StaffHomeController: Object.assign(StaffHomeController, StaffHomeController),
    IdentityManagementController: Object.assign(IdentityManagementController, IdentityManagementController),
    RoleBookmarkController: Object.assign(RoleBookmarkController, RoleBookmarkController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers