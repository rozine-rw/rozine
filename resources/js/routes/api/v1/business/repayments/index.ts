import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:34
* @route '/api/v1/business/{business}/repayments'
*/
export const pay = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay.url(args, options),
    method: 'post',
})

pay.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/repayments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:34
* @route '/api/v1/business/{business}/repayments'
*/
pay.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return pay.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:34
* @route '/api/v1/business/{business}/repayments'
*/
pay.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:34
* @route '/api/v1/business/{business}/repayments'
*/
const payForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:34
* @route '/api/v1/business/{business}/repayments'
*/
payForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay.url(args, options),
    method: 'post',
})

pay.form = payForm

const repayments = {
    pay: Object.assign(pay, pay),
    operations: Object.assign(operations, operations),
}

export default repayments