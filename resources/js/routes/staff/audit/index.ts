import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import operations from './operations'
import disputes from './disputes'
/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
export const show = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/admin/audit-assignments/{assignment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
show.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { assignment: args }
    }

    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
    }

    return show.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
show.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
show.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
const showForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
showForm.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
showForm.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\AuditOperationsController::redispatch
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
export const redispatch = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redispatch.url(args, options),
    method: 'post',
})

redispatch.definition = {
    methods: ["post"],
    url: '/admin/audit-assignments/{assignment}/redispatch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::redispatch
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
redispatch.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { assignment: args }
    }

    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
    }

    return redispatch.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::redispatch
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
redispatch.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redispatch.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::redispatch
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
const redispatchForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redispatch.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::redispatch
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
redispatchForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redispatch.url(args, options),
    method: 'post',
})

redispatch.form = redispatchForm

/**
* @see \App\Http\Controllers\AuditOperationsController::close
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
export const close = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/admin/audit-assignments/{assignment}/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::close
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
close.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { assignment: args }
    }

    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
    }

    return close.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::close
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
close.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::close
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
const closeForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::close
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
closeForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(args, options),
    method: 'post',
})

close.form = closeForm

const audit = {
    operations: Object.assign(operations, operations),
    disputes: Object.assign(disputes, disputes),
    show: Object.assign(show, show),
    redispatch: Object.assign(redispatch, redispatch),
    close: Object.assign(close, close),
}

export default audit