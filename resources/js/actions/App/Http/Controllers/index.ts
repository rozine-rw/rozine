import SiteController from './SiteController'
import PulseController from './PulseController'
import Settings from './Settings'

const Controllers = {
    SiteController: Object.assign(SiteController, SiteController),
    PulseController: Object.assign(PulseController, PulseController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers