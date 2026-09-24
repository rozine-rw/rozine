import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::update
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityManagementController.php:30
* @route '/api/v1/identity/memberships'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/api/v1/identity/memberships',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::update
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityManagementController.php:30
* @route '/api/v1/identity/memberships'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::update
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityManagementController.php:30
* @route '/api/v1/identity/memberships'
*/
update.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::update
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityManagementController.php:30
* @route '/api/v1/identity/memberships'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::update
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityManagementController.php:30
* @route '/api/v1/identity/memberships'
*/
updateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

update.form = updateForm

const memberships = {
    update: Object.assign(update, update),
}

export default memberships