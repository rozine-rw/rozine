import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
const quote52b3d8afd976b8d571d14ecf47312a1d = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: quote52b3d8afd976b8d571d14ecf47312a1d.url(options),
    method: 'post',
})

quote52b3d8afd976b8d571d14ecf47312a1d.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/quote',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
quote52b3d8afd976b8d571d14ecf47312a1d.url = (options?: RouteQueryOptions) => {
    return quote52b3d8afd976b8d571d14ecf47312a1d.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
quote52b3d8afd976b8d571d14ecf47312a1d.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: quote52b3d8afd976b8d571d14ecf47312a1d.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
const quote52b3d8afd976b8d571d14ecf47312a1dForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: quote52b3d8afd976b8d571d14ecf47312a1d.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
quote52b3d8afd976b8d571d14ecf47312a1dForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: quote52b3d8afd976b8d571d14ecf47312a1d.url(options),
    method: 'post',
})

quote52b3d8afd976b8d571d14ecf47312a1d.form = quote52b3d8afd976b8d571d14ecf47312a1dForm
/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/investor/cart/quote'
*/
const quote1eccdee0f7849828442f92629330ed02 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: quote1eccdee0f7849828442f92629330ed02.url(options),
    method: 'post',
})

quote1eccdee0f7849828442f92629330ed02.definition = {
    methods: ["post"],
    url: '/investor/cart/quote',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/investor/cart/quote'
*/
quote1eccdee0f7849828442f92629330ed02.url = (options?: RouteQueryOptions) => {
    return quote1eccdee0f7849828442f92629330ed02.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/investor/cart/quote'
*/
quote1eccdee0f7849828442f92629330ed02.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: quote1eccdee0f7849828442f92629330ed02.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/investor/cart/quote'
*/
const quote1eccdee0f7849828442f92629330ed02Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: quote1eccdee0f7849828442f92629330ed02.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/investor/cart/quote'
*/
quote1eccdee0f7849828442f92629330ed02Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: quote1eccdee0f7849828442f92629330ed02.url(options),
    method: 'post',
})

quote1eccdee0f7849828442f92629330ed02.form = quote1eccdee0f7849828442f92629330ed02Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorCartCheckoutController::quote, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `quote['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const quote = {
    '/api/v1/investor/cart/quote': quote52b3d8afd976b8d571d14ecf47312a1d,
    '/investor/cart/quote': quote1eccdee0f7849828442f92629330ed02,
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
const reserve069414f3b8b5cdf3f6b4e2265d8dc382 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve069414f3b8b5cdf3f6b4e2265d8dc382.url(options),
    method: 'post',
})

reserve069414f3b8b5cdf3f6b4e2265d8dc382.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/reservations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
reserve069414f3b8b5cdf3f6b4e2265d8dc382.url = (options?: RouteQueryOptions) => {
    return reserve069414f3b8b5cdf3f6b4e2265d8dc382.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
reserve069414f3b8b5cdf3f6b4e2265d8dc382.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve069414f3b8b5cdf3f6b4e2265d8dc382.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
const reserve069414f3b8b5cdf3f6b4e2265d8dc382Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve069414f3b8b5cdf3f6b4e2265d8dc382.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
reserve069414f3b8b5cdf3f6b4e2265d8dc382Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve069414f3b8b5cdf3f6b4e2265d8dc382.url(options),
    method: 'post',
})

reserve069414f3b8b5cdf3f6b4e2265d8dc382.form = reserve069414f3b8b5cdf3f6b4e2265d8dc382Form
/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/investor/cart/reservations'
*/
const reservedbb5a073138f66a6ae27763946da0c27 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reservedbb5a073138f66a6ae27763946da0c27.url(options),
    method: 'post',
})

reservedbb5a073138f66a6ae27763946da0c27.definition = {
    methods: ["post"],
    url: '/investor/cart/reservations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/investor/cart/reservations'
*/
reservedbb5a073138f66a6ae27763946da0c27.url = (options?: RouteQueryOptions) => {
    return reservedbb5a073138f66a6ae27763946da0c27.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/investor/cart/reservations'
*/
reservedbb5a073138f66a6ae27763946da0c27.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reservedbb5a073138f66a6ae27763946da0c27.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/investor/cart/reservations'
*/
const reservedbb5a073138f66a6ae27763946da0c27Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reservedbb5a073138f66a6ae27763946da0c27.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/investor/cart/reservations'
*/
reservedbb5a073138f66a6ae27763946da0c27Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reservedbb5a073138f66a6ae27763946da0c27.url(options),
    method: 'post',
})

reservedbb5a073138f66a6ae27763946da0c27.form = reservedbb5a073138f66a6ae27763946da0c27Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorCartCheckoutController::reserve, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `reserve['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const reserve = {
    '/api/v1/investor/cart/reservations': reserve069414f3b8b5cdf3f6b4e2265d8dc382,
    '/investor/cart/reservations': reservedbb5a073138f66a6ae27763946da0c27,
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
const reservation7f1a0fcb88612df9ea062fe7a97c6806 = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reservation7f1a0fcb88612df9ea062fe7a97c6806.url(args, options),
    method: 'get',
})

reservation7f1a0fcb88612df9ea062fe7a97c6806.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/cart/reservations/{checkout}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
reservation7f1a0fcb88612df9ea062fe7a97c6806.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return reservation7f1a0fcb88612df9ea062fe7a97c6806.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
reservation7f1a0fcb88612df9ea062fe7a97c6806.get = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reservation7f1a0fcb88612df9ea062fe7a97c6806.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
reservation7f1a0fcb88612df9ea062fe7a97c6806.head = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reservation7f1a0fcb88612df9ea062fe7a97c6806.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
const reservation7f1a0fcb88612df9ea062fe7a97c6806Form = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reservation7f1a0fcb88612df9ea062fe7a97c6806.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
reservation7f1a0fcb88612df9ea062fe7a97c6806Form.get = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reservation7f1a0fcb88612df9ea062fe7a97c6806.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
reservation7f1a0fcb88612df9ea062fe7a97c6806Form.head = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reservation7f1a0fcb88612df9ea062fe7a97c6806.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

reservation7f1a0fcb88612df9ea062fe7a97c6806.form = reservation7f1a0fcb88612df9ea062fe7a97c6806Form
/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/investor/cart/reservations/{checkout}'
*/
const reservationc1e0b740c15eff860d3ce0d5ead3e224 = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reservationc1e0b740c15eff860d3ce0d5ead3e224.url(args, options),
    method: 'get',
})

reservationc1e0b740c15eff860d3ce0d5ead3e224.definition = {
    methods: ["get","head"],
    url: '/investor/cart/reservations/{checkout}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/investor/cart/reservations/{checkout}'
*/
reservationc1e0b740c15eff860d3ce0d5ead3e224.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return reservationc1e0b740c15eff860d3ce0d5ead3e224.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/investor/cart/reservations/{checkout}'
*/
reservationc1e0b740c15eff860d3ce0d5ead3e224.get = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reservationc1e0b740c15eff860d3ce0d5ead3e224.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/investor/cart/reservations/{checkout}'
*/
reservationc1e0b740c15eff860d3ce0d5ead3e224.head = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reservationc1e0b740c15eff860d3ce0d5ead3e224.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/investor/cart/reservations/{checkout}'
*/
const reservationc1e0b740c15eff860d3ce0d5ead3e224Form = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reservationc1e0b740c15eff860d3ce0d5ead3e224.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/investor/cart/reservations/{checkout}'
*/
reservationc1e0b740c15eff860d3ce0d5ead3e224Form.get = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reservationc1e0b740c15eff860d3ce0d5ead3e224.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/investor/cart/reservations/{checkout}'
*/
reservationc1e0b740c15eff860d3ce0d5ead3e224Form.head = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reservationc1e0b740c15eff860d3ce0d5ead3e224.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

reservationc1e0b740c15eff860d3ce0d5ead3e224.form = reservationc1e0b740c15eff860d3ce0d5ead3e224Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorCartCheckoutController::reservation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `reservation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const reservation = {
    '/api/v1/investor/cart/reservations/{checkout}': reservation7f1a0fcb88612df9ea062fe7a97c6806,
    '/investor/cart/reservations/{checkout}': reservationc1e0b740c15eff860d3ce0d5ead3e224,
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
const confirm4110190e11e52e5c66853629043c953e = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm4110190e11e52e5c66853629043c953e.url(args, options),
    method: 'post',
})

confirm4110190e11e52e5c66853629043c953e.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/reservations/{checkout}/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
confirm4110190e11e52e5c66853629043c953e.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return confirm4110190e11e52e5c66853629043c953e.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
confirm4110190e11e52e5c66853629043c953e.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm4110190e11e52e5c66853629043c953e.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
const confirm4110190e11e52e5c66853629043c953eForm = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm4110190e11e52e5c66853629043c953e.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
confirm4110190e11e52e5c66853629043c953eForm.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm4110190e11e52e5c66853629043c953e.url(args, options),
    method: 'post',
})

confirm4110190e11e52e5c66853629043c953e.form = confirm4110190e11e52e5c66853629043c953eForm
/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/investor/cart/reservations/{checkout}/confirm'
*/
const confirm83f8c8f2f013743ddb8b26fddece14aa = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm83f8c8f2f013743ddb8b26fddece14aa.url(args, options),
    method: 'post',
})

confirm83f8c8f2f013743ddb8b26fddece14aa.definition = {
    methods: ["post"],
    url: '/investor/cart/reservations/{checkout}/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/investor/cart/reservations/{checkout}/confirm'
*/
confirm83f8c8f2f013743ddb8b26fddece14aa.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return confirm83f8c8f2f013743ddb8b26fddece14aa.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/investor/cart/reservations/{checkout}/confirm'
*/
confirm83f8c8f2f013743ddb8b26fddece14aa.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm83f8c8f2f013743ddb8b26fddece14aa.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/investor/cart/reservations/{checkout}/confirm'
*/
const confirm83f8c8f2f013743ddb8b26fddece14aaForm = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm83f8c8f2f013743ddb8b26fddece14aa.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/investor/cart/reservations/{checkout}/confirm'
*/
confirm83f8c8f2f013743ddb8b26fddece14aaForm.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm83f8c8f2f013743ddb8b26fddece14aa.url(args, options),
    method: 'post',
})

confirm83f8c8f2f013743ddb8b26fddece14aa.form = confirm83f8c8f2f013743ddb8b26fddece14aaForm

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorCartCheckoutController::confirm, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `confirm['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const confirm = {
    '/api/v1/investor/cart/reservations/{checkout}/confirm': confirm4110190e11e52e5c66853629043c953e,
    '/investor/cart/reservations/{checkout}/confirm': confirm83f8c8f2f013743ddb8b26fddece14aa,
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
const release8e16ed145fccaf8957a1f11b20db3a96 = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release8e16ed145fccaf8957a1f11b20db3a96.url(args, options),
    method: 'post',
})

release8e16ed145fccaf8957a1f11b20db3a96.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/reservations/{checkout}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
release8e16ed145fccaf8957a1f11b20db3a96.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return release8e16ed145fccaf8957a1f11b20db3a96.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
release8e16ed145fccaf8957a1f11b20db3a96.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release8e16ed145fccaf8957a1f11b20db3a96.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
const release8e16ed145fccaf8957a1f11b20db3a96Form = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release8e16ed145fccaf8957a1f11b20db3a96.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
release8e16ed145fccaf8957a1f11b20db3a96Form.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release8e16ed145fccaf8957a1f11b20db3a96.url(args, options),
    method: 'post',
})

release8e16ed145fccaf8957a1f11b20db3a96.form = release8e16ed145fccaf8957a1f11b20db3a96Form
/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/investor/cart/reservations/{checkout}/release'
*/
const releasea4672044ccec99f406705a2b99c1abaf = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: releasea4672044ccec99f406705a2b99c1abaf.url(args, options),
    method: 'post',
})

releasea4672044ccec99f406705a2b99c1abaf.definition = {
    methods: ["post"],
    url: '/investor/cart/reservations/{checkout}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/investor/cart/reservations/{checkout}/release'
*/
releasea4672044ccec99f406705a2b99c1abaf.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return releasea4672044ccec99f406705a2b99c1abaf.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/investor/cart/reservations/{checkout}/release'
*/
releasea4672044ccec99f406705a2b99c1abaf.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: releasea4672044ccec99f406705a2b99c1abaf.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/investor/cart/reservations/{checkout}/release'
*/
const releasea4672044ccec99f406705a2b99c1abafForm = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: releasea4672044ccec99f406705a2b99c1abaf.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/investor/cart/reservations/{checkout}/release'
*/
releasea4672044ccec99f406705a2b99c1abafForm.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: releasea4672044ccec99f406705a2b99c1abaf.url(args, options),
    method: 'post',
})

releasea4672044ccec99f406705a2b99c1abaf.form = releasea4672044ccec99f406705a2b99c1abafForm

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorCartCheckoutController::release, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `release['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const release = {
    '/api/v1/investor/cart/reservations/{checkout}/release': release8e16ed145fccaf8957a1f11b20db3a96,
    '/investor/cart/reservations/{checkout}/release': releasea4672044ccec99f406705a2b99c1abaf,
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
const operation74a80bfb9f86421c9b6f2974c1f6e944 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation74a80bfb9f86421c9b6f2974c1f6e944.url(args, options),
    method: 'get',
})

operation74a80bfb9f86421c9b6f2974c1f6e944.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/cart/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
operation74a80bfb9f86421c9b6f2974c1f6e944.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { request_id: args }
    }

    if (Array.isArray(args)) {
        args = {
            request_id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        request_id: args.request_id,
    }

    return operation74a80bfb9f86421c9b6f2974c1f6e944.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
operation74a80bfb9f86421c9b6f2974c1f6e944.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation74a80bfb9f86421c9b6f2974c1f6e944.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
operation74a80bfb9f86421c9b6f2974c1f6e944.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation74a80bfb9f86421c9b6f2974c1f6e944.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
const operation74a80bfb9f86421c9b6f2974c1f6e944Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation74a80bfb9f86421c9b6f2974c1f6e944.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
operation74a80bfb9f86421c9b6f2974c1f6e944Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation74a80bfb9f86421c9b6f2974c1f6e944.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
operation74a80bfb9f86421c9b6f2974c1f6e944Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation74a80bfb9f86421c9b6f2974c1f6e944.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation74a80bfb9f86421c9b6f2974c1f6e944.form = operation74a80bfb9f86421c9b6f2974c1f6e944Form
/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/investor/cart/operations/{request_id}'
*/
const operation13ccae7423910cbde110feda4a8f45c2 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation13ccae7423910cbde110feda4a8f45c2.url(args, options),
    method: 'get',
})

operation13ccae7423910cbde110feda4a8f45c2.definition = {
    methods: ["get","head"],
    url: '/investor/cart/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/investor/cart/operations/{request_id}'
*/
operation13ccae7423910cbde110feda4a8f45c2.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { request_id: args }
    }

    if (Array.isArray(args)) {
        args = {
            request_id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        request_id: args.request_id,
    }

    return operation13ccae7423910cbde110feda4a8f45c2.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/investor/cart/operations/{request_id}'
*/
operation13ccae7423910cbde110feda4a8f45c2.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation13ccae7423910cbde110feda4a8f45c2.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/investor/cart/operations/{request_id}'
*/
operation13ccae7423910cbde110feda4a8f45c2.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation13ccae7423910cbde110feda4a8f45c2.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/investor/cart/operations/{request_id}'
*/
const operation13ccae7423910cbde110feda4a8f45c2Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation13ccae7423910cbde110feda4a8f45c2.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/investor/cart/operations/{request_id}'
*/
operation13ccae7423910cbde110feda4a8f45c2Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation13ccae7423910cbde110feda4a8f45c2.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/investor/cart/operations/{request_id}'
*/
operation13ccae7423910cbde110feda4a8f45c2Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation13ccae7423910cbde110feda4a8f45c2.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation13ccae7423910cbde110feda4a8f45c2.form = operation13ccae7423910cbde110feda4a8f45c2Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorCartCheckoutController::operation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `operation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const operation = {
    '/api/v1/investor/cart/operations/{request_id}': operation74a80bfb9f86421c9b6f2974c1f6e944,
    '/investor/cart/operations/{request_id}': operation13ccae7423910cbde110feda4a8f45c2,
}

const InvestorCartCheckoutController = { quote, reserve, reservation, confirm, release, operation }

export default InvestorCartCheckoutController