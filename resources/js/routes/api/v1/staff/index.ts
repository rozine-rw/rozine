import disbursements from './disbursements'
import audit from './audit'
import applications from './applications'

const staff = {
    disbursements: Object.assign(disbursements, disbursements),
    audit: Object.assign(audit, audit),
    applications: Object.assign(applications, applications),
}

export default staff