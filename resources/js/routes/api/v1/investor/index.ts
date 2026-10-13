import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
import dealsC9d3dc from './deals'
import wallet0fdd46 from './wallet'
import risks from './risks'
import notices5868c6 from './notices'
import primary from './primary'
import commitments from './commitments'
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
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
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
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
wallet.url = (options?: RouteQueryOptions) => {
    return wallet.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
wallet.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
wallet.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: wallet.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
const walletForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
walletForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
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
* @see app/Http/Controllers/InvestorVerificationController.php:40
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
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
verification.url = (options?: RouteQueryOptions) => {
    return verification.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
verification.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verification.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
verification.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: verification.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
const verificationForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verification.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
verificationForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verification.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
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
    wallet: Object.assign(wallet, wallet0fdd46),
    risks: Object.assign(risks, risks),
    notices: Object.assign(notices, notices5868c6),
    primary: Object.assign(primary, primary),
    commitments: Object.assign(commitments, commitments),
    verification: Object.assign(verification, verificationAf8c0f),
}

export default investor