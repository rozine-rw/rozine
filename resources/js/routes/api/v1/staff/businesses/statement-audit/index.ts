import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
import operations from './operations'
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

/**
* @see \App\Http\Controllers\StaffStatementReviewController::review
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
export const review = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: review.url(args, options),
    method: 'post',
})

review.definition = {
    methods: ["post"],
    url: '/api/v1/staff/businesses/{business}/initial-statement-audit/review',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffStatementReviewController::review
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
review.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return review.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffStatementReviewController::review
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
review.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: review.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffStatementReviewController::review
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
const reviewForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: review.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffStatementReviewController::review
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
reviewForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: review.url(args, options),
    method: 'post',
})

review.form = reviewForm

const statementAudit = {
    readiness: Object.assign(readiness, readiness),
    review: Object.assign(review, review),
    operations: Object.assign(operations, operations),
}

export default statementAudit