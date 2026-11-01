import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:85
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:85
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:85
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
draft.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: draft.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:85
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
draft.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: draft.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:85
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
const draftForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: draft.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:85
* @route '/api/v1/business/{business}/statements/transcription-draft'
*/
draftForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: draft.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::draft
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:85
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:92
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:92
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:92
* @route '/api/v1/business/{business}/statements/transcription'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:92
* @route '/api/v1/business/{business}/statements/transcription'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:92
* @route '/api/v1/business/{business}/statements/transcription'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:92
* @route '/api/v1/business/{business}/statements/transcription'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:92
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/api/v1/business/{business}/statements/transcription'
*/
const savea51f66a9d3818fcf9a19089e3f6972fa = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savea51f66a9d3818fcf9a19089e3f6972fa.url(args, options),
    method: 'post',
})

savea51f66a9d3818fcf9a19089e3f6972fa.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/statements/transcription',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/api/v1/business/{business}/statements/transcription'
*/
savea51f66a9d3818fcf9a19089e3f6972fa.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return savea51f66a9d3818fcf9a19089e3f6972fa.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/api/v1/business/{business}/statements/transcription'
*/
savea51f66a9d3818fcf9a19089e3f6972fa.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savea51f66a9d3818fcf9a19089e3f6972fa.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/api/v1/business/{business}/statements/transcription'
*/
const savea51f66a9d3818fcf9a19089e3f6972faForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: savea51f66a9d3818fcf9a19089e3f6972fa.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/api/v1/business/{business}/statements/transcription'
*/
savea51f66a9d3818fcf9a19089e3f6972faForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: savea51f66a9d3818fcf9a19089e3f6972fa.url(args, options),
    method: 'post',
})

savea51f66a9d3818fcf9a19089e3f6972fa.form = savea51f66a9d3818fcf9a19089e3f6972faForm
/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/business/{business}/statements/transcription'
*/
const save9726eff259edc3ad3af3c75d586726c6 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save9726eff259edc3ad3af3c75d586726c6.url(args, options),
    method: 'post',
})

save9726eff259edc3ad3af3c75d586726c6.definition = {
    methods: ["post"],
    url: '/business/{business}/statements/transcription',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/business/{business}/statements/transcription'
*/
save9726eff259edc3ad3af3c75d586726c6.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return save9726eff259edc3ad3af3c75d586726c6.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/business/{business}/statements/transcription'
*/
save9726eff259edc3ad3af3c75d586726c6.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save9726eff259edc3ad3af3c75d586726c6.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/business/{business}/statements/transcription'
*/
const save9726eff259edc3ad3af3c75d586726c6Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save9726eff259edc3ad3af3c75d586726c6.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/business/{business}/statements/transcription'
*/
save9726eff259edc3ad3af3c75d586726c6Form.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save9726eff259edc3ad3af3c75d586726c6.url(args, options),
    method: 'post',
})

save9726eff259edc3ad3af3c75d586726c6.form = save9726eff259edc3ad3af3c75d586726c6Form

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessStatementTranscriptionController::save, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `save['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const save = {
    '/api/v1/business/{business}/statements/transcription': savea51f66a9d3818fcf9a19089e3f6972fa,
    '/business/{business}/statements/transcription': save9726eff259edc3ad3af3c75d586726c6,
}

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:127
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:127
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:127
* @route '/api/v1/business/{business}/statements/audit-readiness'
*/
readiness.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: readiness.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:127
* @route '/api/v1/business/{business}/statements/audit-readiness'
*/
readiness.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: readiness.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:127
* @route '/api/v1/business/{business}/statements/audit-readiness'
*/
const readinessForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: readiness.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:127
* @route '/api/v1/business/{business}/statements/audit-readiness'
*/
readinessForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: readiness.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::readiness
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:127
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:118
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:118
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:118
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
*/
operation.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:118
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
*/
operation.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:118
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
*/
const operationForm = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:118
* @route '/api/v1/business/{business}/statement-operations/{request_id}'
*/
operationForm.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::operation
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:118
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

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::page
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
export const page = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(args, options),
    method: 'get',
})

page.definition = {
    methods: ["get","head"],
    url: '/business/{business}/statements/transcription',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::page
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
page.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return page.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::page
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
page.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::page
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
page.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: page.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::page
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
const pageForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: page.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::page
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
pageForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: page.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::page
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
pageForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: page.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

page.form = pageForm

const BusinessStatementTranscriptionController = { draft, show, save, readiness, operation, page }

export default BusinessStatementTranscriptionController