import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/api/v1/staff/staff'
*/
const indexc83dab306741c8ddcc901f3f7b92cbb9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'get',
})

indexc83dab306741c8ddcc901f3f7b92cbb9.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/staff',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9.url = (options?: RouteQueryOptions) => {
    return indexc83dab306741c8ddcc901f3f7b92cbb9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/api/v1/staff/staff'
*/
const indexc83dab306741c8ddcc901f3f7b92cbb9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexc83dab306741c8ddcc901f3f7b92cbb9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexc83dab306741c8ddcc901f3f7b92cbb9.form = indexc83dab306741c8ddcc901f3f7b92cbb9Form
/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/admin/staff'
*/
const index49be9ecafad32c357f5919028afa2421 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'get',
})

index49be9ecafad32c357f5919028afa2421.definition = {
    methods: ["get","head"],
    url: '/admin/staff',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421.url = (options?: RouteQueryOptions) => {
    return index49be9ecafad32c357f5919028afa2421.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/admin/staff'
*/
const index49be9ecafad32c357f5919028afa2421Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:20
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index49be9ecafad32c357f5919028afa2421.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index49be9ecafad32c357f5919028afa2421.form = index49be9ecafad32c357f5919028afa2421Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffDirectoryController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/staff': indexc83dab306741c8ddcc901f3f7b92cbb9,
    '/admin/staff': index49be9ecafad32c357f5919028afa2421,
}

const StaffDirectoryController = { index }

export default StaffDirectoryController