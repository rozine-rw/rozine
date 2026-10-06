import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
const AuditSealVerificationController121379339ed9650d05c9244059551c7b = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AuditSealVerificationController121379339ed9650d05c9244059551c7b.url(args, options),
    method: 'get',
})

AuditSealVerificationController121379339ed9650d05c9244059551c7b.definition = {
    methods: ["get","head"],
    url: '/api/v1/audit-seals/{report}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
AuditSealVerificationController121379339ed9650d05c9244059551c7b.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return AuditSealVerificationController121379339ed9650d05c9244059551c7b.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
AuditSealVerificationController121379339ed9650d05c9244059551c7b.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AuditSealVerificationController121379339ed9650d05c9244059551c7b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
AuditSealVerificationController121379339ed9650d05c9244059551c7b.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: AuditSealVerificationController121379339ed9650d05c9244059551c7b.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
const AuditSealVerificationController121379339ed9650d05c9244059551c7bForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditSealVerificationController121379339ed9650d05c9244059551c7b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
AuditSealVerificationController121379339ed9650d05c9244059551c7bForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditSealVerificationController121379339ed9650d05c9244059551c7b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/api/v1/audit-seals/{report}'
*/
AuditSealVerificationController121379339ed9650d05c9244059551c7bForm.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditSealVerificationController121379339ed9650d05c9244059551c7b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

AuditSealVerificationController121379339ed9650d05c9244059551c7b.form = AuditSealVerificationController121379339ed9650d05c9244059551c7bForm
/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/audit-seals/{report}'
*/
const AuditSealVerificationController178a434657b86fb747bd53460a5ce950 = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AuditSealVerificationController178a434657b86fb747bd53460a5ce950.url(args, options),
    method: 'get',
})

AuditSealVerificationController178a434657b86fb747bd53460a5ce950.definition = {
    methods: ["get","head"],
    url: '/audit-seals/{report}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/audit-seals/{report}'
*/
AuditSealVerificationController178a434657b86fb747bd53460a5ce950.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return AuditSealVerificationController178a434657b86fb747bd53460a5ce950.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/audit-seals/{report}'
*/
AuditSealVerificationController178a434657b86fb747bd53460a5ce950.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AuditSealVerificationController178a434657b86fb747bd53460a5ce950.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/audit-seals/{report}'
*/
AuditSealVerificationController178a434657b86fb747bd53460a5ce950.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: AuditSealVerificationController178a434657b86fb747bd53460a5ce950.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/audit-seals/{report}'
*/
const AuditSealVerificationController178a434657b86fb747bd53460a5ce950Form = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditSealVerificationController178a434657b86fb747bd53460a5ce950.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/audit-seals/{report}'
*/
AuditSealVerificationController178a434657b86fb747bd53460a5ce950Form.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditSealVerificationController178a434657b86fb747bd53460a5ce950.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditSealVerificationController::__invoke
* @see app/Http/Controllers/AuditSealVerificationController.php:15
* @route '/audit-seals/{report}'
*/
AuditSealVerificationController178a434657b86fb747bd53460a5ce950Form.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditSealVerificationController178a434657b86fb747bd53460a5ce950.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

AuditSealVerificationController178a434657b86fb747bd53460a5ce950.form = AuditSealVerificationController178a434657b86fb747bd53460a5ce950Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditSealVerificationController::AuditSealVerificationController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `AuditSealVerificationController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const AuditSealVerificationController = {
    '/api/v1/audit-seals/{report}': AuditSealVerificationController121379339ed9650d05c9244059551c7b,
    '/audit-seals/{report}': AuditSealVerificationController178a434657b86fb747bd53460a5ce950,
}

export default AuditSealVerificationController