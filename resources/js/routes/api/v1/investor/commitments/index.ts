import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorPrimaryController::show
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
export const show = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/commitments/{commitment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::show
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
show.url = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { commitment: args }
    }

    if (Array.isArray(args)) {
        args = {
            commitment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        commitment: args.commitment,
    }

    return show.definition.url
            .replace('{commitment}', parsedArgs.commitment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::show
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
show.get = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::show
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
show.head = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::show
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
const showForm = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::show
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
showForm.get = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::show
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
showForm.head = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const commitments = {
    show: Object.assign(show, show),
}

export default commitments