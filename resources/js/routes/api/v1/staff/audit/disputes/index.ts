import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
import operations from './operations'
import proofs from './proofs'
/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
export const show = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
show.url = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
    }

    return show.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
show.get = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
show.head = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
const showForm = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showForm.get = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showForm.head = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\AuditDisputeController::escalate
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
export const escalate = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: escalate.url(args, options),
    method: 'post',
})

escalate.definition = {
    methods: ["post"],
    url: '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::escalate
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
escalate.url = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
    }

    return escalate.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::escalate
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
escalate.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: escalate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::escalate
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
const escalateForm = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: escalate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::escalate
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
escalateForm.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: escalate.url(args, options),
    method: 'post',
})

escalate.form = escalateForm

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
export const resolve = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(args, options),
    method: 'post',
})

resolve.definition = {
    methods: ["post"],
    url: '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
resolve.url = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
    }

    return resolve.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
resolve.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
const resolveForm = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
resolveForm.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve.url(args, options),
    method: 'post',
})

resolve.form = resolveForm

const disputes = {
    operations: Object.assign(operations, operations),
    show: Object.assign(show, show),
    proofs: Object.assign(proofs, proofs),
    escalate: Object.assign(escalate, escalate),
    resolve: Object.assign(resolve, resolve),
}

export default disputes