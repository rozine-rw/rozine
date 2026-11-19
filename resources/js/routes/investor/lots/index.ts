import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorLotController::show
* @see app/Http/Controllers/InvestorLotController.php:18
* @route '/investor/lots/{lot}'
*/
export const show = (args: { lot: string | number } | [lot: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/investor/lots/{lot}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorLotController::show
* @see app/Http/Controllers/InvestorLotController.php:18
* @route '/investor/lots/{lot}'
*/
show.url = (args: { lot: string | number } | [lot: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { lot: args }
    }

    if (Array.isArray(args)) {
        args = {
            lot: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        lot: args.lot,
    }

    return show.definition.url
            .replace('{lot}', parsedArgs.lot.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorLotController::show
* @see app/Http/Controllers/InvestorLotController.php:18
* @route '/investor/lots/{lot}'
*/
show.get = (args: { lot: string | number } | [lot: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorLotController::show
* @see app/Http/Controllers/InvestorLotController.php:18
* @route '/investor/lots/{lot}'
*/
show.head = (args: { lot: string | number } | [lot: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorLotController::show
* @see app/Http/Controllers/InvestorLotController.php:18
* @route '/investor/lots/{lot}'
*/
const showForm = (args: { lot: string | number } | [lot: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorLotController::show
* @see app/Http/Controllers/InvestorLotController.php:18
* @route '/investor/lots/{lot}'
*/
showForm.get = (args: { lot: string | number } | [lot: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorLotController::show
* @see app/Http/Controllers/InvestorLotController.php:18
* @route '/investor/lots/{lot}'
*/
showForm.head = (args: { lot: string | number } | [lot: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const lots = {
    show: Object.assign(show, show),
}

export default lots