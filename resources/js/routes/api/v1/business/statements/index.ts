import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
export const index = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/statements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
index.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return index.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
index.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
index.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
const indexForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
indexForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
indexForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
export const upload = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

upload.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/statements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
upload.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return upload.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
upload.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
const uploadForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
uploadForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(args, options),
    method: 'post',
})

upload.form = uploadForm

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
export const show = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/statements/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
show.url = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        document: args.document,
    }

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
show.get = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
show.head = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
const showForm = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
showForm.get = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
showForm.head = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const statements = {
    index: Object.assign(index, index),
    upload: Object.assign(upload, upload),
    show: Object.assign(show, show),
}

export default statements