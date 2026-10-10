import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:40
* @route '/investor/verified'
*/
export const verified = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verified.url(options),
    method: 'get',
})

verified.definition = {
    methods: ["get","head"],
    url: '/investor/verified',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:40
* @route '/investor/verified'
*/
verified.url = (options?: RouteQueryOptions) => {
    return verified.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:40
* @route '/investor/verified'
*/
verified.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verified.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:40
* @route '/investor/verified'
*/
verified.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: verified.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:40
* @route '/investor/verified'
*/
const verifiedForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verified.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:40
* @route '/investor/verified'
*/
verifiedForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verified.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:40
* @route '/investor/verified'
*/
verifiedForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verified.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

verified.form = verifiedForm

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:26
* @route '/investor/profile'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/investor/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:26
* @route '/investor/profile'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:26
* @route '/investor/profile'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:26
* @route '/investor/profile'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:26
* @route '/investor/profile'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:26
* @route '/investor/profile'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:26
* @route '/investor/profile'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const InvestorProfileController = { verified, show }

export default InvestorProfileController