import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
const DashboardVersionController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DashboardVersionController.url(options),
    method: 'get',
})

DashboardVersionController.definition = {
    methods: ["get","head"],
    url: '/dashboard/version',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
DashboardVersionController.url = (options?: RouteQueryOptions) => {
    return DashboardVersionController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
DashboardVersionController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DashboardVersionController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
DashboardVersionController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: DashboardVersionController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
const DashboardVersionControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: DashboardVersionController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
DashboardVersionControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: DashboardVersionController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardVersionController::__invoke
* @see app/Http/Controllers/DashboardVersionController.php:18
* @route '/dashboard/version'
*/
DashboardVersionControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: DashboardVersionController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

DashboardVersionController.form = DashboardVersionControllerForm

export default DashboardVersionController