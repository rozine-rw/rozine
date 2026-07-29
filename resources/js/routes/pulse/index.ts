import investor from './investor';
import business from './business';

const pulse = {
    investor: Object.assign(investor, investor),
    business: Object.assign(business, business),
};

export default pulse;
