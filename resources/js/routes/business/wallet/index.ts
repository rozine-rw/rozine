import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:24
* @route '/business/{business}/wallet'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:24
* @route '/business/{business}/wallet'
*/
show.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:24
* @route '/business/{business}/wallet'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:24
* @route '/business/{business}/wallet'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:24
* @route '/business/{business}/wallet'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:24
* @route '/business/{business}/wallet'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:24
* @route '/business/{business}/wallet'
*/
showForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:34
* @route '/business/{business}/wallet/deposits'
*/
export const deposit = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(args, options),
    method: 'post',
})

deposit.definition = {
    methods: ["post"],
    url: '/business/{business}/wallet/deposits',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:34
* @route '/business/{business}/wallet/deposits'
*/
deposit.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return deposit.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:34
* @route '/business/{business}/wallet/deposits'
*/
deposit.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:34
* @route '/business/{business}/wallet/deposits'
*/
const depositForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:34
* @route '/business/{business}/wallet/deposits'
*/
depositForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit.url(args, options),
    method: 'post',
})

deposit.form = depositForm

const wallet = {
    show: Object.assign(show, show),
    deposit: Object.assign(deposit, deposit),
    operations: Object.assign(operations, operations),
}

export default wallet