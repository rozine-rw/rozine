import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorProfileController::link
* @see app/Http/Controllers/InvestorProfileController.php:54
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
* @see app/Http/Controllers/InvestorProfileController.php:54
* @route '/investor/profile/linked-accounts'
*/
link.url = (options?: RouteQueryOptions) => {
    return link.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProfileController::link
* @see app/Http/Controllers/InvestorProfileController.php:54
* @route '/investor/profile/linked-accounts'
*/
link.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: link.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::link
* @see app/Http/Controllers/InvestorProfileController.php:54
* @route '/investor/profile/linked-accounts'
*/
const linkForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: link.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::link
* @see app/Http/Controllers/InvestorProfileController.php:54
* @route '/investor/profile/linked-accounts'
*/
linkForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: link.url(options),
    method: 'post',
})

link.form = linkForm

/**
* @see \App\Http\Controllers\InvestorProfileController::unlink
* @see app/Http/Controllers/InvestorProfileController.php:60
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
* @see app/Http/Controllers/InvestorProfileController.php:60
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
* @see app/Http/Controllers/InvestorProfileController.php:60
* @route '/investor/profile/linked-accounts/{method}/unlink'
*/
unlink.post = (args: { method: string | number } | [method: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unlink.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::unlink
* @see app/Http/Controllers/InvestorProfileController.php:60
* @route '/investor/profile/linked-accounts/{method}/unlink'
*/
const unlinkForm = (args: { method: string | number } | [method: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlink.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorProfileController::unlink
* @see app/Http/Controllers/InvestorProfileController.php:60
* @route '/investor/profile/linked-accounts/{method}/unlink'
*/
unlinkForm.post = (args: { method: string | number } | [method: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlink.url(args, options),
    method: 'post',
})

unlink.form = unlinkForm

const profile = {
    link: Object.assign(link, link),
    unlink: Object.assign(unlink, unlink),
}

export default profile