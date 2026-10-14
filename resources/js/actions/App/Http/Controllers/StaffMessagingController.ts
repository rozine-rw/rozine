import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
const StaffMessagingController35bd237eba7654bc158cc5aeecbede52 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffMessagingController35bd237eba7654bc158cc5aeecbede52.url(options),
    method: 'get',
})

StaffMessagingController35bd237eba7654bc158cc5aeecbede52.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/messaging/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
StaffMessagingController35bd237eba7654bc158cc5aeecbede52.url = (options?: RouteQueryOptions) => {
    return StaffMessagingController35bd237eba7654bc158cc5aeecbede52.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
StaffMessagingController35bd237eba7654bc158cc5aeecbede52.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffMessagingController35bd237eba7654bc158cc5aeecbede52.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
StaffMessagingController35bd237eba7654bc158cc5aeecbede52.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffMessagingController35bd237eba7654bc158cc5aeecbede52.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
const StaffMessagingController35bd237eba7654bc158cc5aeecbede52Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffMessagingController35bd237eba7654bc158cc5aeecbede52.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
StaffMessagingController35bd237eba7654bc158cc5aeecbede52Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffMessagingController35bd237eba7654bc158cc5aeecbede52.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
StaffMessagingController35bd237eba7654bc158cc5aeecbede52Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffMessagingController35bd237eba7654bc158cc5aeecbede52.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffMessagingController35bd237eba7654bc158cc5aeecbede52.form = StaffMessagingController35bd237eba7654bc158cc5aeecbede52Form
/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/admin/messaging/history'
*/
const StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.url(options),
    method: 'get',
})

StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.definition = {
    methods: ["get","head"],
    url: '/admin/messaging/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/admin/messaging/history'
*/
StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.url = (options?: RouteQueryOptions) => {
    return StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/admin/messaging/history'
*/
StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/admin/messaging/history'
*/
StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/admin/messaging/history'
*/
const StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97deForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/admin/messaging/history'
*/
StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97deForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/admin/messaging/history'
*/
StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97deForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de.form = StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97deForm

/**
* Multiple routes resolve to \App\Http\Controllers\StaffMessagingController::StaffMessagingController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffMessagingController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffMessagingController = {
    '/api/v1/staff/messaging/history': StaffMessagingController35bd237eba7654bc158cc5aeecbede52,
    '/admin/messaging/history': StaffMessagingControlleradcc36db34ac5cc3be5e84868cbf97de,
}

export default StaffMessagingController