import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import listings from './listings'
/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::halt
* @see app/Http/Controllers/StaffSecondaryMarketController.php:42
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
* @see app/Http/Controllers/StaffSecondaryMarketController.php:42
* @route '/admin/secondary-market/halt'
*/
halt.url = (options?: RouteQueryOptions) => {
    return halt.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::halt
* @see app/Http/Controllers/StaffSecondaryMarketController.php:42
* @route '/admin/secondary-market/halt'
*/
halt.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: halt.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::halt
* @see app/Http/Controllers/StaffSecondaryMarketController.php:42
* @route '/admin/secondary-market/halt'
*/
const haltForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: halt.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::halt
* @see app/Http/Controllers/StaffSecondaryMarketController.php:42
* @route '/admin/secondary-market/halt'
*/
haltForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: halt.url(options),
    method: 'post',
})

halt.form = haltForm

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::lift
* @see app/Http/Controllers/StaffSecondaryMarketController.php:48
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
* @see app/Http/Controllers/StaffSecondaryMarketController.php:48
* @route '/admin/secondary-market/lift'
*/
lift.url = (options?: RouteQueryOptions) => {
    return lift.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::lift
* @see app/Http/Controllers/StaffSecondaryMarketController.php:48
* @route '/admin/secondary-market/lift'
*/
lift.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lift.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::lift
* @see app/Http/Controllers/StaffSecondaryMarketController.php:48
* @route '/admin/secondary-market/lift'
*/
const liftForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lift.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffSecondaryMarketController::lift
* @see app/Http/Controllers/StaffSecondaryMarketController.php:48
* @route '/admin/secondary-market/lift'
*/
liftForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lift.url(options),
    method: 'post',
})

lift.form = liftForm

const secondary = {
    halt: Object.assign(halt, halt),
    lift: Object.assign(lift, lift),
    listings: Object.assign(listings, listings),
}

export default secondary