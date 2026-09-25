import identity from './identity'
import business from './business'
import staffAccess from './staff-access'
import staff from './staff'
import auditor from './auditor'

const v1 = {
    identity: Object.assign(identity, identity),
    business: Object.assign(business, business),
    staffAccess: Object.assign(staffAccess, staffAccess),
    staff: Object.assign(staff, staff),
    auditor: Object.assign(auditor, auditor),
}

export default v1