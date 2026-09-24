import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
const IdentityController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: IdentityController.url(options),
    method: 'get',
})

IdentityController.definition = {
    methods: ["get","head"],
    url: '/api/v1/identity',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
IdentityController.url = (options?: RouteQueryOptions) => {
    return IdentityController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
IdentityController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: IdentityController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
IdentityController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: IdentityController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
const IdentityControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: IdentityController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
IdentityControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: IdentityController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
IdentityControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: IdentityController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

IdentityController.form = IdentityControllerForm

export default IdentityController