import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::resolve
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:21
* @route '/api/v1/identity/people/resolve'
*/
export const resolve = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(options),
    method: 'post',
})

resolve.definition = {
    methods: ["post"],
    url: '/api/v1/identity/people/resolve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::resolve
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:21
* @route '/api/v1/identity/people/resolve'
*/
resolve.url = (options?: RouteQueryOptions) => {
    return resolve.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::resolve
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:21
* @route '/api/v1/identity/people/resolve'
*/
resolve.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::resolve
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:21
* @route '/api/v1/identity/people/resolve'
*/
const resolveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::resolve
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:21
* @route '/api/v1/identity/people/resolve'
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