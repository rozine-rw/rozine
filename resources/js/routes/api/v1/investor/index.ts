import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
import wallet0fdd46 from './wallet'
import primary from './primary'
import commitments from './commitments'
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

const investor = {
    wallet: Object.assign(wallet, wallet0fdd46),
    primary: Object.assign(primary, primary),
    commitments: Object.assign(commitments, commitments),
}

export default investor