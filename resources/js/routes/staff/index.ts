import audit from './audit'
import disbursements from './disbursements'
import applications from './applications'
import ledger from './ledger'
import changes from './changes'

const staff = {
    audit: Object.assign(audit, audit),
    disbursements: Object.assign(disbursements, disbursements),
    applications: Object.assign(applications, applications),
    ledger: Object.assign(ledger, ledger),
    changes: Object.assign(changes, changes),
}

export default staff