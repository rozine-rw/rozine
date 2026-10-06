import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
export const show = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
show.url = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            report: args[1],
            proof: args[2],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        report: args.report,
        proof: args.proof,
    }

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace('{proof}', parsedArgs.proof.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
show.get = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
show.head = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
const showForm = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
showForm.get = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
showForm.head = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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