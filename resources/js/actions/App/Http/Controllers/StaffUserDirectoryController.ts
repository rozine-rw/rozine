import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffUserDirectoryController::index
* @see app/Http/Controllers/StaffUserDirectoryController.php:41
* @route '/admin/users'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/users',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffUserDirectoryController::index
* @see app/Http/Controllers/StaffUserDirectoryController.php:41
* @route '/admin/users'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffUserDirectoryController::index
* @see app/Http/Controllers/StaffUserDirectoryController.php:41
* @route '/admin/users'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffUserDirectoryController::index
* @see app/Http/Controllers/StaffUserDirectoryController.php:41
* @route '/admin/users'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffUserDirectoryController::index
* @see app/Http/Controllers/StaffUserDirectoryController.php:41
* @route '/admin/users'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffUserDirectoryController::index
* @see app/Http/Controllers/StaffUserDirectoryController.php:41
* @route '/admin/users'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffUserDirectoryController::index
* @see app/Http/Controllers/StaffUserDirectoryController.php:41
* @route '/admin/users'
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

const StaffUserDirectoryController = { index }

export default StaffUserDirectoryController