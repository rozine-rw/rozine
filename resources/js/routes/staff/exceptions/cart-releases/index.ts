import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
export const retry = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retry.url(args, options),
    method: 'post',
})

retry.definition = {
    methods: ["post"],
    url: '/admin/exceptions/cart-releases/{checkout}/retry',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
retry.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { checkout: args }
    }

    if (Array.isArray(args)) {
        args = {
            checkout: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        checkout: args.checkout,
    }

    return retry.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
retry.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retry.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
const retryForm = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: retry.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
retryForm.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: retry.url(args, options),
    method: 'post',
})

retry.form = retryForm

const cartReleases = {
    retry: Object.assign(retry, retry),
}

export default cartReleases