import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:32
* @route '/investor/wallet/deposits'
*/
export const deposit = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(options),
    method: 'post',
})

deposit.definition = {
    methods: ["post"],
    url: '/investor/wallet/deposits',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:32
* @route '/investor/wallet/deposits'
*/
deposit.url = (options?: RouteQueryOptions) => {
    return deposit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:32
* @route '/investor/wallet/deposits'
*/
deposit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:32
* @route '/investor/wallet/deposits'
*/
const depositForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:32
* @route '/investor/wallet/deposits'
*/
depositForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit.url(options),
    method: 'post',
})

deposit.form = depositForm

const wallet = {
    deposit: Object.assign(deposit, deposit),
    operations: Object.assign(operations, operations),
}

export default wallet