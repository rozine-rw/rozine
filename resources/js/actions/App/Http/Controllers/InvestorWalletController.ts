import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
const show3c9a2007c468f412440bc67237ebcaa4 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3c9a2007c468f412440bc67237ebcaa4.url(options),
    method: 'get',
})

show3c9a2007c468f412440bc67237ebcaa4.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
show3c9a2007c468f412440bc67237ebcaa4.url = (options?: RouteQueryOptions) => {
    return show3c9a2007c468f412440bc67237ebcaa4.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
show3c9a2007c468f412440bc67237ebcaa4.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3c9a2007c468f412440bc67237ebcaa4.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
show3c9a2007c468f412440bc67237ebcaa4.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show3c9a2007c468f412440bc67237ebcaa4.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
const show3c9a2007c468f412440bc67237ebcaa4Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show3c9a2007c468f412440bc67237ebcaa4.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
show3c9a2007c468f412440bc67237ebcaa4Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show3c9a2007c468f412440bc67237ebcaa4.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/api/v1/investor/wallet'
*/
show3c9a2007c468f412440bc67237ebcaa4Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show3c9a2007c468f412440bc67237ebcaa4.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show3c9a2007c468f412440bc67237ebcaa4.form = show3c9a2007c468f412440bc67237ebcaa4Form
/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
const show4671c6f2d06d3b1a1756b850eef449a4 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show4671c6f2d06d3b1a1756b850eef449a4.url(options),
    method: 'get',
})

show4671c6f2d06d3b1a1756b850eef449a4.definition = {
    methods: ["get","head"],
    url: '/investor/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
show4671c6f2d06d3b1a1756b850eef449a4.url = (options?: RouteQueryOptions) => {
    return show4671c6f2d06d3b1a1756b850eef449a4.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
show4671c6f2d06d3b1a1756b850eef449a4.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show4671c6f2d06d3b1a1756b850eef449a4.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
show4671c6f2d06d3b1a1756b850eef449a4.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show4671c6f2d06d3b1a1756b850eef449a4.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
const show4671c6f2d06d3b1a1756b850eef449a4Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show4671c6f2d06d3b1a1756b850eef449a4.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
show4671c6f2d06d3b1a1756b850eef449a4Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show4671c6f2d06d3b1a1756b850eef449a4.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::show
* @see app/Http/Controllers/InvestorWalletController.php:25
* @route '/investor/wallet'
*/
show4671c6f2d06d3b1a1756b850eef449a4Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show4671c6f2d06d3b1a1756b850eef449a4.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show4671c6f2d06d3b1a1756b850eef449a4.form = show4671c6f2d06d3b1a1756b850eef449a4Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorWalletController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/investor/wallet': show3c9a2007c468f412440bc67237ebcaa4,
    '/investor/wallet': show4671c6f2d06d3b1a1756b850eef449a4,
}

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/api/v1/investor/wallet/deposits'
*/
const depositae3238b5c3bb70f8cceb8f3faa5c4434 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: depositae3238b5c3bb70f8cceb8f3faa5c4434.url(options),
    method: 'post',
})

depositae3238b5c3bb70f8cceb8f3faa5c4434.definition = {
    methods: ["post"],
    url: '/api/v1/investor/wallet/deposits',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/api/v1/investor/wallet/deposits'
*/
depositae3238b5c3bb70f8cceb8f3faa5c4434.url = (options?: RouteQueryOptions) => {
    return depositae3238b5c3bb70f8cceb8f3faa5c4434.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/api/v1/investor/wallet/deposits'
*/
depositae3238b5c3bb70f8cceb8f3faa5c4434.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: depositae3238b5c3bb70f8cceb8f3faa5c4434.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/api/v1/investor/wallet/deposits'
*/
const depositae3238b5c3bb70f8cceb8f3faa5c4434Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: depositae3238b5c3bb70f8cceb8f3faa5c4434.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/api/v1/investor/wallet/deposits'
*/
depositae3238b5c3bb70f8cceb8f3faa5c4434Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: depositae3238b5c3bb70f8cceb8f3faa5c4434.url(options),
    method: 'post',
})

depositae3238b5c3bb70f8cceb8f3faa5c4434.form = depositae3238b5c3bb70f8cceb8f3faa5c4434Form
/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/investor/wallet/deposits'
*/
const deposit9dabf7c7723af4e74eb0694975135af9 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit9dabf7c7723af4e74eb0694975135af9.url(options),
    method: 'post',
})

deposit9dabf7c7723af4e74eb0694975135af9.definition = {
    methods: ["post"],
    url: '/investor/wallet/deposits',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/investor/wallet/deposits'
*/
deposit9dabf7c7723af4e74eb0694975135af9.url = (options?: RouteQueryOptions) => {
    return deposit9dabf7c7723af4e74eb0694975135af9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/investor/wallet/deposits'
*/
deposit9dabf7c7723af4e74eb0694975135af9.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit9dabf7c7723af4e74eb0694975135af9.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/investor/wallet/deposits'
*/
const deposit9dabf7c7723af4e74eb0694975135af9Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit9dabf7c7723af4e74eb0694975135af9.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:35
* @route '/investor/wallet/deposits'
*/
deposit9dabf7c7723af4e74eb0694975135af9Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit9dabf7c7723af4e74eb0694975135af9.url(options),
    method: 'post',
})

deposit9dabf7c7723af4e74eb0694975135af9.form = deposit9dabf7c7723af4e74eb0694975135af9Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorWalletController::deposit, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `deposit['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const deposit = {
    '/api/v1/investor/wallet/deposits': depositae3238b5c3bb70f8cceb8f3faa5c4434,
    '/investor/wallet/deposits': deposit9dabf7c7723af4e74eb0694975135af9,
}

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/api/v1/investor/wallet-operations/{request_id}'
*/
const operationb23efe0fdae3213a4d89cbf03d1b2cc1 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationb23efe0fdae3213a4d89cbf03d1b2cc1.url(args, options),
    method: 'get',
})

operationb23efe0fdae3213a4d89cbf03d1b2cc1.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/wallet-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/api/v1/investor/wallet-operations/{request_id}'
*/
operationb23efe0fdae3213a4d89cbf03d1b2cc1.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return operationb23efe0fdae3213a4d89cbf03d1b2cc1.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/api/v1/investor/wallet-operations/{request_id}'
*/
operationb23efe0fdae3213a4d89cbf03d1b2cc1.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationb23efe0fdae3213a4d89cbf03d1b2cc1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/api/v1/investor/wallet-operations/{request_id}'
*/
operationb23efe0fdae3213a4d89cbf03d1b2cc1.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operationb23efe0fdae3213a4d89cbf03d1b2cc1.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/api/v1/investor/wallet-operations/{request_id}'
*/
const operationb23efe0fdae3213a4d89cbf03d1b2cc1Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationb23efe0fdae3213a4d89cbf03d1b2cc1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/api/v1/investor/wallet-operations/{request_id}'
*/
operationb23efe0fdae3213a4d89cbf03d1b2cc1Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationb23efe0fdae3213a4d89cbf03d1b2cc1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/api/v1/investor/wallet-operations/{request_id}'
*/
operationb23efe0fdae3213a4d89cbf03d1b2cc1Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationb23efe0fdae3213a4d89cbf03d1b2cc1.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operationb23efe0fdae3213a4d89cbf03d1b2cc1.form = operationb23efe0fdae3213a4d89cbf03d1b2cc1Form
/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/investor/wallet-operations/{request_id}'
*/
const operationb844d10b7ae7ce79217f9292aff548a8 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationb844d10b7ae7ce79217f9292aff548a8.url(args, options),
    method: 'get',
})

operationb844d10b7ae7ce79217f9292aff548a8.definition = {
    methods: ["get","head"],
    url: '/investor/wallet-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/investor/wallet-operations/{request_id}'
*/
operationb844d10b7ae7ce79217f9292aff548a8.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return operationb844d10b7ae7ce79217f9292aff548a8.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/investor/wallet-operations/{request_id}'
*/
operationb844d10b7ae7ce79217f9292aff548a8.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationb844d10b7ae7ce79217f9292aff548a8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/investor/wallet-operations/{request_id}'
*/
operationb844d10b7ae7ce79217f9292aff548a8.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operationb844d10b7ae7ce79217f9292aff548a8.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/investor/wallet-operations/{request_id}'
*/
const operationb844d10b7ae7ce79217f9292aff548a8Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationb844d10b7ae7ce79217f9292aff548a8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/investor/wallet-operations/{request_id}'
*/
operationb844d10b7ae7ce79217f9292aff548a8Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationb844d10b7ae7ce79217f9292aff548a8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::operation
* @see app/Http/Controllers/InvestorWalletController.php:44
* @route '/investor/wallet-operations/{request_id}'
*/
operationb844d10b7ae7ce79217f9292aff548a8Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationb844d10b7ae7ce79217f9292aff548a8.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operationb844d10b7ae7ce79217f9292aff548a8.form = operationb844d10b7ae7ce79217f9292aff548a8Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorWalletController::operation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `operation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const operation = {
    '/api/v1/investor/wallet-operations/{request_id}': operationb23efe0fdae3213a4d89cbf03d1b2cc1,
    '/investor/wallet-operations/{request_id}': operationb844d10b7ae7ce79217f9292aff548a8,
}

const InvestorWalletController = { show, deposit, operation }

export default InvestorWalletController