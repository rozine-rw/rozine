import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}/statements/transcription',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::show
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:72
* @route '/business/{business}/statements/transcription'
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
* @route '/business/{business}/statements/transcription'
*/
export const save = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/business/{business}/statements/transcription',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/business/{business}/statements/transcription'
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
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/business/{business}/statements/transcription'
*/
save.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/business/{business}/statements/transcription'
*/
const saveForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementTranscriptionController::save
* @see app/Http/Controllers/BusinessStatementTranscriptionController.php:101
* @route '/business/{business}/statements/transcription'
*/
saveForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

save.form = saveForm

const transcription = {
    show: Object.assign(show, show),
    save: Object.assign(save, save),
}

export default transcription