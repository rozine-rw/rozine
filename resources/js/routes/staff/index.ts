import audit from './audit'
import disbursements from './disbursements'
import applications from './applications'
import investorVerifications from './investor-verifications'
import changes from './changes'

const staff = {
    audit: Object.assign(audit, audit),
    disbursements: Object.assign(disbursements, disbursements),
    applications: Object.assign(applications, applications),
    investorVerifications: Object.assign(investorVerifications, investorVerifications),
    changes: Object.assign(changes, changes),
}

export default staff