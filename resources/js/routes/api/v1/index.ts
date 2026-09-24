import identity from './identity'
import staffAccess from './staff-access'
import auditor from './auditor'

const v1 = {
    identity: Object.assign(identity, identity),
    staffAccess: Object.assign(staffAccess, staffAccess),
    auditor: Object.assign(auditor, auditor),
}

export default v1