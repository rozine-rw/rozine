import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
const StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.url(options),
    method: 'get',
})

StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/primary-market',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.url = (options?: RouteQueryOptions) => {
    return StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
const StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3.form = StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3Form
/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/admin/primary-market'
*/
const StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.url(options),
    method: 'get',
})

StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.definition = {
    methods: ["get","head"],
    url: '/admin/primary-market',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/admin/primary-market'
*/
StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.url = (options?: RouteQueryOptions) => {
    return StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/admin/primary-market'
*/
StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/admin/primary-market'
*/
StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/admin/primary-market'
*/
const StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/admin/primary-market'
*/
StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/admin/primary-market'
*/
StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42.form = StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffPrimaryMarketController::StaffPrimaryMarketController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffPrimaryMarketController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffPrimaryMarketController = {
    '/api/v1/staff/primary-market': StaffPrimaryMarketController9920a41178cb37e3cdf36518642115b3,
    '/admin/primary-market': StaffPrimaryMarketController5822bd88f7f0962a7f851eb3558cfd42,
}

export default StaffPrimaryMarketController