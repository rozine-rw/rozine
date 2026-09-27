import audit from './audit'
import applications from './applications'

const staff = {
    audit: Object.assign(audit, audit),
    applications: Object.assign(applications, applications),
}

export default staff