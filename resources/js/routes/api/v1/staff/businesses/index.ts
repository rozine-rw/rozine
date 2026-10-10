import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/businesses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:26
* @route '/api/v1/staff/businesses'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

const businesses = {
    index: Object.assign(index, index),
}

export default businesses