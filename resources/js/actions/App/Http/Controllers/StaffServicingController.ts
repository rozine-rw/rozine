import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
const StaffServicingControllerb884c1afdc66cada8438b70faf7ca064 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.url(options),
    method: 'get',
})

StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/repayments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.url = (options?: RouteQueryOptions) => {
    return StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
const StaffServicingControllerb884c1afdc66cada8438b70faf7ca064Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
StaffServicingControllerb884c1afdc66cada8438b70faf7ca064Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/api/v1/staff/repayments'
*/
StaffServicingControllerb884c1afdc66cada8438b70faf7ca064Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffServicingControllerb884c1afdc66cada8438b70faf7ca064.form = StaffServicingControllerb884c1afdc66cada8438b70faf7ca064Form
/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
const StaffServicingControllerb858e4990c97e2719f3e0c252e63d129 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.url(options),
    method: 'get',
})

StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.definition = {
    methods: ["get","head"],
    url: '/admin/repayments/data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.url = (options?: RouteQueryOptions) => {
    return StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
const StaffServicingControllerb858e4990c97e2719f3e0c252e63d129Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
StaffServicingControllerb858e4990c97e2719f3e0c252e63d129Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
StaffServicingControllerb858e4990c97e2719f3e0c252e63d129Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffServicingControllerb858e4990c97e2719f3e0c252e63d129.form = StaffServicingControllerb858e4990c97e2719f3e0c252e63d129Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffServicingController::StaffServicingController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffServicingController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffServicingController = {
    '/api/v1/staff/repayments': StaffServicingControllerb884c1afdc66cada8438b70faf7ca064,
    '/admin/repayments/data': StaffServicingControllerb858e4990c97e2719f3e0c252e63d129,
}

export default StaffServicingController