import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:78
* @route '/investor/verified'
*/
export const verified = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verified.url(options),
    method: 'get',
})

verified.definition = {
    methods: ["get","head"],
    url: '/investor/verified',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:78
* @route '/investor/verified'
*/
verified.url = (options?: RouteQueryOptions) => {
    return verified.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:78
* @route '/investor/verified'
*/
verified.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verified.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:78
* @route '/investor/verified'
*/
verified.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: verified.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:78
* @route '/investor/verified'
*/
const verifiedForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verified.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:78
* @route '/investor/verified'
*/
verifiedForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verified.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::verified
* @see app/Http/Controllers/InvestorProfileController.php:78
* @route '/investor/verified'
*/
verifiedForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verified.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

verified.form = verifiedForm

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:39
* @route '/investor/profile'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/investor/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:39
* @route '/investor/profile'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:39
* @route '/investor/profile'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:39
* @route '/investor/profile'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:39
* @route '/investor/profile'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:39
* @route '/investor/profile'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::show
* @see app/Http/Controllers/InvestorProfileController.php:39
* @route '/investor/profile'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\InvestorProfileController::link
* @see app/Http/Controllers/InvestorProfileController.php:52
* @route '/investor/profile/linked-accounts'
*/
export const link = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: link.url(options),
    method: 'post',
})

link.definition = {
    methods: ["post"],
    url: '/investor/profile/linked-accounts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorProfileController::link
* @see app/Http/Controllers/InvestorProfileController.php:52
* @route '/investor/profile/linked-accounts'
*/
link.url = (options?: RouteQueryOptions) => {
    return link.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProfileController::link
* @see app/Http/Controllers/InvestorProfileController.php:52
* @route '/investor/profile/linked-accounts'
*/
link.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: link.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::link
* @see app/Http/Controllers/InvestorProfileController.php:52
* @route '/investor/profile/linked-accounts'
*/
const linkForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: link.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::link
* @see app/Http/Controllers/InvestorProfileController.php:52
* @route '/investor/profile/linked-accounts'
*/
linkForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: link.url(options),
    method: 'post',
})

link.form = linkForm

/**
* @see \App\Http\Controllers\InvestorProfileController::unlink
* @see app/Http/Controllers/InvestorProfileController.php:58
* @route '/investor/profile/linked-accounts/{method}/unlink'
*/
export const unlink = (args: { method: string | number } | [method: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unlink.url(args, options),
    method: 'post',
})

unlink.definition = {
    methods: ["post"],
    url: '/investor/profile/linked-accounts/{method}/unlink',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorProfileController::unlink
* @see app/Http/Controllers/InvestorProfileController.php:58
* @route '/investor/profile/linked-accounts/{method}/unlink'
*/
unlink.url = (args: { method: string | number } | [method: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { method: args }
    }

    if (Array.isArray(args)) {
        args = {
            method: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        method: args.method,
    }

    return unlink.definition.url
            .replace('{method}', parsedArgs.method.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProfileController::unlink
* @see app/Http/Controllers/InvestorProfileController.php:58
* @route '/investor/profile/linked-accounts/{method}/unlink'
*/
unlink.post = (args: { method: string | number } | [method: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unlink.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::unlink
* @see app/Http/Controllers/InvestorProfileController.php:58
* @route '/investor/profile/linked-accounts/{method}/unlink'
*/
const unlinkForm = (args: { method: string | number } | [method: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlink.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::unlink
* @see app/Http/Controllers/InvestorProfileController.php:58
* @route '/investor/profile/linked-accounts/{method}/unlink'
*/
unlinkForm.post = (args: { method: string | number } | [method: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlink.url(args, options),
    method: 'post',
})

unlink.form = unlinkForm

const InvestorProfileController = { verified, show, link, unlink }

export default InvestorProfileController