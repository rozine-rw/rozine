import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
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
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
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
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
export const statement = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement.url(args, options),
    method: 'get',
})

statement.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/wallet/statement.{format}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statement.url = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            format: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        format: args.format,
    }

    return statement.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{format}', parsedArgs.format.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statement.get = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statement.head = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statement.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
const statementForm = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statementForm.get = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statementForm.head = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

statement.form = statementForm

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
*/
export const deposit = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(args, options),
    method: 'post',
})

deposit.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/wallet/deposits',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
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
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
*/
deposit.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
*/
const depositForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
*/
depositForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit.url(args, options),
    method: 'post',
})

deposit.form = depositForm

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
export const withdraw = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw.url(args, options),
    method: 'post',
})

withdraw.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/wallet/withdrawals',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
withdraw.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return withdraw.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
withdraw.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
const withdrawForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdraw.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
withdrawForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdraw.url(args, options),
    method: 'post',
})

withdraw.form = withdrawForm

const wallet = {
    show: Object.assign(show, show),
    statement: Object.assign(statement, statement),
    deposit: Object.assign(deposit, deposit),
    withdraw: Object.assign(withdraw, withdraw),
    operations: Object.assign(operations, operations),
}

export default wallet