import audit from './audit'
import disbursements from './disbursements'
import applications from './applications'
import sections from './sections'
import investors from './investors'
import investorVerifications from './investor-verifications'
import stagingMailTesters from './staging-mail-testers'
import changes from './changes'

const staff = {
    audit: Object.assign(audit, audit),
    disbursements: Object.assign(disbursements, disbursements),
    applications: Object.assign(applications, applications),
    sections: Object.assign(sections, sections),
    investors: Object.assign(investors, investors),
    investorVerifications: Object.assign(investorVerifications, investorVerifications),
    stagingMailTesters: Object.assign(stagingMailTesters, stagingMailTesters),
    changes: Object.assign(changes, changes),
}

export default staff