import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::show
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/businesses/{business}/initial-statement-audit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::show
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
show.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::show
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::show
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::show
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::show
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStatementAuditReadinessController::show
* @see app/Http/Controllers/StaffStatementAuditReadinessController.php:17
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit'
*/
showForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const StaffStatementAuditReadinessController = { show }

export default StaffStatementAuditReadinessController