import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:51
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
* @see app/Http/Controllers/RoleAccessRequestController.php:51
* @route '/admin/access-requests'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:51
* @route '/admin/access-requests'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:51
* @route '/admin/access-requests'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:51
* @route '/admin/access-requests'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:51
* @route '/admin/access-requests'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::index
* @see app/Http/Controllers/RoleAccessRequestController.php:51
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

/**
* @see \App\Http\Controllers\RoleAccessRequestController::grant
* @see app/Http/Controllers/RoleAccessRequestController.php:74
* @route '/admin/access-requests/{accessRequest}/grant'
*/
export const grant = (args: { accessRequest: string | { id: string } } | [accessRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grant.url(args, options),
    method: 'post',
})

grant.definition = {
    methods: ["post"],
    url: '/admin/access-requests/{accessRequest}/grant',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RoleAccessRequestController::grant
* @see app/Http/Controllers/RoleAccessRequestController.php:74
* @route '/admin/access-requests/{accessRequest}/grant'
*/
grant.url = (args: { accessRequest: string | { id: string } } | [accessRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { accessRequest: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { accessRequest: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            accessRequest: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        accessRequest: typeof args.accessRequest === 'object'
        ? args.accessRequest.id
        : args.accessRequest,
    }

    return grant.definition.url
            .replace('{accessRequest}', parsedArgs.accessRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleAccessRequestController::grant
* @see app/Http/Controllers/RoleAccessRequestController.php:74
* @route '/admin/access-requests/{accessRequest}/grant'
*/
grant.post = (args: { accessRequest: string | { id: string } } | [accessRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grant.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::grant
* @see app/Http/Controllers/RoleAccessRequestController.php:74
* @route '/admin/access-requests/{accessRequest}/grant'
*/
const grantForm = (args: { accessRequest: string | { id: string } } | [accessRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grant.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleAccessRequestController::grant
* @see app/Http/Controllers/RoleAccessRequestController.php:74
* @route '/admin/access-requests/{accessRequest}/grant'
*/
grantForm.post = (args: { accessRequest: string | { id: string } } | [accessRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grant.url(args, options),
    method: 'post',
})

grant.form = grantForm

const accessRequests = {
    index: Object.assign(index, index),
    grant: Object.assign(grant, grant),
}

export default accessRequests