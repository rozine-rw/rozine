import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorSecondaryController::list
* @see app/Http/Controllers/InvestorSecondaryController.php:18
* @route '/investor/market/listings'
*/
export const list = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: list.url(options),
    method: 'post',
})

list.definition = {
    methods: ["post"],
    url: '/investor/market/listings',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorSecondaryController::list
* @see app/Http/Controllers/InvestorSecondaryController.php:18
* @route '/investor/market/listings'
*/
list.url = (options?: RouteQueryOptions) => {
    return list.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorSecondaryController::list
* @see app/Http/Controllers/InvestorSecondaryController.php:18
* @route '/investor/market/listings'
*/
list.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: list.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorSecondaryController::list
* @see app/Http/Controllers/InvestorSecondaryController.php:18
* @route '/investor/market/listings'
*/
const listForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: list.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorSecondaryController::list
* @see app/Http/Controllers/InvestorSecondaryController.php:18
* @route '/investor/market/listings'
*/
listForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: list.url(options),
    method: 'post',
})

list.form = listForm

/**
* @see \App\Http\Controllers\InvestorSecondaryController::reserve
* @see app/Http/Controllers/InvestorSecondaryController.php:25
* @route '/investor/market/listings/{listing}/reserve'
*/
export const reserve = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve.url(args, options),
    method: 'post',
})

reserve.definition = {
    methods: ["post"],
    url: '/investor/market/listings/{listing}/reserve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorSecondaryController::reserve
* @see app/Http/Controllers/InvestorSecondaryController.php:25
* @route '/investor/market/listings/{listing}/reserve'
*/
reserve.url = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { listing: args }
    }

    if (Array.isArray(args)) {
        args = {
            listing: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        listing: args.listing,
    }

    return reserve.definition.url
            .replace('{listing}', parsedArgs.listing.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorSecondaryController::reserve
* @see app/Http/Controllers/InvestorSecondaryController.php:25
* @route '/investor/market/listings/{listing}/reserve'
*/
reserve.post = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorSecondaryController::reserve
* @see app/Http/Controllers/InvestorSecondaryController.php:25
* @route '/investor/market/listings/{listing}/reserve'
*/
const reserveForm = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorSecondaryController::reserve
* @see app/Http/Controllers/InvestorSecondaryController.php:25
* @route '/investor/market/listings/{listing}/reserve'
*/
reserveForm.post = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve.url(args, options),
    method: 'post',
})

reserve.form = reserveForm

/**
* @see \App\Http\Controllers\InvestorSecondaryController::settle
* @see app/Http/Controllers/InvestorSecondaryController.php:31
* @route '/investor/market/listings/{listing}/settle'
*/
export const settle = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: settle.url(args, options),
    method: 'post',
})

settle.definition = {
    methods: ["post"],
    url: '/investor/market/listings/{listing}/settle',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorSecondaryController::settle
* @see app/Http/Controllers/InvestorSecondaryController.php:31
* @route '/investor/market/listings/{listing}/settle'
*/
settle.url = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { listing: args }
    }

    if (Array.isArray(args)) {
        args = {
            listing: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        listing: args.listing,
    }

    return settle.definition.url
            .replace('{listing}', parsedArgs.listing.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorSecondaryController::settle
* @see app/Http/Controllers/InvestorSecondaryController.php:31
* @route '/investor/market/listings/{listing}/settle'
*/
settle.post = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: settle.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorSecondaryController::settle
* @see app/Http/Controllers/InvestorSecondaryController.php:31
* @route '/investor/market/listings/{listing}/settle'
*/
const settleForm = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: settle.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorSecondaryController::settle
* @see app/Http/Controllers/InvestorSecondaryController.php:31
* @route '/investor/market/listings/{listing}/settle'
*/
settleForm.post = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: settle.url(args, options),
    method: 'post',
})

settle.form = settleForm

/**
* @see \App\Http\Controllers\InvestorSecondaryController::withdraw
* @see app/Http/Controllers/InvestorSecondaryController.php:37
* @route '/investor/market/listings/{listing}/withdraw'
*/
export const withdraw = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw.url(args, options),
    method: 'post',
})

withdraw.definition = {
    methods: ["post"],
    url: '/investor/market/listings/{listing}/withdraw',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorSecondaryController::withdraw
* @see app/Http/Controllers/InvestorSecondaryController.php:37
* @route '/investor/market/listings/{listing}/withdraw'
*/
withdraw.url = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { listing: args }
    }

    if (Array.isArray(args)) {
        args = {
            listing: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        listing: args.listing,
    }

    return withdraw.definition.url
            .replace('{listing}', parsedArgs.listing.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorSecondaryController::withdraw
* @see app/Http/Controllers/InvestorSecondaryController.php:37
* @route '/investor/market/listings/{listing}/withdraw'
*/
withdraw.post = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorSecondaryController::withdraw
* @see app/Http/Controllers/InvestorSecondaryController.php:37
* @route '/investor/market/listings/{listing}/withdraw'
*/
const withdrawForm = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdraw.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorSecondaryController::withdraw
* @see app/Http/Controllers/InvestorSecondaryController.php:37
* @route '/investor/market/listings/{listing}/withdraw'
*/
withdrawForm.post = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdraw.url(args, options),
    method: 'post',
})

withdraw.form = withdrawForm

const InvestorSecondaryController = { list, reserve, settle, withdraw }

export default InvestorSecondaryController