import statement from './statement'
import investor from './investor'
import business from './business'

const pulse = {
    statement: Object.assign(statement, statement),
    investor: Object.assign(investor, investor),
    business: Object.assign(business, business),
}

export default pulse