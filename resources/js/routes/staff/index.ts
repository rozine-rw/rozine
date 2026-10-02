import audit from './audit'
import disbursements from './disbursements'
import applications from './applications'

const staff = {
    audit: Object.assign(audit, audit),
    disbursements: Object.assign(disbursements, disbursements),
    applications: Object.assign(applications, applications),
}

export default staff