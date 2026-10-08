import disbursements from './disbursements'
import audit from './audit'
import applications from './applications'
import investors from './investors'
import businesses from './businesses'
import auditors from './auditors'
import investorVerifications from './investor-verifications'

const staff = {
    disbursements: Object.assign(disbursements, disbursements),
    audit: Object.assign(audit, audit),
    applications: Object.assign(applications, applications),
    investors: Object.assign(investors, investors),
    businesses: Object.assign(businesses, businesses),
    auditors: Object.assign(auditors, auditors),
    investorVerifications: Object.assign(investorVerifications, investorVerifications),
}

export default staff