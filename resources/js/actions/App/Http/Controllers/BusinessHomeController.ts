import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults, validateParameters } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/api/v1/business/{business}'
*/
const show740a1695eb98b7bf1b75e02eb6012df1 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show740a1695eb98b7bf1b75e02eb6012df1.url(args, options),
    method: 'get',
})

show740a1695eb98b7bf1b75e02eb6012df1.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/api/v1/business/{business}'
*/
show740a1695eb98b7bf1b75e02eb6012df1.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return show740a1695eb98b7bf1b75e02eb6012df1.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/api/v1/business/{business}'
*/
show740a1695eb98b7bf1b75e02eb6012df1.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show740a1695eb98b7bf1b75e02eb6012df1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/api/v1/business/{business}'
*/
show740a1695eb98b7bf1b75e02eb6012df1.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show740a1695eb98b7bf1b75e02eb6012df1.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/api/v1/business/{business}'
*/
const show740a1695eb98b7bf1b75e02eb6012df1Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show740a1695eb98b7bf1b75e02eb6012df1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/api/v1/business/{business}'
*/
show740a1695eb98b7bf1b75e02eb6012df1Form.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show740a1695eb98b7bf1b75e02eb6012df1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/api/v1/business/{business}'
*/
show740a1695eb98b7bf1b75e02eb6012df1Form.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show740a1695eb98b7bf1b75e02eb6012df1.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show740a1695eb98b7bf1b75e02eb6012df1.form = show740a1695eb98b7bf1b75e02eb6012df1Form
/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/business/{business}'
*/
const showf2bed257a86e67ae119e02ff274e3020 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showf2bed257a86e67ae119e02ff274e3020.url(args, options),
    method: 'get',
})

showf2bed257a86e67ae119e02ff274e3020.definition = {
    methods: ["get","head"],
    url: '/business/{business}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/business/{business}'
*/
showf2bed257a86e67ae119e02ff274e3020.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return showf2bed257a86e67ae119e02ff274e3020.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/business/{business}'
*/
showf2bed257a86e67ae119e02ff274e3020.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showf2bed257a86e67ae119e02ff274e3020.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/business/{business}'
*/
showf2bed257a86e67ae119e02ff274e3020.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showf2bed257a86e67ae119e02ff274e3020.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/business/{business}'
*/
const showf2bed257a86e67ae119e02ff274e3020Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showf2bed257a86e67ae119e02ff274e3020.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/business/{business}'
*/
showf2bed257a86e67ae119e02ff274e3020Form.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showf2bed257a86e67ae119e02ff274e3020.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:30
* @route '/business/{business}'
*/
showf2bed257a86e67ae119e02ff274e3020Form.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showf2bed257a86e67ae119e02ff274e3020.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showf2bed257a86e67ae119e02ff274e3020.form = showf2bed257a86e67ae119e02ff274e3020Form

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessHomeController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/business/{business}': show740a1695eb98b7bf1b75e02eb6012df1,
    '/business/{business}': showf2bed257a86e67ae119e02ff274e3020,
}

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:44
* @route '/business/{business}/reports'
*/
export const reports = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reports.url(args, options),
    method: 'get',
})

reports.definition = {
    methods: ["get","head"],
    url: '/business/{business}/reports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:44
* @route '/business/{business}/reports'
*/
reports.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return reports.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:44
* @route '/business/{business}/reports'
*/
reports.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reports.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:44
* @route '/business/{business}/reports'
*/
reports.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reports.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:44
* @route '/business/{business}/reports'
*/
const reportsForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reports.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:44
* @route '/business/{business}/reports'
*/
reportsForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reports.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:44
* @route '/business/{business}/reports'
*/
reportsForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reports.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

reports.form = reportsForm

/**
* @see \App\Http\Controllers\BusinessHomeController::market
* @see app/Http/Controllers/BusinessHomeController.php:51
* @route '/business/{business}/market'
*/
export const market = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: market.url(args, options),
    method: 'get',
})

market.definition = {
    methods: ["get","head"],
    url: '/business/{business}/market',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::market
* @see app/Http/Controllers/BusinessHomeController.php:51
* @route '/business/{business}/market'
*/
market.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return market.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::market
* @see app/Http/Controllers/BusinessHomeController.php:51
* @route '/business/{business}/market'
*/
market.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: market.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::market
* @see app/Http/Controllers/BusinessHomeController.php:51
* @route '/business/{business}/market'
*/
market.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: market.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::market
* @see app/Http/Controllers/BusinessHomeController.php:51
* @route '/business/{business}/market'
*/
const marketForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: market.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::market
* @see app/Http/Controllers/BusinessHomeController.php:51
* @route '/business/{business}/market'
*/
marketForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: market.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::market
* @see app/Http/Controllers/BusinessHomeController.php:51
* @route '/business/{business}/market'
*/
marketForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: market.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

market.form = marketForm

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:62
* @route '/business/{business}/profile/{section?}'
*/
export const profile = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(args, options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/business/{business}/profile/{section?}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:62
* @route '/business/{business}/profile/{section?}'
*/
profile.url = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            section: args[1],
        }
    }

    args = applyUrlDefaults(args)

    validateParameters(args, [
        "section",
    ])

    const parsedArgs = {
        business: args.business,
        section: args.section,
    }

    return profile.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{section?}', parsedArgs.section?.toString() ?? '')
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:62
* @route '/business/{business}/profile/{section?}'
*/
profile.get = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:62
* @route '/business/{business}/profile/{section?}'
*/
profile.head = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:62
* @route '/business/{business}/profile/{section?}'
*/
const profileForm = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:62
* @route '/business/{business}/profile/{section?}'
*/
profileForm.get = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:62
* @route '/business/{business}/profile/{section?}'
*/
profileForm.head = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

profile.form = profileForm

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:37
* @route '/business/{business}/rating'
*/
export const rating = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rating.url(args, options),
    method: 'get',
})

rating.definition = {
    methods: ["get","head"],
    url: '/business/{business}/rating',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:37
* @route '/business/{business}/rating'
*/
rating.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return rating.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:37
* @route '/business/{business}/rating'
*/
rating.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rating.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:37
* @route '/business/{business}/rating'
*/
rating.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: rating.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:37
* @route '/business/{business}/rating'
*/
const ratingForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rating.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:37
* @route '/business/{business}/rating'
*/
ratingForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rating.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:37
* @route '/business/{business}/rating'
*/
ratingForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rating.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

rating.form = ratingForm

const BusinessHomeController = { show, reports, market, profile, rating }

export default BusinessHomeController