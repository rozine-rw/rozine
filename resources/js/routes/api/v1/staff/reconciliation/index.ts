import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/reconciliation',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
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

const reconciliation = {
    index: Object.assign(index, index),
}

export default reconciliation