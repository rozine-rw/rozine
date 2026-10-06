import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
import proofs from './proofs'
/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
export const uphold = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uphold.url(args, options),
    method: 'post',
})

uphold.definition = {
    methods: ["post"],
    url: '/auditor/reports/{report}/dispute/uphold',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
uphold.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { report: args }
    }

    if (Array.isArray(args)) {
        args = {
            report: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        report: args.report,
    }

    return uphold.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
uphold.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uphold.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
const upholdForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uphold.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
upholdForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uphold.url(args, options),
    method: 'post',
})

uphold.form = upholdForm

const disputes = {
    uphold: Object.assign(uphold, uphold),
    proofs: Object.assign(proofs, proofs),
}

export default disputes