import PulseController from './PulseController'
import Settings from './Settings'

const Controllers = {
    PulseController: Object.assign(PulseController, PulseController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers