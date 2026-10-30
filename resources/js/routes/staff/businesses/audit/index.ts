import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flash
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
export const flash = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: flash.url(args, options),
    method: 'post',
})

flash.definition = {
    methods: ["post"],
    url: '/admin/businesses/{business}/flash-audit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flash
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
flash.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return flash.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flash
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
flash.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: flash.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flash
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
const flashForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: flash.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flash
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
flashForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: flash.url(args, options),
    method: 'post',
})

flash.form = flashForm

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatch
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
export const redispatch = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redispatch.url(args, options),
    method: 'post',
})

redispatch.definition = {
    methods: ["post"],
    url: '/admin/businesses/{business}/audits/{assignment}/redispatch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatch
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
redispatch.url = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            assignment: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        assignment: args.assignment,
    }

    return redispatch.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatch
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
redispatch.post = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redispatch.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatch
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
const redispatchForm = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redispatch.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatch
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
redispatchForm.post = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redispatch.url(args, options),
    method: 'post',
})

redispatch.form = redispatchForm

const audit = {
    flash: Object.assign(flash, flash),
    redispatch: Object.assign(redispatch, redispatch),
}

export default audit