import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
import applications from './applications'
/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/business',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
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

const business = {
    index: Object.assign(index, index),
    applications: Object.assign(applications, applications),
}

export default business