import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:28
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
export const draft = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: draft.url(args, options),
    method: 'get',
})

draft.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/statements/transcription-draft',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:28
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
draft.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return draft.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:28
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
draft.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: draft.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:28
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
draft.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: draft.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:28
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
const draftForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: draft.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:28
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
draftForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: draft.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:28
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
draftForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: draft.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

draft.form = draftForm

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:35
* @route '/api/v1/business/{business}/statements/transcription'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/statements/transcription',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:35
* @route '/api/v1/business/{business}/statements/transcription'
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
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:35
* @route '/api/v1/business/{business}/statements/transcription'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:35
* @route '/api/v1/business/{business}/statements/transcription'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:35
* @route '/api/v1/business/{business}/statements/transcription'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:35
* @route '/api/v1/business/{business}/statements/transcription'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:35
* @route '/api/v1/business/{business}/statements/transcription'
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

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:44
* @route '/api/v1/business/{business}/statements/transcription'
*/
export const save = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/statements/transcription',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:44
* @route '/api/v1/business/{business}/statements/transcription'
*/
save.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return save.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:44
* @route '/api/v1/business/{business}/statements/transcription'
*/
save.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:44
* @route '/api/v1/business/{business}/statements/transcription'
*/
const saveForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:44
* @route '/api/v1/business/{business}/statements/transcription'
*/
saveForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

save.form = saveForm

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:67
* @route '/api/v1/business/{business}/statements/audit-readiness'
*/
export const readiness = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: readiness.url(args, options),
    method: 'get',
})

readiness.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/statements/audit-readiness',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:67
* @route '/api/v1/business/{business}/statements/audit-readiness'
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
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:67
* @route '/api/v1/business/{business}/statements/audit-readiness'
*/
readiness.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: readiness.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:67
* @route '/api/v1/business/{business}/statements/audit-readiness'
*/
readiness.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: readiness.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:67
* @route '/api/v1/business/{business}/statements/audit-readiness'
*/
const readinessForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: readiness.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:67
* @route '/api/v1/business/{business}/statements/audit-readiness'
*/
readinessForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: readiness.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:67
* @route '/api/v1/business/{business}/statements/audit-readiness'
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
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:58
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
*/
export const operation = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

operation.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/statement-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:58
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
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
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:58
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
*/
operation.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:58
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
*/
operation.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:58
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
*/
const operationForm = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:58
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
*/
operationForm.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:58
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
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

const BusinessStatementTranscriptionController = { draft, show, save, readiness, operation }

export default BusinessStatementTranscriptionController