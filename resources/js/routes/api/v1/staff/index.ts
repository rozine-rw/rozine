import ledger from './ledger'
import disbursements from './disbursements'
import audit from './audit'
import applications from './applications'

const staff = {
    ledger: Object.assign(ledger, ledger),
    disbursements: Object.assign(disbursements, disbursements),
    audit: Object.assign(audit, audit),
    applications: Object.assign(applications, applications),
}

export default staff