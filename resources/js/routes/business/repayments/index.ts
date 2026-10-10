import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\BusinessRepaymentController::show
* @see app/Http/Controllers/BusinessRepaymentController.php:26
* @route '/business/{business}/repayments'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}/repayments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessRepaymentController::show
* @see app/Http/Controllers/BusinessRepaymentController.php:26
* @route '/business/{business}/repayments'
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
* @see \App\Http\Controllers\BusinessRepaymentController::show
* @see app/Http/Controllers/BusinessRepaymentController.php:26
* @route '/business/{business}/repayments'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::show
* @see app/Http/Controllers/BusinessRepaymentController.php:26
* @route '/business/{business}/repayments'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::show
* @see app/Http/Controllers/BusinessRepaymentController.php:26
* @route '/business/{business}/repayments'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::show
* @see app/Http/Controllers/BusinessRepaymentController.php:26
* @route '/business/{business}/repayments'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::show
* @see app/Http/Controllers/BusinessRepaymentController.php:26
* @route '/business/{business}/repayments'
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
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:34
* @route '/business/{business}/repayments'
*/
export const pay = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay.url(args, options),
    method: 'post',
})

pay.definition = {
    methods: ["post"],
    url: '/business/{business}/repayments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:34
* @route '/business/{business}/repayments'
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
* @route '/business/{business}/repayments'
*/
pay.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:34
* @route '/business/{business}/repayments'
*/
const payForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:34
* @route '/business/{business}/repayments'
*/
payForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay.url(args, options),
    method: 'post',
})

pay.form = payForm

const repayments = {
    show: Object.assign(show, show),
    pay: Object.assign(pay, pay),
    operations: Object.assign(operations, operations),
}

export default repayments