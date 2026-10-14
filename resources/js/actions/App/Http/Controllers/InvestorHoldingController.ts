import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/api/v1/investor/holdings/{holding}'
*/
const show2d90da9506fecf802ca14320d286aee7 = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show2d90da9506fecf802ca14320d286aee7.url(args, options),
    method: 'get',
})

show2d90da9506fecf802ca14320d286aee7.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/holdings/{holding}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/api/v1/investor/holdings/{holding}'
*/
show2d90da9506fecf802ca14320d286aee7.url = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { holding: args }
    }

    if (Array.isArray(args)) {
        args = {
            holding: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        holding: args.holding,
    }

    return show2d90da9506fecf802ca14320d286aee7.definition.url
            .replace('{holding}', parsedArgs.holding.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/api/v1/investor/holdings/{holding}'
*/
show2d90da9506fecf802ca14320d286aee7.get = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show2d90da9506fecf802ca14320d286aee7.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/api/v1/investor/holdings/{holding}'
*/
show2d90da9506fecf802ca14320d286aee7.head = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show2d90da9506fecf802ca14320d286aee7.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/api/v1/investor/holdings/{holding}'
*/
const show2d90da9506fecf802ca14320d286aee7Form = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show2d90da9506fecf802ca14320d286aee7.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/api/v1/investor/holdings/{holding}'
*/
show2d90da9506fecf802ca14320d286aee7Form.get = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show2d90da9506fecf802ca14320d286aee7.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/api/v1/investor/holdings/{holding}'
*/
show2d90da9506fecf802ca14320d286aee7Form.head = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show2d90da9506fecf802ca14320d286aee7.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show2d90da9506fecf802ca14320d286aee7.form = show2d90da9506fecf802ca14320d286aee7Form
/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/investor/holdings/{holding}'
*/
const show75afaa693aeab7486eaf61bf84b20f60 = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show75afaa693aeab7486eaf61bf84b20f60.url(args, options),
    method: 'get',
})

show75afaa693aeab7486eaf61bf84b20f60.definition = {
    methods: ["get","head"],
    url: '/investor/holdings/{holding}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/investor/holdings/{holding}'
*/
show75afaa693aeab7486eaf61bf84b20f60.url = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { holding: args }
    }

    if (Array.isArray(args)) {
        args = {
            holding: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        holding: args.holding,
    }

    return show75afaa693aeab7486eaf61bf84b20f60.definition.url
            .replace('{holding}', parsedArgs.holding.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/investor/holdings/{holding}'
*/
show75afaa693aeab7486eaf61bf84b20f60.get = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show75afaa693aeab7486eaf61bf84b20f60.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/investor/holdings/{holding}'
*/
show75afaa693aeab7486eaf61bf84b20f60.head = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show75afaa693aeab7486eaf61bf84b20f60.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/investor/holdings/{holding}'
*/
const show75afaa693aeab7486eaf61bf84b20f60Form = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show75afaa693aeab7486eaf61bf84b20f60.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/investor/holdings/{holding}'
*/
show75afaa693aeab7486eaf61bf84b20f60Form.get = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show75afaa693aeab7486eaf61bf84b20f60.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:18
* @route '/investor/holdings/{holding}'
*/
show75afaa693aeab7486eaf61bf84b20f60Form.head = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show75afaa693aeab7486eaf61bf84b20f60.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show75afaa693aeab7486eaf61bf84b20f60.form = show75afaa693aeab7486eaf61bf84b20f60Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorHoldingController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/investor/holdings/{holding}': show2d90da9506fecf802ca14320d286aee7,
    '/investor/holdings/{holding}': show75afaa693aeab7486eaf61bf84b20f60,
}

const InvestorHoldingController = { show }

export default InvestorHoldingController