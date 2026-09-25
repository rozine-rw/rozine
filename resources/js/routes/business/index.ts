import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import applications from './applications'
/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:15
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
* @see app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:15
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

const business = {
    home: Object.assign(home, home),
    applications: Object.assign(applications, applications),
}

export default business