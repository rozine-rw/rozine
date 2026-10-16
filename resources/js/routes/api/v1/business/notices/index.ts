import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
export const index = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/notices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
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
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
index.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
index.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
const indexForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
indexForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
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
* @see \App\Http\Controllers\BusinessNoticesController::read_all
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
export const read_all = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read_all.url(args, options),
    method: 'post',
})

read_all.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/notices/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessNoticesController::read_all
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
read_all.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return read_all.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::read_all
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
read_all.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read_all.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::read_all
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
const read_allForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read_all.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::read_all
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
read_allForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read_all.url(args, options),
    method: 'post',
})

read_all.form = read_allForm

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
export const read = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read.url(args, options),
    method: 'post',
})

read.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/notices/{notice}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
read.url = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            notice: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        notice: args.notice,
    }

    return read.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{notice}', parsedArgs.notice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
read.post = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
const readForm = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
readForm.post = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read.url(args, options),
    method: 'post',
})

read.form = readForm

const notices = {
    index: Object.assign(index, index),
    read_all: Object.assign(read_all, read_all),
    read: Object.assign(read, read),
}

export default notices