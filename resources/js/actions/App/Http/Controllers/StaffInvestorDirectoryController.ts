import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
const index4cb6f36ff06cf2dabb5481e6076e1a2c = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index4cb6f36ff06cf2dabb5481e6076e1a2c.url(options),
    method: 'get',
})

index4cb6f36ff06cf2dabb5481e6076e1a2c.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/investors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
index4cb6f36ff06cf2dabb5481e6076e1a2c.url = (options?: RouteQueryOptions) => {
    return index4cb6f36ff06cf2dabb5481e6076e1a2c.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
index4cb6f36ff06cf2dabb5481e6076e1a2c.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index4cb6f36ff06cf2dabb5481e6076e1a2c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
index4cb6f36ff06cf2dabb5481e6076e1a2c.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index4cb6f36ff06cf2dabb5481e6076e1a2c.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
const index4cb6f36ff06cf2dabb5481e6076e1a2cForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index4cb6f36ff06cf2dabb5481e6076e1a2c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
index4cb6f36ff06cf2dabb5481e6076e1a2cForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index4cb6f36ff06cf2dabb5481e6076e1a2c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
index4cb6f36ff06cf2dabb5481e6076e1a2cForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index4cb6f36ff06cf2dabb5481e6076e1a2c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index4cb6f36ff06cf2dabb5481e6076e1a2c.form = index4cb6f36ff06cf2dabb5481e6076e1a2cForm
/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/admin/investors'
*/
const index934c2335ea7976074d571b6035b4ddbf = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index934c2335ea7976074d571b6035b4ddbf.url(options),
    method: 'get',
})

index934c2335ea7976074d571b6035b4ddbf.definition = {
    methods: ["get","head"],
    url: '/admin/investors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/admin/investors'
*/
index934c2335ea7976074d571b6035b4ddbf.url = (options?: RouteQueryOptions) => {
    return index934c2335ea7976074d571b6035b4ddbf.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/admin/investors'
*/
index934c2335ea7976074d571b6035b4ddbf.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index934c2335ea7976074d571b6035b4ddbf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/admin/investors'
*/
index934c2335ea7976074d571b6035b4ddbf.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index934c2335ea7976074d571b6035b4ddbf.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/admin/investors'
*/
const index934c2335ea7976074d571b6035b4ddbfForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index934c2335ea7976074d571b6035b4ddbf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/admin/investors'
*/
index934c2335ea7976074d571b6035b4ddbfForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index934c2335ea7976074d571b6035b4ddbf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/admin/investors'
*/
index934c2335ea7976074d571b6035b4ddbfForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index934c2335ea7976074d571b6035b4ddbf.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index934c2335ea7976074d571b6035b4ddbf.form = index934c2335ea7976074d571b6035b4ddbfForm

/**
* Multiple routes resolve to \App\Http\Controllers\StaffInvestorDirectoryController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/investors': index4cb6f36ff06cf2dabb5481e6076e1a2c,
    '/admin/investors': index934c2335ea7976074d571b6035b4ddbf,
}

const StaffInvestorDirectoryController = { index }

export default StaffInvestorDirectoryController