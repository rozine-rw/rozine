import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PulseController::preview
* @see app/Http/Controllers/PulseController.php:40
* @route '/pulse/investor/preview'
*/
export const preview = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

preview.definition = {
    methods: ["post"],
    url: '/pulse/investor/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PulseController::preview
* @see app/Http/Controllers/PulseController.php:40
* @route '/pulse/investor/preview'
*/
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::preview
* @see app/Http/Controllers/PulseController.php:40
* @route '/pulse/investor/preview'
*/
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::preview
* @see app/Http/Controllers/PulseController.php:40
* @route '/pulse/investor/preview'
*/
const previewForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::preview
* @see app/Http/Controllers/PulseController.php:40
* @route '/pulse/investor/preview'
*/
previewForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
})

preview.form = previewForm

/**
* @see \App\Http\Controllers\PulseController::store
* @see app/Http/Controllers/PulseController.php:68
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
* @see app/Http/Controllers/PulseController.php:68
* @route '/pulse/investor'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::store
* @see app/Http/Controllers/PulseController.php:68
* @route '/pulse/investor'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::store
* @see app/Http/Controllers/PulseController.php:68
* @route '/pulse/investor'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::store
* @see app/Http/Controllers/PulseController.php:68
* @route '/pulse/investor'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const investor = {
    preview: Object.assign(preview, preview),
    store: Object.assign(store, store),
}

export default investor