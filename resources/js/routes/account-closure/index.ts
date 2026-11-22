import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\AccountClosureController::store
* @see app/Http/Controllers/Settings/AccountClosureController.php:21
* @route '/settings/close-account'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/settings/close-account',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\AccountClosureController::store
* @see app/Http/Controllers/Settings/AccountClosureController.php:21
* @route '/settings/close-account'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AccountClosureController::store
* @see app/Http/Controllers/Settings/AccountClosureController.php:21
* @route '/settings/close-account'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AccountClosureController::store
* @see app/Http/Controllers/Settings/AccountClosureController.php:21
* @route '/settings/close-account'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AccountClosureController::store
* @see app/Http/Controllers/Settings/AccountClosureController.php:21
* @route '/settings/close-account'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const accountClosure = {
    store: Object.assign(store, store),
}

export default accountClosure