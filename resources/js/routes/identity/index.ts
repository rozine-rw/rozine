import people from './people'
import memberships from './memberships'
import activeRole from './active-role'
import roles from './roles'

const identity = {
    people: Object.assign(people, people),
    memberships: Object.assign(memberships, memberships),
    activeRole: Object.assign(activeRole, activeRole),
    roles: Object.assign(roles, roles),
}

export default identity