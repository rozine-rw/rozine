import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
const StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.url(options),
    method: 'get',
})

StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.url = (options?: RouteQueryOptions) => {
    return StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
const StaffDashboardControlleraed298dd70b90860f777bd895fb5a55aForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
StaffDashboardControlleraed298dd70b90860f777bd895fb5a55aForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
StaffDashboardControlleraed298dd70b90860f777bd895fb5a55aForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a.form = StaffDashboardControlleraed298dd70b90860f777bd895fb5a55aForm
/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
const StaffDashboardController750aeb224105761400ee952169bd178c = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffDashboardController750aeb224105761400ee952169bd178c.url(options),
    method: 'get',
})

StaffDashboardController750aeb224105761400ee952169bd178c.definition = {
    methods: ["get","head"],
    url: '/admin/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
StaffDashboardController750aeb224105761400ee952169bd178c.url = (options?: RouteQueryOptions) => {
    return StaffDashboardController750aeb224105761400ee952169bd178c.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
StaffDashboardController750aeb224105761400ee952169bd178c.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffDashboardController750aeb224105761400ee952169bd178c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
StaffDashboardController750aeb224105761400ee952169bd178c.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffDashboardController750aeb224105761400ee952169bd178c.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
const StaffDashboardController750aeb224105761400ee952169bd178cForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffDashboardController750aeb224105761400ee952169bd178c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
StaffDashboardController750aeb224105761400ee952169bd178cForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffDashboardController750aeb224105761400ee952169bd178c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
StaffDashboardController750aeb224105761400ee952169bd178cForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffDashboardController750aeb224105761400ee952169bd178c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffDashboardController750aeb224105761400ee952169bd178c.form = StaffDashboardController750aeb224105761400ee952169bd178cForm

/**
* Multiple routes resolve to \App\Http\Controllers\StaffDashboardController::StaffDashboardController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffDashboardController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffDashboardController = {
    '/api/v1/staff/dashboard': StaffDashboardControlleraed298dd70b90860f777bd895fb5a55a,
    '/admin/dashboard': StaffDashboardController750aeb224105761400ee952169bd178c,
}

export default StaffDashboardController