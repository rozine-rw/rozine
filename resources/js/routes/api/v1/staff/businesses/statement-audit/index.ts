import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::readiness
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
export const readiness = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: readiness.url(args, options),
    method: 'get',
})

readiness.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/businesses/{business}/initial-statement-audit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::readiness
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
readiness.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return readiness.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::readiness
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
readiness.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: readiness.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::readiness
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
readiness.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: readiness.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::readiness
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
const readinessForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: readiness.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::readiness
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
readinessForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: readiness.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::readiness
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
readinessForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: readiness.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

readiness.form = readinessForm

const statementAudit = {
    readiness: Object.assign(readiness, readiness),
}

export default statementAudit