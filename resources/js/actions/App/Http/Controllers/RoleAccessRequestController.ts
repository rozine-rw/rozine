import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\RoleAccessRequestController::store
* @see app/Http/Controllers/RoleAccessRequestController.php:33
* @route '/identity/access-requests'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/identity/access-requests',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RoleAccessRequestController::store
* @see app/Http/Controllers/RoleAccessRequestController.php:33
* @route '/identity/access-requests'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleAccessRequestController::store
* @see app/Http/Controllers/RoleAccessRequestController.php:33
* @route '/identity/access-requests'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::store
* @see app/Http/Controllers/RoleAccessRequestController.php:33
* @route '/identity/access-requests'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::store
* @see app/Http/Controllers/RoleAccessRequestController.php:33
* @route '/identity/access-requests'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

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

const RoleAccessRequestController = { store, index }

export default RoleAccessRequestController