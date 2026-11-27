import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::halt
* @see app/Http/Controllers/StaffSecondaryMarketController.php:34
* @route '/admin/secondary-market/halt'
*/
export const halt = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: halt.url(options),
    method: 'post',
})

halt.definition = {
    methods: ["post"],
    url: '/admin/secondary-market/halt',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::halt
* @see app/Http/Controllers/StaffSecondaryMarketController.php:34
* @route '/admin/secondary-market/halt'
*/
halt.url = (options?: RouteQueryOptions) => {
    return halt.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::halt
* @see app/Http/Controllers/StaffSecondaryMarketController.php:34
* @route '/admin/secondary-market/halt'
*/
halt.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: halt.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::halt
* @see app/Http/Controllers/StaffSecondaryMarketController.php:34
* @route '/admin/secondary-market/halt'
*/
const haltForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: halt.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::halt
* @see app/Http/Controllers/StaffSecondaryMarketController.php:34
* @route '/admin/secondary-market/halt'
*/
haltForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: halt.url(options),
    method: 'post',
})

halt.form = haltForm

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::lift
* @see app/Http/Controllers/StaffSecondaryMarketController.php:40
* @route '/admin/secondary-market/lift'
*/
export const lift = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lift.url(options),
    method: 'post',
})

lift.definition = {
    methods: ["post"],
    url: '/admin/secondary-market/lift',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::lift
* @see app/Http/Controllers/StaffSecondaryMarketController.php:40
* @route '/admin/secondary-market/lift'
*/
lift.url = (options?: RouteQueryOptions) => {
    return lift.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::lift
* @see app/Http/Controllers/StaffSecondaryMarketController.php:40
* @route '/admin/secondary-market/lift'
*/
lift.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lift.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::lift
* @see app/Http/Controllers/StaffSecondaryMarketController.php:40
* @route '/admin/secondary-market/lift'
*/
const liftForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lift.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::lift
* @see app/Http/Controllers/StaffSecondaryMarketController.php:40
* @route '/admin/secondary-market/lift'
*/
liftForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lift.url(options),
    method: 'post',
})

lift.form = liftForm

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::cancel
* @see app/Http/Controllers/StaffSecondaryMarketController.php:46
* @route '/admin/secondary-market/listings/{listing}/cancel'
*/
export const cancel = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

cancel.definition = {
    methods: ["post"],
    url: '/admin/secondary-market/listings/{listing}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::cancel
* @see app/Http/Controllers/StaffSecondaryMarketController.php:46
* @route '/admin/secondary-market/listings/{listing}/cancel'
*/
cancel.url = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { listing: args }
    }

    if (Array.isArray(args)) {
        args = {
            listing: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        listing: args.listing,
    }

    return cancel.definition.url
            .replace('{listing}', parsedArgs.listing.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::cancel
* @see app/Http/Controllers/StaffSecondaryMarketController.php:46
* @route '/admin/secondary-market/listings/{listing}/cancel'
*/
cancel.post = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::cancel
* @see app/Http/Controllers/StaffSecondaryMarketController.php:46
* @route '/admin/secondary-market/listings/{listing}/cancel'
*/
const cancelForm = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::cancel
* @see app/Http/Controllers/StaffSecondaryMarketController.php:46
* @route '/admin/secondary-market/listings/{listing}/cancel'
*/
cancelForm.post = (args: { listing: string | number } | [listing: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

cancel.form = cancelForm

const StaffSecondaryMarketController = { halt, lift, cancel }

export default StaffSecondaryMarketController