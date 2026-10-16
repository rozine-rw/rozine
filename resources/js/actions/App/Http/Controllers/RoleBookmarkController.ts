import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\RoleBookmarkController::show
* @see app/Http/Controllers/RoleBookmarkController.php:16
* @route '/identity/bookmarks/{role}'
*/
export const show = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/identity/bookmarks/{role}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleBookmarkController::show
* @see app/Http/Controllers/RoleBookmarkController.php:16
* @route '/identity/bookmarks/{role}'
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
* @see \App\Http\Controllers\RoleBookmarkController::show
* @see app/Http/Controllers/RoleBookmarkController.php:16
* @route '/identity/bookmarks/{role}'
*/
show.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::show
* @see app/Http/Controllers/RoleBookmarkController.php:16
* @route '/identity/bookmarks/{role}'
*/
show.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::show
* @see app/Http/Controllers/RoleBookmarkController.php:16
* @route '/identity/bookmarks/{role}'
*/
const showForm = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::show
* @see app/Http/Controllers/RoleBookmarkController.php:16
* @route '/identity/bookmarks/{role}'
*/
showForm.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::show
* @see app/Http/Controllers/RoleBookmarkController.php:16
* @route '/identity/bookmarks/{role}'
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
* @see \App\Http\Controllers\RoleBookmarkController::store
* @see app/Http/Controllers/RoleBookmarkController.php:23
* @route '/identity/bookmarks'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/identity/bookmarks',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RoleBookmarkController::store
* @see app/Http/Controllers/RoleBookmarkController.php:23
* @route '/identity/bookmarks'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleBookmarkController::store
* @see app/Http/Controllers/RoleBookmarkController.php:23
* @route '/identity/bookmarks'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::store
* @see app/Http/Controllers/RoleBookmarkController.php:23
* @route '/identity/bookmarks'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::store
* @see app/Http/Controllers/RoleBookmarkController.php:23
* @route '/identity/bookmarks'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\RoleBookmarkController::resume
* @see app/Http/Controllers/RoleBookmarkController.php:32
* @route '/identity/roles/{role}/resume'
*/
export const resume = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: resume.url(args, options),
    method: 'get',
})

resume.definition = {
    methods: ["get","head"],
    url: '/identity/roles/{role}/resume',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleBookmarkController::resume
* @see app/Http/Controllers/RoleBookmarkController.php:32
* @route '/identity/roles/{role}/resume'
*/
resume.url = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return resume.definition.url
            .replace('{role}', parsedArgs.role.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleBookmarkController::resume
* @see app/Http/Controllers/RoleBookmarkController.php:32
* @route '/identity/roles/{role}/resume'
*/
resume.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: resume.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::resume
* @see app/Http/Controllers/RoleBookmarkController.php:32
* @route '/identity/roles/{role}/resume'
*/
resume.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: resume.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::resume
* @see app/Http/Controllers/RoleBookmarkController.php:32
* @route '/identity/roles/{role}/resume'
*/
const resumeForm = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: resume.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::resume
* @see app/Http/Controllers/RoleBookmarkController.php:32
* @route '/identity/roles/{role}/resume'
*/
resumeForm.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: resume.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleBookmarkController::resume
* @see app/Http/Controllers/RoleBookmarkController.php:32
* @route '/identity/roles/{role}/resume'
*/
resumeForm.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: resume.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

resume.form = resumeForm

const RoleBookmarkController = { show, store, resume }

export default RoleBookmarkController