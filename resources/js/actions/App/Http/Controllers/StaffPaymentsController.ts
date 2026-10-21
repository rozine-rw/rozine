import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
const StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.url(options),
    method: 'get',
})

StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/payments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.url = (options?: RouteQueryOptions) => {
    return StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
const StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/api/v1/staff/payments'
*/
StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082.form = StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082Form
/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/admin/payments'
*/
const StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.url(options),
    method: 'get',
})

StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.definition = {
    methods: ["get","head"],
    url: '/admin/payments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/admin/payments'
*/
StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.url = (options?: RouteQueryOptions) => {
    return StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/admin/payments'
*/
StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/admin/payments'
*/
StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/admin/payments'
*/
const StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/admin/payments'
*/
StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPaymentsController::__invoke
* @see app/Http/Controllers/StaffPaymentsController.php:25
* @route '/admin/payments'
*/
StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030.form = StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffPaymentsController::StaffPaymentsController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffPaymentsController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffPaymentsController = {
    '/api/v1/staff/payments': StaffPaymentsController4b7fd018556f06bc138d3c9ca29e6082,
    '/admin/payments': StaffPaymentsControllerf0a8d547aea657c8a574f1d622b6a030,
}

export default StaffPaymentsController