import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
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
* @see app/Http/Controllers/BusinessHomeController.php:16
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
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/api/v1/business/{business}'
*/
show740a1695eb98b7bf1b75e02eb6012df1.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show740a1695eb98b7bf1b75e02eb6012df1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/api/v1/business/{business}'
*/
show740a1695eb98b7bf1b75e02eb6012df1.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show740a1695eb98b7bf1b75e02eb6012df1.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/api/v1/business/{business}'
*/
const show740a1695eb98b7bf1b75e02eb6012df1Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show740a1695eb98b7bf1b75e02eb6012df1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/api/v1/business/{business}'
*/
show740a1695eb98b7bf1b75e02eb6012df1Form.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show740a1695eb98b7bf1b75e02eb6012df1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
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
* @see app/Http/Controllers/BusinessHomeController.php:16
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
* @see app/Http/Controllers/BusinessHomeController.php:16
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
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
*/
showf2bed257a86e67ae119e02ff274e3020.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showf2bed257a86e67ae119e02ff274e3020.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
*/
showf2bed257a86e67ae119e02ff274e3020.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showf2bed257a86e67ae119e02ff274e3020.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
*/
const showf2bed257a86e67ae119e02ff274e3020Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showf2bed257a86e67ae119e02ff274e3020.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
*/
showf2bed257a86e67ae119e02ff274e3020Form.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showf2bed257a86e67ae119e02ff274e3020.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
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

const BusinessHomeController = { show }

export default BusinessHomeController