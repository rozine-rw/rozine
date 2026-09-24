import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::show
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:16
* @route '/api/v1/identity/bookmarks/{role}'
*/
export const show = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/identity/bookmarks/{role}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::show
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:16
* @route '/api/v1/identity/bookmarks/{role}'
*/
show.url = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { role: args }
    }

    if (Array.isArray(args)) {
        args = {
            role: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        role: args.role,
    }

    return show.definition.url
            .replace('{role}', parsedArgs.role.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::show
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:16
* @route '/api/v1/identity/bookmarks/{role}'
*/
show.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::show
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:16
* @route '/api/v1/identity/bookmarks/{role}'
*/
show.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::show
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:16
* @route '/api/v1/identity/bookmarks/{role}'
*/
const showForm = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::show
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:16
* @route '/api/v1/identity/bookmarks/{role}'
*/
showForm.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::show
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:16
* @route '/api/v1/identity/bookmarks/{role}'
*/
showForm.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:23
* @route '/api/v1/identity/bookmarks'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/v1/identity/bookmarks',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:23
* @route '/api/v1/identity/bookmarks'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:23
* @route '/api/v1/identity/bookmarks'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:23
* @route '/api/v1/identity/bookmarks'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\RoleBookmarkController::store
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/RoleBookmarkController.php:23
* @route '/api/v1/identity/bookmarks'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const RoleBookmarkController = { show, store }

export default RoleBookmarkController