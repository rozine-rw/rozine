import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\IdentityManagementController::resolve
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
export const resolve = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(options),
    method: 'post',
})

resolve.definition = {
    methods: ["post"],
    url: '/identity/people/resolve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\IdentityManagementController::resolve
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
resolve.url = (options?: RouteQueryOptions) => {
    return resolve.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\IdentityManagementController::resolve
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
resolve.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::resolve
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
const resolveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::resolve
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
resolveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve.url(options),
    method: 'post',
})

resolve.form = resolveForm

const people = {
    resolve: Object.assign(resolve, resolve),
}

export default people