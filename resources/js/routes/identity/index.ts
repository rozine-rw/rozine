import people from './people'
import staffPeople from './staff-people'
import memberships from './memberships'
import activeRole from './active-role'
import investorAccess from './investor-access'
import roles from './roles'
import bookmarks from './bookmarks'

const identity = {
    people: Object.assign(people, people),
    staffPeople: Object.assign(staffPeople, staffPeople),
    memberships: Object.assign(memberships, memberships),
    activeRole: Object.assign(activeRole, activeRole),
    investorAccess: Object.assign(investorAccess, investorAccess),
    roles: Object.assign(roles, roles),
    bookmarks: Object.assign(bookmarks, bookmarks),
}

export default identity