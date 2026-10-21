import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/payments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
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

const payments = {
    index: Object.assign(index, index),
}

export default payments