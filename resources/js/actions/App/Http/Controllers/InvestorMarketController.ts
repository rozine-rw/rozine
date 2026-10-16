import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:20
* @route '/investor/market'
*/
export const market = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: market.url(options),
    method: 'get',
})

market.definition = {
    methods: ["get","head"],
    url: '/investor/market',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:20
* @route '/investor/market'
*/
market.url = (options?: RouteQueryOptions) => {
    return market.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:20
* @route '/investor/market'
*/
market.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: market.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:20
* @route '/investor/market'
*/
market.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: market.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:20
* @route '/investor/market'
*/
const marketForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: market.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:20
* @route '/investor/market'
*/
marketForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: market.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::market
* @see app/Http/Controllers/InvestorMarketController.php:20
* @route '/investor/market'
*/
marketForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: market.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

market.form = marketForm

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:25
* @route '/investor/cart'
*/
export const cart = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart.url(options),
    method: 'get',
})

cart.definition = {
    methods: ["get","head"],
    url: '/investor/cart',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:25
* @route '/investor/cart'
*/
cart.url = (options?: RouteQueryOptions) => {
    return cart.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:25
* @route '/investor/cart'
*/
cart.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:25
* @route '/investor/cart'
*/
cart.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cart.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:25
* @route '/investor/cart'
*/
const cartForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:25
* @route '/investor/cart'
*/
cartForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorMarketController::cart
* @see app/Http/Controllers/InvestorMarketController.php:25
* @route '/investor/cart'
*/
cartForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

cart.form = cartForm

const InvestorMarketController = { market, cart }

export default InvestorMarketController