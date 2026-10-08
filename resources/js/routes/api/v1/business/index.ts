import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
import auditReports from './audit-reports'
import repayments from './repayments'
import wallet from './wallet'
import applications from './applications'
import campaigns from './campaigns'
/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/business',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/api/v1/business/{business}'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/api/v1/business/{business}'
*/
show.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/api/v1/business/{business}'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/api/v1/business/{business}'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/api/v1/business/{business}'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/api/v1/business/{business}'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/api/v1/business/{business}'
*/
showForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const business = {
    auditReports: Object.assign(auditReports, auditReports),
    index: Object.assign(index, index),
    repayments: Object.assign(repayments, repayments),
    show: Object.assign(show, show),
    wallet: Object.assign(wallet, wallet),
    applications: Object.assign(applications, applications),
    campaigns: Object.assign(campaigns, campaigns),
}

export default business