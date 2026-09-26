import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
export const show = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
show.url = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
            proof: args[2],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
        proof: args.proof,
    }

    return show.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace('{proof}', parsedArgs.proof.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
show.get = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
show.head = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
const showForm = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
showForm.get = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
showForm.head = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const proofs = {
    show: Object.assign(show, show),
}

export default proofs