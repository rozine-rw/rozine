import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PulseController::store
* @see app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/pulse/investor',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PulseController::store
* @see app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::store
* @see app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::store
* @see app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::store
* @see app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const investor = {
    store: Object.assign(store, store),
}

export default investor