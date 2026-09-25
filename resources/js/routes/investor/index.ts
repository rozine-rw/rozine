import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:17
* @route '/investor'
*/
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/investor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:17
* @route '/investor'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:17
* @route '/investor'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:17
* @route '/investor'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:17
* @route '/investor'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:17
* @route '/investor'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:17
* @route '/investor'
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

const investor = {
    home: Object.assign(home, home),
}

export default investor