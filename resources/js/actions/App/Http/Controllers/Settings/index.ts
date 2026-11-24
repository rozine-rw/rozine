import ProfileController from './ProfileController'
import AccountClosureController from './AccountClosureController'
import SecurityController from './SecurityController'

const Settings = {
    ProfileController: Object.assign(ProfileController, ProfileController),
    AccountClosureController: Object.assign(AccountClosureController, AccountClosureController),
    SecurityController: Object.assign(SecurityController, SecurityController),
}

export default Settings