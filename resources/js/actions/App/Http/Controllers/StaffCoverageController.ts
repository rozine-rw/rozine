import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/api/v1/staff/coverage'
*/
const StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.url(options),
    method: 'get',
})

StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/coverage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/api/v1/staff/coverage'
*/
StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.url = (options?: RouteQueryOptions) => {
    return StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/api/v1/staff/coverage'
*/
StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/api/v1/staff/coverage'
*/
StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/api/v1/staff/coverage'
*/
const StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/api/v1/staff/coverage'
*/
StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/api/v1/staff/coverage'
*/
StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990.form = StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990Form
/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/admin/coverage'
*/
const StaffCoverageController218372b23821f56108bce3c22e4766c2 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffCoverageController218372b23821f56108bce3c22e4766c2.url(options),
    method: 'get',
})

StaffCoverageController218372b23821f56108bce3c22e4766c2.definition = {
    methods: ["get","head"],
    url: '/admin/coverage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/admin/coverage'
*/
StaffCoverageController218372b23821f56108bce3c22e4766c2.url = (options?: RouteQueryOptions) => {
    return StaffCoverageController218372b23821f56108bce3c22e4766c2.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/admin/coverage'
*/
StaffCoverageController218372b23821f56108bce3c22e4766c2.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffCoverageController218372b23821f56108bce3c22e4766c2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/admin/coverage'
*/
StaffCoverageController218372b23821f56108bce3c22e4766c2.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffCoverageController218372b23821f56108bce3c22e4766c2.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/admin/coverage'
*/
const StaffCoverageController218372b23821f56108bce3c22e4766c2Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffCoverageController218372b23821f56108bce3c22e4766c2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/admin/coverage'
*/
StaffCoverageController218372b23821f56108bce3c22e4766c2Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffCoverageController218372b23821f56108bce3c22e4766c2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffCoverageController::__invoke
* @see app/Http/Controllers/StaffCoverageController.php:21
* @route '/admin/coverage'
*/
StaffCoverageController218372b23821f56108bce3c22e4766c2Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffCoverageController218372b23821f56108bce3c22e4766c2.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffCoverageController218372b23821f56108bce3c22e4766c2.form = StaffCoverageController218372b23821f56108bce3c22e4766c2Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffCoverageController::StaffCoverageController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffCoverageController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffCoverageController = {
    '/api/v1/staff/coverage': StaffCoverageController97b8b8bcbeb350a646ef0c2b7340c990,
    '/admin/coverage': StaffCoverageController218372b23821f56108bce3c22e4766c2,
}

export default StaffCoverageController