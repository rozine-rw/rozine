import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flash
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:135
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
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:135
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
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:135
* @route '/admin/businesses/{business}/flash-audit'
*/
flash.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: flash.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flash
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:135
* @route '/admin/businesses/{business}/flash-audit'
*/
const flashForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: flash.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flash
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:135
* @route '/admin/businesses/{business}/flash-audit'
*/
flashForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: flash.url(args, options),
    method: 'post',
})

flash.form = flashForm

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatch
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:148
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
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:148
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
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:148
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
redispatch.post = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redispatch.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatch
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:148
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
const redispatchForm = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redispatch.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatch
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:148
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
redispatchForm.post = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redispatch.url(args, options),
    method: 'post',
})

redispatch.form = redispatchForm

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::close
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:157
* @route '/admin/businesses/{business}/audits/{assignment}/close'
*/
export const close = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/admin/businesses/{business}/audits/{assignment}/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::close
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:157
* @route '/admin/businesses/{business}/audits/{assignment}/close'
*/
close.url = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions) => {
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

    return close.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::close
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:157
* @route '/admin/businesses/{business}/audits/{assignment}/close'
*/
close.post = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::close
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:157
* @route '/admin/businesses/{business}/audits/{assignment}/close'
*/
const closeForm = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::close
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:157
* @route '/admin/businesses/{business}/audits/{assignment}/close'
*/
closeForm.post = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(args, options),
    method: 'post',
})

close.form = closeForm

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::independence
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:166
* @route '/admin/businesses/{business}/auditors/{party}/independence'
*/
export const independence = (args: { business: string | number, party: string | number } | [business: string | number, party: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: independence.url(args, options),
    method: 'post',
})

independence.definition = {
    methods: ["post"],
    url: '/admin/businesses/{business}/auditors/{party}/independence',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::independence
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:166
* @route '/admin/businesses/{business}/auditors/{party}/independence'
*/
independence.url = (args: { business: string | number, party: string | number } | [business: string | number, party: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            party: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        party: args.party,
    }

    return independence.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::independence
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:166
* @route '/admin/businesses/{business}/auditors/{party}/independence'
*/
independence.post = (args: { business: string | number, party: string | number } | [business: string | number, party: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: independence.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::independence
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:166
* @route '/admin/businesses/{business}/auditors/{party}/independence'
*/
const independenceForm = (args: { business: string | number, party: string | number } | [business: string | number, party: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: independence.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::independence
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:166
* @route '/admin/businesses/{business}/auditors/{party}/independence'
*/
independenceForm.post = (args: { business: string | number, party: string | number } | [business: string | number, party: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: independence.url(args, options),
    method: 'post',
})

independence.form = independenceForm

const audit = {
    flash: Object.assign(flash, flash),
    redispatch: Object.assign(redispatch, redispatch),
    close: Object.assign(close, close),
    independence: Object.assign(independence, independence),
}

export default audit