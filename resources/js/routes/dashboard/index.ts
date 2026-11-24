import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
export const version = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: version.url(options),
    method: 'get',
})

version.definition = {
    methods: ["get","head"],
    url: '/dashboard/version',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
version.url = (options?: RouteQueryOptions) => {
    return version.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
version.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: version.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
version.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: version.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
const versionForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: version.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
versionForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: version.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
versionForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: version.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

version.form = versionForm

const dashboard = {
    version: Object.assign(version, version),
}

export default dashboard