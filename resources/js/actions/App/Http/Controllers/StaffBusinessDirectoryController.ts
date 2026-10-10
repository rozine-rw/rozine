import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
const indexdb3a888129fba9a701bb6313c25269b6 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'get',
})

indexdb3a888129fba9a701bb6313c25269b6.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/businesses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6.url = (options?: RouteQueryOptions) => {
    return indexdb3a888129fba9a701bb6313c25269b6.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
const indexdb3a888129fba9a701bb6313c25269b6Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexdb3a888129fba9a701bb6313c25269b6.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexdb3a888129fba9a701bb6313c25269b6.form = indexdb3a888129fba9a701bb6313c25269b6Form
/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/admin/businesses'
*/
const index8c8e212d400b1e5f7bb096b3a45096d9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'get',
})

index8c8e212d400b1e5f7bb096b3a45096d9.definition = {
    methods: ["get","head"],
    url: '/admin/businesses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9.url = (options?: RouteQueryOptions) => {
    return index8c8e212d400b1e5f7bb096b3a45096d9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/admin/businesses'
*/
const index8c8e212d400b1e5f7bb096b3a45096d9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8c8e212d400b1e5f7bb096b3a45096d9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index8c8e212d400b1e5f7bb096b3a45096d9.form = index8c8e212d400b1e5f7bb096b3a45096d9Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffBusinessDirectoryController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/businesses': indexdb3a888129fba9a701bb6313c25269b6,
    '/admin/businesses': index8c8e212d400b1e5f7bb096b3a45096d9,
}

const StaffBusinessDirectoryController = { index }

export default StaffBusinessDirectoryController