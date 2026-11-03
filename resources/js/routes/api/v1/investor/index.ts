import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
import dealsC9d3dc from './deals'
import wallet0fdd46 from './wallet'
import risks from './risks'
import notices5868c6 from './notices'
import primary from './primary'
import commitments from './commitments'
import holdings from './holdings'
import verificationAf8c0f from './verification'
/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
export const deals = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: deals.url(options),
    method: 'get',
})

deals.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
deals.url = (options?: RouteQueryOptions) => {
    return deals.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
deals.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: deals.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
deals.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: deals.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
const dealsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deals.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
dealsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deals.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
dealsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deals.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

deals.form = dealsForm

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
export const checkout = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkout.url(args, options),
    method: 'get',
})

checkout.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals/{campaign}/checkout',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkout.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return checkout.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkout.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkout.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkout.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: checkout.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
const checkoutForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkout.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkoutForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkout.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkoutForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkout.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

checkout.form = checkoutForm

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_add
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
export const cart_add = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_add.url(args, options),
    method: 'post',
})

cart_add.definition = {
    methods: ["post"],
    url: '/api/v1/investor/deals/{campaign}/cart',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_add
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
cart_add.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return cart_add.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_add
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
cart_add.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_add.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_add
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
const cart_addForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_add.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_add
* @see app/Http/Controllers/InvestorMarketController.php:38
* @route '/api/v1/investor/deals/{campaign}/cart'
*/
cart_addForm.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_add.url(args, options),
    method: 'post',
})

cart_add.form = cart_addForm

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_units
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
export const cart_units = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_units.url(args, options),
    method: 'post',
})

cart_units.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/{campaign}/units',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_units
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
cart_units.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return cart_units.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_units
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
cart_units.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_units.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_units
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
const cart_unitsForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_units.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_units
* @see app/Http/Controllers/InvestorMarketController.php:44
* @route '/api/v1/investor/cart/{campaign}/units'
*/
cart_unitsForm.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_units.url(args, options),
    method: 'post',
})

cart_units.form = cart_unitsForm

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_remove
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
export const cart_remove = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_remove.url(args, options),
    method: 'post',
})

cart_remove.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/{campaign}/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_remove
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
cart_remove.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return cart_remove.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_remove
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
cart_remove.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_remove.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_remove
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
const cart_removeForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_remove.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart_remove
* @see app/Http/Controllers/InvestorMarketController.php:50
* @route '/api/v1/investor/cart/{campaign}/remove'
*/
cart_removeForm.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_remove.url(args, options),
    method: 'post',
})

cart_remove.form = cart_removeForm

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
export const cart_quote = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_quote.url(options),
    method: 'post',
})

cart_quote.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/quote',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
cart_quote.url = (options?: RouteQueryOptions) => {
    return cart_quote.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
cart_quote.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_quote.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
const cart_quoteForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_quote.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_quote
* @see app/Http/Controllers/InvestorCartCheckoutController.php:27
* @route '/api/v1/investor/cart/quote'
*/
cart_quoteForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_quote.url(options),
    method: 'post',
})

cart_quote.form = cart_quoteForm

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
export const cart_reserve = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_reserve.url(options),
    method: 'post',
})

cart_reserve.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/reservations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
cart_reserve.url = (options?: RouteQueryOptions) => {
    return cart_reserve.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
cart_reserve.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_reserve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
const cart_reserveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_reserve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reserve
* @see app/Http/Controllers/InvestorCartCheckoutController.php:33
* @route '/api/v1/investor/cart/reservations'
*/
cart_reserveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_reserve.url(options),
    method: 'post',
})

cart_reserve.form = cart_reserveForm

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
export const cart_reservation = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart_reservation.url(args, options),
    method: 'get',
})

cart_reservation.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/cart/reservations/{checkout}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
cart_reservation.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return cart_reservation.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
cart_reservation.get = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart_reservation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
cart_reservation.head = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cart_reservation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
const cart_reservationForm = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart_reservation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
cart_reservationForm.get = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart_reservation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_reservation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:40
* @route '/api/v1/investor/cart/reservations/{checkout}'
*/
cart_reservationForm.head = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart_reservation.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

cart_reservation.form = cart_reservationForm

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
export const cart_confirm = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_confirm.url(args, options),
    method: 'post',
})

cart_confirm.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/reservations/{checkout}/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
cart_confirm.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return cart_confirm.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
cart_confirm.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_confirm.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
const cart_confirmForm = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_confirm.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_confirm
* @see app/Http/Controllers/InvestorCartCheckoutController.php:45
* @route '/api/v1/investor/cart/reservations/{checkout}/confirm'
*/
cart_confirmForm.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_confirm.url(args, options),
    method: 'post',
})

cart_confirm.form = cart_confirmForm

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
export const cart_release = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_release.url(args, options),
    method: 'post',
})

cart_release.definition = {
    methods: ["post"],
    url: '/api/v1/investor/cart/reservations/{checkout}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
cart_release.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return cart_release.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
cart_release.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cart_release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
const cart_releaseForm = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_release
* @see app/Http/Controllers/InvestorCartCheckoutController.php:52
* @route '/api/v1/investor/cart/reservations/{checkout}/release'
*/
cart_releaseForm.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cart_release.url(args, options),
    method: 'post',
})

cart_release.form = cart_releaseForm

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
export const cart_operation = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart_operation.url(args, options),
    method: 'get',
})

cart_operation.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/cart/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
cart_operation.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { request_id: args }
    }

    if (Array.isArray(args)) {
        args = {
            request_id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        request_id: args.request_id,
    }

    return cart_operation.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
cart_operation.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart_operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
cart_operation.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cart_operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
const cart_operationForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart_operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
cart_operationForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart_operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorCartCheckoutController::cart_operation
* @see app/Http/Controllers/InvestorCartCheckoutController.php:58
* @route '/api/v1/investor/cart/operations/{request_id}'
*/
cart_operationForm.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart_operation.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

cart_operation.form = cart_operationForm

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:31
* @route '/api/v1/investor/wallet'
*/
export const wallet = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: wallet.url(options),
    method: 'get',
})

wallet.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:31
* @route '/api/v1/investor/wallet'
*/
wallet.url = (options?: RouteQueryOptions) => {
    return wallet.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:31
* @route '/api/v1/investor/wallet'
*/
wallet.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:31
* @route '/api/v1/investor/wallet'
*/
wallet.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: wallet.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:31
* @route '/api/v1/investor/wallet'
*/
const walletForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:31
* @route '/api/v1/investor/wallet'
*/
walletForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:31
* @route '/api/v1/investor/wallet'
*/
walletForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

wallet.form = walletForm

/**
* @see \App\Http\Controllers\InvestorNoticesController::notices
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
export const notices = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notices.url(options),
    method: 'get',
})

notices.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/notices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorNoticesController::notices
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
notices.url = (options?: RouteQueryOptions) => {
    return notices.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::notices
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
notices.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::notices
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
notices.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notices.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::notices
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
const noticesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::notices
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
noticesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::notices
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
noticesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notices.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

notices.form = noticesForm

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:43
* @route '/api/v1/investor/verification'
*/
export const verification = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verification.url(options),
    method: 'get',
})

verification.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/verification',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:43
* @route '/api/v1/investor/verification'
*/
verification.url = (options?: RouteQueryOptions) => {
    return verification.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:43
* @route '/api/v1/investor/verification'
*/
verification.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verification.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:43
* @route '/api/v1/investor/verification'
*/
verification.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: verification.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:43
* @route '/api/v1/investor/verification'
*/
const verificationForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verification.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:43
* @route '/api/v1/investor/verification'
*/
verificationForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verification.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:43
* @route '/api/v1/investor/verification'
*/
verificationForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verification.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

verification.form = verificationForm

const investor = {
    deals: Object.assign(deals, dealsC9d3dc),
    checkout: Object.assign(checkout, checkout),
    cart_add: Object.assign(cart_add, cart_add),
    cart_units: Object.assign(cart_units, cart_units),
    cart_remove: Object.assign(cart_remove, cart_remove),
    cart_quote: Object.assign(cart_quote, cart_quote),
    cart_reserve: Object.assign(cart_reserve, cart_reserve),
    cart_reservation: Object.assign(cart_reservation, cart_reservation),
    cart_confirm: Object.assign(cart_confirm, cart_confirm),
    cart_release: Object.assign(cart_release, cart_release),
    cart_operation: Object.assign(cart_operation, cart_operation),
    wallet: Object.assign(wallet, wallet0fdd46),
    risks: Object.assign(risks, risks),
    notices: Object.assign(notices, notices5868c6),
    primary: Object.assign(primary, primary),
    commitments: Object.assign(commitments, commitments),
    holdings: Object.assign(holdings, holdings),
    verification: Object.assign(verification, verificationAf8c0f),
}

export default investor