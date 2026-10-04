import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:27
* @route '/investor/deals/{campaign}/reservations'
*/
export const reserve = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve.url(args, options),
    method: 'post',
})

reserve.definition = {
    methods: ["post"],
    url: '/investor/deals/{campaign}/reservations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:27
* @route '/investor/deals/{campaign}/reservations'
*/
reserve.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return reserve.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:27
* @route '/investor/deals/{campaign}/reservations'
*/
reserve.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:27
* @route '/investor/deals/{campaign}/reservations'
*/
const reserveForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:27
* @route '/investor/deals/{campaign}/reservations'
*/
reserveForm.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve.url(args, options),
    method: 'post',
})

reserve.form = reserveForm

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:34
* @route '/investor/reservations/{reservation}/confirm'
*/
export const confirm = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(args, options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/investor/reservations/{reservation}/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:34
* @route '/investor/reservations/{reservation}/confirm'
*/
confirm.url = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reservation: args }
    }

    if (Array.isArray(args)) {
        args = {
            reservation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reservation: args.reservation,
    }

    return confirm.definition.url
            .replace('{reservation}', parsedArgs.reservation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:34
* @route '/investor/reservations/{reservation}/confirm'
*/
confirm.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:34
* @route '/investor/reservations/{reservation}/confirm'
*/
const confirmForm = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:34
* @route '/investor/reservations/{reservation}/confirm'
*/
confirmForm.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(args, options),
    method: 'post',
})

confirm.form = confirmForm

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:41
* @route '/investor/reservations/{reservation}/release'
*/
export const release = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

release.definition = {
    methods: ["post"],
    url: '/investor/reservations/{reservation}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:41
* @route '/investor/reservations/{reservation}/release'
*/
release.url = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reservation: args }
    }

    if (Array.isArray(args)) {
        args = {
            reservation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reservation: args.reservation,
    }

    return release.definition.url
            .replace('{reservation}', parsedArgs.reservation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:41
* @route '/investor/reservations/{reservation}/release'
*/
release.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:41
* @route '/investor/reservations/{reservation}/release'
*/
const releaseForm = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:41
* @route '/investor/reservations/{reservation}/release'
*/
releaseForm.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release.url(args, options),
    method: 'post',
})

release.form = releaseForm

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:47
* @route '/investor/commitments/{commitment}/cancel'
*/
export const cancel = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

cancel.definition = {
    methods: ["post"],
    url: '/investor/commitments/{commitment}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:47
* @route '/investor/commitments/{commitment}/cancel'
*/
cancel.url = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { commitment: args }
    }

    if (Array.isArray(args)) {
        args = {
            commitment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        commitment: args.commitment,
    }

    return cancel.definition.url
            .replace('{commitment}', parsedArgs.commitment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:47
* @route '/investor/commitments/{commitment}/cancel'
*/
cancel.post = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:47
* @route '/investor/commitments/{commitment}/cancel'
*/
const cancelForm = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:47
* @route '/investor/commitments/{commitment}/cancel'
*/
cancelForm.post = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

cancel.form = cancelForm

const primary = {
    reserve: Object.assign(reserve, reserve),
    confirm: Object.assign(confirm, confirm),
    release: Object.assign(release, release),
    cancel: Object.assign(cancel, cancel),
    operations: Object.assign(operations, operations),
}

export default primary