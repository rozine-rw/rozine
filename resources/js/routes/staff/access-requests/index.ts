import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:47
* @route '/admin/access-requests'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/access-requests',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:47
* @route '/admin/access-requests'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:47
* @route '/admin/access-requests'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:47
* @route '/admin/access-requests'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:47
* @route '/admin/access-requests'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:47
* @route '/admin/access-requests'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:47
* @route '/admin/access-requests'
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

const accessRequests = {
    index: Object.assign(index, index),
}

export default accessRequests