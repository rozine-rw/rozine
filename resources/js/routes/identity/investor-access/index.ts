import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see app/Http/Controllers/IdentityManagementController.php:61
* @route '/identity/investor-access'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/identity/investor-access',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see app/Http/Controllers/IdentityManagementController.php:61
* @route '/identity/investor-access'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see app/Http/Controllers/IdentityManagementController.php:61
* @route '/identity/investor-access'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see app/Http/Controllers/IdentityManagementController.php:61
* @route '/identity/investor-access'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::store
* @see app/Http/Controllers/IdentityManagementController.php:61
* @route '/identity/investor-access'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const investorAccess = {
    store: Object.assign(store, store),
}

export default investorAccess