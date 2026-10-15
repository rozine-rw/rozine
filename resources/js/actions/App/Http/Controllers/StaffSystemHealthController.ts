import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
const StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.url(options),
    method: 'get',
})

StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/system-health',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.url = (options?: RouteQueryOptions) => {
    return StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
const StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63fForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63fForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63fForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f.form = StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63fForm
/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/admin/system-health'
*/
const StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.url(options),
    method: 'get',
})

StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.definition = {
    methods: ["get","head"],
    url: '/admin/system-health',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/admin/system-health'
*/
StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.url = (options?: RouteQueryOptions) => {
    return StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/admin/system-health'
*/
StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/admin/system-health'
*/
StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/admin/system-health'
*/
const StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/admin/system-health'
*/
StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/admin/system-health'
*/
StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298.form = StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffSystemHealthController::StaffSystemHealthController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffSystemHealthController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffSystemHealthController = {
    '/api/v1/staff/system-health': StaffSystemHealthController6ca8e17029b1a88da7a0f7e65b05e63f,
    '/admin/system-health': StaffSystemHealthControllerfb1aefa608547c4d63d178fb3a98c298,
}

export default StaffSystemHealthController