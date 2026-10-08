import disbursements from './disbursements'
import audit from './audit'
import applications from './applications'
import investors from './investors'
import investorVerifications from './investor-verifications'

const staff = {
    disbursements: Object.assign(disbursements, disbursements),
    audit: Object.assign(audit, audit),
    applications: Object.assign(applications, applications),
    investors: Object.assign(investors, investors),
    investorVerifications: Object.assign(investorVerifications, investorVerifications),
}

export default staff