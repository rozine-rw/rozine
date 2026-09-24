import identity from './identity'
import staffAccess from './staff-access'
import staff from './staff'
import auditor from './auditor'

const v1 = {
    identity: Object.assign(identity, identity),
    staffAccess: Object.assign(staffAccess, staffAccess),
    staff: Object.assign(staff, staff),
    auditor: Object.assign(auditor, auditor),
}

export default v1