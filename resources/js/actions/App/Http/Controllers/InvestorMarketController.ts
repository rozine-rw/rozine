import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
const addToCart7ffb691a6e3954429d565076599908ae = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addToCart7ffb691a6e3954429d565076599908ae.url(args, options),
    method: 'post',
})

addToCart7ffb691a6e3954429d565076599908ae.definition = {
    methods: ["post"],
    url: '/api/v1/investor/deals/{campaign}/cart',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
addToCart7ffb691a6e3954429d565076599908ae.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return addToCart7ffb691a6e3954429d565076599908ae.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
addToCart7ffb691a6e3954429d565076599908ae.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addToCart7ffb691a6e3954429d565076599908ae.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
const addToCart7ffb691a6e3954429d565076599908aeForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addToCart7ffb691a6e3954429d565076599908ae.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
addToCart7ffb691a6e3954429d565076599908aeForm.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addToCart7ffb691a6e3954429d565076599908ae.url(args, options),
    method: 'post',
})

addToCart7ffb691a6e3954429d565076599908ae.form = addToCart7ffb691a6e3954429d565076599908aeForm
/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/investor/deals/{campaign}/cart'
*/
const addToCarta591d9d7698b4173e78bc50779792ca2 = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addToCarta591d9d7698b4173e78bc50779792ca2.url(args, options),
    method: 'post',
})

addToCarta591d9d7698b4173e78bc50779792ca2.definition = {
    methods: ["post"],
    url: '/investor/deals/{campaign}/cart',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/investor/deals/{campaign}/cart'
*/
addToCarta591d9d7698b4173e78bc50779792ca2.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return addToCarta591d9d7698b4173e78bc50779792ca2.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/investor/deals/{campaign}/cart'
*/
addToCarta591d9d7698b4173e78bc50779792ca2.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addToCarta591d9d7698b4173e78bc50779792ca2.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/investor/deals/{campaign}/cart'
*/
const addToCarta591d9d7698b4173e78bc50779792ca2Form = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addToCarta591d9d7698b4173e78bc50779792ca2.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::addToCart
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/investor/deals/{campaign}/cart'
*/
addToCarta591d9d7698b4173e78bc50779792ca2Form.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addToCarta591d9d7698b4173e78bc50779792ca2.url(args, options),
    method: 'post',
})

addToCarta591d9d7698b4173e78bc50779792ca2.form = addToCarta591d9d7698b4173e78bc50779792ca2Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorMarketController::addToCart, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `addToCart['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const addToCart = {
    '/api/v1/investor/deals/{campaign}/cart': addToCart7ffb691a6e3954429d565076599908ae,
    '/investor/deals/{campaign}/cart': addToCarta591d9d7698b4173e78bc50779792ca2,
}

/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
const changeCartUnitsfe44831fad72acf819c6de44bb84eb1b = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: changeCartUnitsfe44831fad72acf819c6de44bb84eb1b.url(args, options),
    method: 'post',
})

changeCartUnitsfe44831fad72acf819c6de44bb84eb1b.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/{campaign}/units',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
changeCartUnitsfe44831fad72acf819c6de44bb84eb1b.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return changeCartUnitsfe44831fad72acf819c6de44bb84eb1b.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
changeCartUnitsfe44831fad72acf819c6de44bb84eb1b.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: changeCartUnitsfe44831fad72acf819c6de44bb84eb1b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
const changeCartUnitsfe44831fad72acf819c6de44bb84eb1bForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: changeCartUnitsfe44831fad72acf819c6de44bb84eb1b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
changeCartUnitsfe44831fad72acf819c6de44bb84eb1bForm.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: changeCartUnitsfe44831fad72acf819c6de44bb84eb1b.url(args, options),
    method: 'post',
})

changeCartUnitsfe44831fad72acf819c6de44bb84eb1b.form = changeCartUnitsfe44831fad72acf819c6de44bb84eb1bForm
/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/investor/cart/{campaign}/units'
*/
const changeCartUnits956cd287ff69d6185dd3e6d9beeae478 = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: changeCartUnits956cd287ff69d6185dd3e6d9beeae478.url(args, options),
    method: 'post',
})

changeCartUnits956cd287ff69d6185dd3e6d9beeae478.definition = {
    methods: ["post"],
    url: '/investor/cart/{campaign}/units',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/investor/cart/{campaign}/units'
*/
changeCartUnits956cd287ff69d6185dd3e6d9beeae478.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return changeCartUnits956cd287ff69d6185dd3e6d9beeae478.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/investor/cart/{campaign}/units'
*/
changeCartUnits956cd287ff69d6185dd3e6d9beeae478.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: changeCartUnits956cd287ff69d6185dd3e6d9beeae478.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/investor/cart/{campaign}/units'
*/
const changeCartUnits956cd287ff69d6185dd3e6d9beeae478Form = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: changeCartUnits956cd287ff69d6185dd3e6d9beeae478.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::changeCartUnits
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/investor/cart/{campaign}/units'
*/
changeCartUnits956cd287ff69d6185dd3e6d9beeae478Form.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: changeCartUnits956cd287ff69d6185dd3e6d9beeae478.url(args, options),
    method: 'post',
})

changeCartUnits956cd287ff69d6185dd3e6d9beeae478.form = changeCartUnits956cd287ff69d6185dd3e6d9beeae478Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorMarketController::changeCartUnits, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `changeCartUnits['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const changeCartUnits = {
    '/api/v1/investor/cart/{campaign}/units': changeCartUnitsfe44831fad72acf819c6de44bb84eb1b,
    '/investor/cart/{campaign}/units': changeCartUnits956cd287ff69d6185dd3e6d9beeae478,
}

/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
const removeFromCart596a0b4671fd4e09f7568364ac0219f6 = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeFromCart596a0b4671fd4e09f7568364ac0219f6.url(args, options),
    method: 'post',
})

removeFromCart596a0b4671fd4e09f7568364ac0219f6.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/{campaign}/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
removeFromCart596a0b4671fd4e09f7568364ac0219f6.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return removeFromCart596a0b4671fd4e09f7568364ac0219f6.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
removeFromCart596a0b4671fd4e09f7568364ac0219f6.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeFromCart596a0b4671fd4e09f7568364ac0219f6.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
const removeFromCart596a0b4671fd4e09f7568364ac0219f6Form = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeFromCart596a0b4671fd4e09f7568364ac0219f6.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
removeFromCart596a0b4671fd4e09f7568364ac0219f6Form.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeFromCart596a0b4671fd4e09f7568364ac0219f6.url(args, options),
    method: 'post',
})

removeFromCart596a0b4671fd4e09f7568364ac0219f6.form = removeFromCart596a0b4671fd4e09f7568364ac0219f6Form
/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/investor/cart/{campaign}/remove'
*/
const removeFromCartac33d23bb262496aff9354a88068eae3 = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeFromCartac33d23bb262496aff9354a88068eae3.url(args, options),
    method: 'post',
})

removeFromCartac33d23bb262496aff9354a88068eae3.definition = {
    methods: ["post"],
    url: '/investor/cart/{campaign}/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/investor/cart/{campaign}/remove'
*/
removeFromCartac33d23bb262496aff9354a88068eae3.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return removeFromCartac33d23bb262496aff9354a88068eae3.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/investor/cart/{campaign}/remove'
*/
removeFromCartac33d23bb262496aff9354a88068eae3.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeFromCartac33d23bb262496aff9354a88068eae3.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/investor/cart/{campaign}/remove'
*/
const removeFromCartac33d23bb262496aff9354a88068eae3Form = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeFromCartac33d23bb262496aff9354a88068eae3.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::removeFromCart
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/investor/cart/{campaign}/remove'
*/
removeFromCartac33d23bb262496aff9354a88068eae3Form.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeFromCartac33d23bb262496aff9354a88068eae3.url(args, options),
    method: 'post',
})

removeFromCartac33d23bb262496aff9354a88068eae3.form = removeFromCartac33d23bb262496aff9354a88068eae3Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorMarketController::removeFromCart, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `removeFromCart['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const removeFromCart = {
    '/api/v1/investor/cart/{campaign}/remove': removeFromCart596a0b4671fd4e09f7568364ac0219f6,
    '/investor/cart/{campaign}/remove': removeFromCartac33d23bb262496aff9354a88068eae3,
}

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:28
* @route '/investor/market'
*/
export const market = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: market.url(options),
    method: 'get',
})

market.definition = {
    methods: ["get","head"],
    url: '/investor/market',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:28
* @route '/investor/market'
*/
market.url = (options?: RouteQueryOptions) => {
    return market.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:28
* @route '/investor/market'
*/
market.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: market.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:28
* @route '/investor/market'
*/
market.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: market.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:28
* @route '/investor/market'
*/
const marketForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: market.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:28
* @route '/investor/market'
*/
marketForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: market.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:28
* @route '/investor/market'
*/
marketForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: market.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

market.form = marketForm

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:33
* @route '/investor/cart'
*/
export const cart = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart.url(options),
    method: 'get',
})

cart.definition = {
    methods: ["get","head"],
    url: '/investor/cart',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:33
* @route '/investor/cart'
*/
cart.url = (options?: RouteQueryOptions) => {
    return cart.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:33
* @route '/investor/cart'
*/
cart.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:33
* @route '/investor/cart'
*/
cart.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cart.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:33
* @route '/investor/cart'
*/
const cartForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:33
* @route '/investor/cart'
*/
cartForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:33
* @route '/investor/cart'
*/
cartForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

cart.form = cartForm

const InvestorMarketController = { addToCart, changeCartUnits, removeFromCart, market, cart }

export default InvestorMarketController