import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/admin',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
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

const admin = {
    home: Object.assign(home, home),
}

export default admin