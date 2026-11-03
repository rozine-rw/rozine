import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
const StaffCartReleaseController = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StaffCartReleaseController.url(args, options),
    method: 'post',
})

StaffCartReleaseController.definition = {
    methods: ["post"],
    url: '/admin/exceptions/cart-releases/{checkout}/retry',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
StaffCartReleaseController.url = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return StaffCartReleaseController.definition.url
            .replace('{checkout}', parsedArgs.checkout.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
StaffCartReleaseController.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StaffCartReleaseController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
const StaffCartReleaseControllerForm = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: StaffCartReleaseController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffCartReleaseController::__invoke
* @see app/Http/Controllers/StaffCartReleaseController.php:25
* @route '/admin/exceptions/cart-releases/{checkout}/retry'
*/
StaffCartReleaseControllerForm.post = (args: { checkout: string | number } | [checkout: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: StaffCartReleaseController.url(args, options),
    method: 'post',
})

StaffCartReleaseController.form = StaffCartReleaseControllerForm

export default StaffCartReleaseController