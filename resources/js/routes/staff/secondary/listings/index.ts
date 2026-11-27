import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
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

const listings = {
    cancel: Object.assign(cancel, cancel),
}

export default listings