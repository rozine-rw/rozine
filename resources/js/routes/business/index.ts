import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import auditReports from './audit-reports'
import repayments from './repayments'
import wallet from './wallet'
import applications from './applications'
import campaigns from './campaigns'
/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/business',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
homeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

home.form = homeForm

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
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
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:16
* @route '/business/{business}'
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
    home: Object.assign(home, home),
    repayments: Object.assign(repayments, repayments),
    show: Object.assign(show, show),
    wallet: Object.assign(wallet, wallet),
    applications: Object.assign(applications, applications),
    campaigns: Object.assign(campaigns, campaigns),
}

export default business