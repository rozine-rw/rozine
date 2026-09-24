import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/identity/active-role',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const activeRole = {
    store: Object.assign(store, store),
}

export default activeRole