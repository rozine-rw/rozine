import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/changes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

const changes = {
    index: Object.assign(index, index),
}

export default changes