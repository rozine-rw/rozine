import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\SiteController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/business',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SiteController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const business = {
    store: Object.assign(store, store),
}

export default business