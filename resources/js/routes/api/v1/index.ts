import identity from './identity'
import staffAccess from './staff-access'

const v1 = {
    identity: Object.assign(identity, identity),
    staffAccess: Object.assign(staffAccess, staffAccess),
}

export default v1