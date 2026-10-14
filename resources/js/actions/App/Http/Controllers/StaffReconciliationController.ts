import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
const StaffReconciliationController32096c59b1bff20b7057988d296c9e8d = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.url(options),
    method: 'get',
})

StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/reconciliation',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.url = (options?: RouteQueryOptions) => {
    return StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
const StaffReconciliationController32096c59b1bff20b7057988d296c9e8dForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
StaffReconciliationController32096c59b1bff20b7057988d296c9e8dForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/api/v1/staff/reconciliation'
*/
StaffReconciliationController32096c59b1bff20b7057988d296c9e8dForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffReconciliationController32096c59b1bff20b7057988d296c9e8d.form = StaffReconciliationController32096c59b1bff20b7057988d296c9e8dForm
/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/admin/reconciliation'
*/
const StaffReconciliationController655e493412a7a435ed51faf01ece16b6 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffReconciliationController655e493412a7a435ed51faf01ece16b6.url(options),
    method: 'get',
})

StaffReconciliationController655e493412a7a435ed51faf01ece16b6.definition = {
    methods: ["get","head"],
    url: '/admin/reconciliation',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/admin/reconciliation'
*/
StaffReconciliationController655e493412a7a435ed51faf01ece16b6.url = (options?: RouteQueryOptions) => {
    return StaffReconciliationController655e493412a7a435ed51faf01ece16b6.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/admin/reconciliation'
*/
StaffReconciliationController655e493412a7a435ed51faf01ece16b6.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffReconciliationController655e493412a7a435ed51faf01ece16b6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/admin/reconciliation'
*/
StaffReconciliationController655e493412a7a435ed51faf01ece16b6.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffReconciliationController655e493412a7a435ed51faf01ece16b6.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/admin/reconciliation'
*/
const StaffReconciliationController655e493412a7a435ed51faf01ece16b6Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffReconciliationController655e493412a7a435ed51faf01ece16b6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/admin/reconciliation'
*/
StaffReconciliationController655e493412a7a435ed51faf01ece16b6Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffReconciliationController655e493412a7a435ed51faf01ece16b6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffReconciliationController::__invoke
* @see app/Http/Controllers/StaffReconciliationController.php:21
* @route '/admin/reconciliation'
*/
StaffReconciliationController655e493412a7a435ed51faf01ece16b6Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffReconciliationController655e493412a7a435ed51faf01ece16b6.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffReconciliationController655e493412a7a435ed51faf01ece16b6.form = StaffReconciliationController655e493412a7a435ed51faf01ece16b6Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffReconciliationController::StaffReconciliationController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffReconciliationController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffReconciliationController = {
    '/api/v1/staff/reconciliation': StaffReconciliationController32096c59b1bff20b7057988d296c9e8d,
    '/admin/reconciliation': StaffReconciliationController655e493412a7a435ed51faf01ece16b6,
}

export default StaffReconciliationController