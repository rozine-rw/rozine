import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PulseController::preview
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
export const preview = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

preview.definition = {
    methods: ["post"],
    url: '/pulse/business/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PulseController::preview
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::preview
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::preview
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
const previewForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::preview
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
previewForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
})

preview.form = previewForm

/**
* @see \App\Http\Controllers\PulseController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/pulse/business',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PulseController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const business = {
    preview: Object.assign(preview, preview),
    store: Object.assign(store, store),
}

export default business