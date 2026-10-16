import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::link
* @see app/Http/Controllers/BusinessPayoutAccountController.php:28
* @route '/business/{business}/profile/linked-accounts'
*/
export const link = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: link.url(args, options),
    method: 'post',
})

link.definition = {
    methods: ["post"],
    url: '/business/{business}/profile/linked-accounts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::link
* @see app/Http/Controllers/BusinessPayoutAccountController.php:28
* @route '/business/{business}/profile/linked-accounts'
*/
link.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return link.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::link
* @see app/Http/Controllers/BusinessPayoutAccountController.php:28
* @route '/business/{business}/profile/linked-accounts'
*/
link.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: link.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::link
* @see app/Http/Controllers/BusinessPayoutAccountController.php:28
* @route '/business/{business}/profile/linked-accounts'
*/
const linkForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: link.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::link
* @see app/Http/Controllers/BusinessPayoutAccountController.php:28
* @route '/business/{business}/profile/linked-accounts'
*/
linkForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: link.url(args, options),
    method: 'post',
})

link.form = linkForm

/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::unlink
* @see app/Http/Controllers/BusinessPayoutAccountController.php:36
* @route '/business/{business}/profile/linked-accounts/{method}/unlink'
*/
export const unlink = (args: { business: string | number, method: string | number } | [business: string | number, method: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unlink.url(args, options),
    method: 'post',
})

unlink.definition = {
    methods: ["post"],
    url: '/business/{business}/profile/linked-accounts/{method}/unlink',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::unlink
* @see app/Http/Controllers/BusinessPayoutAccountController.php:36
* @route '/business/{business}/profile/linked-accounts/{method}/unlink'
*/
unlink.url = (args: { business: string | number, method: string | number } | [business: string | number, method: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            method: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        method: args.method,
    }

    return unlink.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{method}', parsedArgs.method.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::unlink
* @see app/Http/Controllers/BusinessPayoutAccountController.php:36
* @route '/business/{business}/profile/linked-accounts/{method}/unlink'
*/
unlink.post = (args: { business: string | number, method: string | number } | [business: string | number, method: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unlink.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::unlink
* @see app/Http/Controllers/BusinessPayoutAccountController.php:36
* @route '/business/{business}/profile/linked-accounts/{method}/unlink'
*/
const unlinkForm = (args: { business: string | number, method: string | number } | [business: string | number, method: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlink.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPayoutAccountController::unlink
* @see app/Http/Controllers/BusinessPayoutAccountController.php:36
* @route '/business/{business}/profile/linked-accounts/{method}/unlink'
*/
unlinkForm.post = (args: { business: string | number, method: string | number } | [business: string | number, method: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlink.url(args, options),
    method: 'post',
})

unlink.form = unlinkForm

const BusinessPayoutAccountController = { link, unlink }

export default BusinessPayoutAccountController