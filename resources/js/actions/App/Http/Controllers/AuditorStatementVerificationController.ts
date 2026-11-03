import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::original
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
export const original = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: original.url(args, options),
    method: 'get',
})

original.definition = {
    methods: ["get","head"],
    url: '/auditor/jobs/{assignment}/statements/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::original
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
original.url = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        document: args.document,
    }

    return original.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::original
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
original.get = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: original.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::original
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
original.head = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: original.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::original
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
const originalForm = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: original.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::original
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
originalForm.get = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: original.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::original
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
originalForm.head = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: original.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

original.form = originalForm

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
export const store = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/auditor/jobs/{assignment}/statement-verification',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
store.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { assignment: args }
    }

    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
    }

    return store.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
store.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
const storeForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
storeForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::operation
* @see app/Http/Controllers/AuditorStatementVerificationController.php:53
* @route '/auditor/statement-operations/{request_id}'
*/
export const operation = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

operation.definition = {
    methods: ["get","head"],
    url: '/auditor/statement-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::operation
* @see app/Http/Controllers/AuditorStatementVerificationController.php:53
* @route '/auditor/statement-operations/{request_id}'
*/
operation.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { request_id: args }
    }

    if (Array.isArray(args)) {
        args = {
            request_id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        request_id: args.request_id,
    }

    return operation.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::operation
* @see app/Http/Controllers/AuditorStatementVerificationController.php:53
* @route '/auditor/statement-operations/{request_id}'
*/
operation.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::operation
* @see app/Http/Controllers/AuditorStatementVerificationController.php:53
* @route '/auditor/statement-operations/{request_id}'
*/
operation.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::operation
* @see app/Http/Controllers/AuditorStatementVerificationController.php:53
* @route '/auditor/statement-operations/{request_id}'
*/
const operationForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::operation
* @see app/Http/Controllers/AuditorStatementVerificationController.php:53
* @route '/auditor/statement-operations/{request_id}'
*/
operationForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::operation
* @see app/Http/Controllers/AuditorStatementVerificationController.php:53
* @route '/auditor/statement-operations/{request_id}'
*/
operationForm.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation.form = operationForm

const AuditorStatementVerificationController = { original, store, operation }

export default AuditorStatementVerificationController