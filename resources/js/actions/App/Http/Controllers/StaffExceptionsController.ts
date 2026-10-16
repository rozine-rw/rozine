import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/api/v1/staff/exceptions'
*/
const StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.url(options),
    method: 'get',
})

StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/exceptions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/api/v1/staff/exceptions'
*/
StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.url = (options?: RouteQueryOptions) => {
    return StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/api/v1/staff/exceptions'
*/
StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/api/v1/staff/exceptions'
*/
StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/api/v1/staff/exceptions'
*/
const StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/api/v1/staff/exceptions'
*/
StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/api/v1/staff/exceptions'
*/
StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9.form = StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9Form
/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
const StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.url(options),
    method: 'get',
})

StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.definition = {
    methods: ["get","head"],
    url: '/admin/exceptions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.url = (options?: RouteQueryOptions) => {
    return StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
const StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffExceptionsController::__invoke
* @see app/Http/Controllers/StaffExceptionsController.php:24
* @route '/admin/exceptions'
*/
StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9.form = StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffExceptionsController::StaffExceptionsController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffExceptionsController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffExceptionsController = {
    '/api/v1/staff/exceptions': StaffExceptionsControllercb177878f9bf970037b5aa7f0bded3a9,
    '/admin/exceptions': StaffExceptionsControllerdd2d29fcb3ca50cf185e552c5f818ea9,
}

export default StaffExceptionsController