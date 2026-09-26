import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
export const show = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
show.url = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            report: args[0],
            proof: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        report: args.report,
        proof: args.proof,
    }

    return show.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace('{proof}', parsedArgs.proof.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
show.get = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
show.head = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
const showForm = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
showForm.get = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
showForm.head = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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