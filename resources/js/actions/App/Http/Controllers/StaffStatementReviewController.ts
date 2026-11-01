import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffStatementReviewController::store
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
export const store = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/v1/staff/businesses/{business}/initial-statement-audit/review',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffStatementReviewController::store
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
store.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffStatementReviewController::store
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
store.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffStatementReviewController::store
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
const storeForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffStatementReviewController::store
* @see app/Http/Controllers/StaffStatementReviewController.php:22
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/review'
*/
storeForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\StaffStatementReviewController::operation
* @see app/Http/Controllers/StaffStatementReviewController.php:28
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/operations/{request_id}'
*/
export const operation = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

operation.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/businesses/{business}/initial-statement-audit/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffStatementReviewController::operation
* @see app/Http/Controllers/StaffStatementReviewController.php:28
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/operations/{request_id}'
*/
operation.url = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            request_id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        request_id: args.request_id,
    }

    return operation.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffStatementReviewController::operation
* @see app/Http/Controllers/StaffStatementReviewController.php:28
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/operations/{request_id}'
*/
operation.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStatementReviewController::operation
* @see app/Http/Controllers/StaffStatementReviewController.php:28
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/operations/{request_id}'
*/
operation.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffStatementReviewController::operation
* @see app/Http/Controllers/StaffStatementReviewController.php:28
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/operations/{request_id}'
*/
const operationForm = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStatementReviewController::operation
* @see app/Http/Controllers/StaffStatementReviewController.php:28
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/operations/{request_id}'
*/
operationForm.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStatementReviewController::operation
* @see app/Http/Controllers/StaffStatementReviewController.php:28
* @route '/api/v1/staff/businesses/{business}/initial-statement-audit/operations/{request_id}'
*/
operationForm.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation.form = operationForm

const StaffStatementReviewController = { store, operation }

export default StaffStatementReviewController