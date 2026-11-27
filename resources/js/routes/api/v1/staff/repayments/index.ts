import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/repayments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
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

const repayments = {
    index: Object.assign(index, index),
}

export default repayments