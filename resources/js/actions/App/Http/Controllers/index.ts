import Api from './Api'
import SiteController from './SiteController'
import PulseController from './PulseController'
import DashboardController from './DashboardController'
import IdentityManagementController from './IdentityManagementController'
import Settings from './Settings'

const Controllers = {
    Api: Object.assign(Api, Api),
    SiteController: Object.assign(SiteController, SiteController),
    PulseController: Object.assign(PulseController, PulseController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    IdentityManagementController: Object.assign(IdentityManagementController, IdentityManagementController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers