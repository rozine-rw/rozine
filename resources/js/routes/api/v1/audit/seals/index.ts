import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
export const verify = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verify.url(args, options),
    method: 'get',
})

verify.definition = {
    methods: ["get","head"],
    url: '/api/v1/audit-seals/{report}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
verify.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return verify.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
verify.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verify.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
verify.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: verify.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
const verifyForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verify.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
verifyForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verify.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
verifyForm.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: verify.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

verify.form = verifyForm

const seals = {
    verify: Object.assign(verify, verify),
}

export default seals