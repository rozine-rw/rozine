import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\InvestorWalletController::statement
* @see app/Http/Controllers/InvestorWalletController.php:40
* @route '/api/v1/investor/wallet/statement.{format}'
*/
export const statement = (args: { format: string | number } | [format: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement.url(args, options),
    method: 'get',
})

statement.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/wallet/statement.{format}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::statement
* @see app/Http/Controllers/InvestorWalletController.php:40
* @route '/api/v1/investor/wallet/statement.{format}'
*/
statement.url = (args: { format: string | number } | [format: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { format: args }
    }

    if (Array.isArray(args)) {
        args = {
            format: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        format: args.format,
    }

    return statement.definition.url
            .replace('{format}', parsedArgs.format.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::statement
* @see app/Http/Controllers/InvestorWalletController.php:40
* @route '/api/v1/investor/wallet/statement.{format}'
*/
statement.get = (args: { format: string | number } | [format: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::statement
* @see app/Http/Controllers/InvestorWalletController.php:40
* @route '/api/v1/investor/wallet/statement.{format}'
*/
statement.head = (args: { format: string | number } | [format: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statement.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::statement
* @see app/Http/Controllers/InvestorWalletController.php:40
* @route '/api/v1/investor/wallet/statement.{format}'
*/
const statementForm = (args: { format: string | number } | [format: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::statement
* @see app/Http/Controllers/InvestorWalletController.php:40
* @route '/api/v1/investor/wallet/statement.{format}'
*/
statementForm.get = (args: { format: string | number } | [format: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::statement
* @see app/Http/Controllers/InvestorWalletController.php:40
* @route '/api/v1/investor/wallet/statement.{format}'
*/
statementForm.head = (args: { format: string | number } | [format: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:57
* @route '/api/v1/investor/wallet/deposits'
*/
export const deposit = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(options),
    method: 'post',
})

deposit.definition = {
    methods: ["post"],
    url: '/api/v1/investor/wallet/deposits',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:57
* @route '/api/v1/investor/wallet/deposits'
*/
deposit.url = (options?: RouteQueryOptions) => {
    return deposit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:57
* @route '/api/v1/investor/wallet/deposits'
*/
deposit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:57
* @route '/api/v1/investor/wallet/deposits'
*/
const depositForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorWalletController::deposit
* @see app/Http/Controllers/InvestorWalletController.php:57
* @route '/api/v1/investor/wallet/deposits'
*/
depositForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit.url(options),
    method: 'post',
})

deposit.form = depositForm

const wallet = {
    statement: Object.assign(statement, statement),
    deposit: Object.assign(deposit, deposit),
    operations: Object.assign(operations, operations),
}

export default wallet