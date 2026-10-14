import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:16
* @route '/api/v1/investor/holdings/{holding}'
*/
export const show = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/holdings/{holding}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:16
* @route '/api/v1/investor/holdings/{holding}'
*/
show.url = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{holding}', parsedArgs.holding.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:16
* @route '/api/v1/investor/holdings/{holding}'
*/
show.get = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:16
* @route '/api/v1/investor/holdings/{holding}'
*/
show.head = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:16
* @route '/api/v1/investor/holdings/{holding}'
*/
const showForm = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:16
* @route '/api/v1/investor/holdings/{holding}'
*/
showForm.get = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorHoldingController::show
* @see app/Http/Controllers/InvestorHoldingController.php:16
* @route '/api/v1/investor/holdings/{holding}'
*/
showForm.head = (args: { holding: string | number } | [holding: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const holdings = {
    show: Object.assign(show, show),
}

export default holdings