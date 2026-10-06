import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import dealsC9d3dc from './deals'
import verificationAf8c0f from './verification'
import wallet0fdd46 from './wallet'
import primary from './primary'
import commitments from './commitments'
/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/investor'
*/
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/investor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/investor'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/investor'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/investor'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/investor'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/investor'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/investor'
*/
homeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

home.form = homeForm

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
export const deals = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: deals.url(options),
    method: 'get',
})

deals.definition = {
    methods: ["get","head"],
    url: '/investor/deals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
deals.url = (options?: RouteQueryOptions) => {
    return deals.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
deals.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: deals.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
deals.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: deals.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
const dealsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deals.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
dealsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deals.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::deals
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
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
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
export const verification = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verification.url(options),
    method: 'get',
})

verification.definition = {
    methods: ["get","head"],
    url: '/investor/verification',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
verification.url = (options?: RouteQueryOptions) => {
    return verification.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
verification.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verification.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
verification.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: verification.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
const verificationForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verification.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
verificationForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verification.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::verification
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
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

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
export const wallet = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: wallet.url(options),
    method: 'get',
})

wallet.definition = {
    methods: ["get","head"],
    url: '/investor/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
wallet.url = (options?: RouteQueryOptions) => {
    return wallet.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
wallet.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
wallet.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: wallet.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
const walletForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
walletForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::wallet
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
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

const investor = {
    home: Object.assign(home, home),
    deals: Object.assign(deals, dealsC9d3dc),
    verification: Object.assign(verification, verificationAf8c0f),
    wallet: Object.assign(wallet, wallet0fdd46),
    primary: Object.assign(primary, primary),
    commitments: Object.assign(commitments, commitments),
}

export default investor