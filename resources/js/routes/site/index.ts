import investor from './investor'
import business from './business'

const site = {
    investor: Object.assign(investor, investor),
    business: Object.assign(business, business),
}

export default site