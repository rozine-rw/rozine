import audit from './audit'
import applications from './applications'
import changes from './changes'

const staff = {
    audit: Object.assign(audit, audit),
    applications: Object.assign(applications, applications),
    changes: Object.assign(changes, changes),
}

export default staff