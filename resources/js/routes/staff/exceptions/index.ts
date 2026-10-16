import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/exceptions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
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

const exceptions = {
    index: Object.assign(index, index),
}

export default exceptions