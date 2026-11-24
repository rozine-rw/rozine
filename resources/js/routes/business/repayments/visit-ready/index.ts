import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessRepaymentController::tick
* @see app/Http/Controllers/BusinessRepaymentController.php:43
* @route '/business/{business}/repayments/visit-ready'
*/
export const tick = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: tick.url(args, options),
    method: 'post',
})

tick.definition = {
    methods: ["post"],
    url: '/business/{business}/repayments/visit-ready',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessRepaymentController::tick
* @see app/Http/Controllers/BusinessRepaymentController.php:43
* @route '/business/{business}/repayments/visit-ready'
*/
tick.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return tick.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessRepaymentController::tick
* @see app/Http/Controllers/BusinessRepaymentController.php:43
* @route '/business/{business}/repayments/visit-ready'
*/
tick.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: tick.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::tick
* @see app/Http/Controllers/BusinessRepaymentController.php:43
* @route '/business/{business}/repayments/visit-ready'
*/
const tickForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: tick.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::tick
* @see app/Http/Controllers/BusinessRepaymentController.php:43
* @route '/business/{business}/repayments/visit-ready'
*/
tickForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: tick.url(args, options),
    method: 'post',
})

tick.form = tickForm

const visitReady = {
    tick: Object.assign(tick, tick),
}

export default visitReady