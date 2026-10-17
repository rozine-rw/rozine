import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/api/v1/auditor/notices'
*/
const indexc2b30a0bab52a2f5447367ff7c924cb6 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexc2b30a0bab52a2f5447367ff7c924cb6.url(options),
    method: 'get',
})

indexc2b30a0bab52a2f5447367ff7c924cb6.definition = {
    methods: ["get","head"],
    url: '/api/v1/auditor/notices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/api/v1/auditor/notices'
*/
indexc2b30a0bab52a2f5447367ff7c924cb6.url = (options?: RouteQueryOptions) => {
    return indexc2b30a0bab52a2f5447367ff7c924cb6.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/api/v1/auditor/notices'
*/
indexc2b30a0bab52a2f5447367ff7c924cb6.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexc2b30a0bab52a2f5447367ff7c924cb6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/api/v1/auditor/notices'
*/
indexc2b30a0bab52a2f5447367ff7c924cb6.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexc2b30a0bab52a2f5447367ff7c924cb6.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/api/v1/auditor/notices'
*/
const indexc2b30a0bab52a2f5447367ff7c924cb6Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexc2b30a0bab52a2f5447367ff7c924cb6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/api/v1/auditor/notices'
*/
indexc2b30a0bab52a2f5447367ff7c924cb6Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexc2b30a0bab52a2f5447367ff7c924cb6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/api/v1/auditor/notices'
*/
indexc2b30a0bab52a2f5447367ff7c924cb6Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexc2b30a0bab52a2f5447367ff7c924cb6.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexc2b30a0bab52a2f5447367ff7c924cb6.form = indexc2b30a0bab52a2f5447367ff7c924cb6Form
/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
const index96b5f6ece5fea1d3cb0aa1bf19bce882 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index96b5f6ece5fea1d3cb0aa1bf19bce882.url(options),
    method: 'get',
})

index96b5f6ece5fea1d3cb0aa1bf19bce882.definition = {
    methods: ["get","head"],
    url: '/auditor/notices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
index96b5f6ece5fea1d3cb0aa1bf19bce882.url = (options?: RouteQueryOptions) => {
    return index96b5f6ece5fea1d3cb0aa1bf19bce882.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
index96b5f6ece5fea1d3cb0aa1bf19bce882.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index96b5f6ece5fea1d3cb0aa1bf19bce882.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
index96b5f6ece5fea1d3cb0aa1bf19bce882.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index96b5f6ece5fea1d3cb0aa1bf19bce882.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
const index96b5f6ece5fea1d3cb0aa1bf19bce882Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index96b5f6ece5fea1d3cb0aa1bf19bce882.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
index96b5f6ece5fea1d3cb0aa1bf19bce882Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index96b5f6ece5fea1d3cb0aa1bf19bce882.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::index
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
index96b5f6ece5fea1d3cb0aa1bf19bce882Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index96b5f6ece5fea1d3cb0aa1bf19bce882.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index96b5f6ece5fea1d3cb0aa1bf19bce882.form = index96b5f6ece5fea1d3cb0aa1bf19bce882Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditorNoticesController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/auditor/notices': indexc2b30a0bab52a2f5447367ff7c924cb6,
    '/auditor/notices': index96b5f6ece5fea1d3cb0aa1bf19bce882,
}

/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/api/v1/auditor/notices/read-all'
*/
const readAllfa68070cdc3fa45f41b55ee0836211f8 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAllfa68070cdc3fa45f41b55ee0836211f8.url(options),
    method: 'post',
})

readAllfa68070cdc3fa45f41b55ee0836211f8.definition = {
    methods: ["post"],
    url: '/api/v1/auditor/notices/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/api/v1/auditor/notices/read-all'
*/
readAllfa68070cdc3fa45f41b55ee0836211f8.url = (options?: RouteQueryOptions) => {
    return readAllfa68070cdc3fa45f41b55ee0836211f8.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/api/v1/auditor/notices/read-all'
*/
readAllfa68070cdc3fa45f41b55ee0836211f8.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAllfa68070cdc3fa45f41b55ee0836211f8.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/api/v1/auditor/notices/read-all'
*/
const readAllfa68070cdc3fa45f41b55ee0836211f8Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAllfa68070cdc3fa45f41b55ee0836211f8.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/api/v1/auditor/notices/read-all'
*/
readAllfa68070cdc3fa45f41b55ee0836211f8Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAllfa68070cdc3fa45f41b55ee0836211f8.url(options),
    method: 'post',
})

readAllfa68070cdc3fa45f41b55ee0836211f8.form = readAllfa68070cdc3fa45f41b55ee0836211f8Form
/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/auditor/notices/read-all'
*/
const readAll6cb7ea9e3a6667e90a95201a71908eec = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll6cb7ea9e3a6667e90a95201a71908eec.url(options),
    method: 'post',
})

readAll6cb7ea9e3a6667e90a95201a71908eec.definition = {
    methods: ["post"],
    url: '/auditor/notices/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/auditor/notices/read-all'
*/
readAll6cb7ea9e3a6667e90a95201a71908eec.url = (options?: RouteQueryOptions) => {
    return readAll6cb7ea9e3a6667e90a95201a71908eec.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/auditor/notices/read-all'
*/
readAll6cb7ea9e3a6667e90a95201a71908eec.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll6cb7ea9e3a6667e90a95201a71908eec.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/auditor/notices/read-all'
*/
const readAll6cb7ea9e3a6667e90a95201a71908eecForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll6cb7ea9e3a6667e90a95201a71908eec.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::readAll
* @see app/Http/Controllers/AuditorNoticesController.php:27
* @route '/auditor/notices/read-all'
*/
readAll6cb7ea9e3a6667e90a95201a71908eecForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll6cb7ea9e3a6667e90a95201a71908eec.url(options),
    method: 'post',
})

readAll6cb7ea9e3a6667e90a95201a71908eec.form = readAll6cb7ea9e3a6667e90a95201a71908eecForm

/**
* Multiple routes resolve to \App\Http\Controllers\AuditorNoticesController::readAll, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `readAll['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const readAll = {
    '/api/v1/auditor/notices/read-all': readAllfa68070cdc3fa45f41b55ee0836211f8,
    '/auditor/notices/read-all': readAll6cb7ea9e3a6667e90a95201a71908eec,
}

/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/api/v1/auditor/notices/{notice}/read'
*/
const read6be2ccab75ac033bd4c295bbf929e875 = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read6be2ccab75ac033bd4c295bbf929e875.url(args, options),
    method: 'post',
})

read6be2ccab75ac033bd4c295bbf929e875.definition = {
    methods: ["post"],
    url: '/api/v1/auditor/notices/{notice}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/api/v1/auditor/notices/{notice}/read'
*/
read6be2ccab75ac033bd4c295bbf929e875.url = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notice: args }
    }

    if (Array.isArray(args)) {
        args = {
            notice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        notice: args.notice,
    }

    return read6be2ccab75ac033bd4c295bbf929e875.definition.url
            .replace('{notice}', parsedArgs.notice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/api/v1/auditor/notices/{notice}/read'
*/
read6be2ccab75ac033bd4c295bbf929e875.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read6be2ccab75ac033bd4c295bbf929e875.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/api/v1/auditor/notices/{notice}/read'
*/
const read6be2ccab75ac033bd4c295bbf929e875Form = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read6be2ccab75ac033bd4c295bbf929e875.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/api/v1/auditor/notices/{notice}/read'
*/
read6be2ccab75ac033bd4c295bbf929e875Form.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read6be2ccab75ac033bd4c295bbf929e875.url(args, options),
    method: 'post',
})

read6be2ccab75ac033bd4c295bbf929e875.form = read6be2ccab75ac033bd4c295bbf929e875Form
/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/auditor/notices/{notice}/read'
*/
const read23f9cb2d9765d09c6fb2f193ef3c89ff = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read23f9cb2d9765d09c6fb2f193ef3c89ff.url(args, options),
    method: 'post',
})

read23f9cb2d9765d09c6fb2f193ef3c89ff.definition = {
    methods: ["post"],
    url: '/auditor/notices/{notice}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/auditor/notices/{notice}/read'
*/
read23f9cb2d9765d09c6fb2f193ef3c89ff.url = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notice: args }
    }

    if (Array.isArray(args)) {
        args = {
            notice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        notice: args.notice,
    }

    return read23f9cb2d9765d09c6fb2f193ef3c89ff.definition.url
            .replace('{notice}', parsedArgs.notice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/auditor/notices/{notice}/read'
*/
read23f9cb2d9765d09c6fb2f193ef3c89ff.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read23f9cb2d9765d09c6fb2f193ef3c89ff.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/auditor/notices/{notice}/read'
*/
const read23f9cb2d9765d09c6fb2f193ef3c89ffForm = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read23f9cb2d9765d09c6fb2f193ef3c89ff.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::read
* @see app/Http/Controllers/AuditorNoticesController.php:22
* @route '/auditor/notices/{notice}/read'
*/
read23f9cb2d9765d09c6fb2f193ef3c89ffForm.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read23f9cb2d9765d09c6fb2f193ef3c89ff.url(args, options),
    method: 'post',
})

read23f9cb2d9765d09c6fb2f193ef3c89ff.form = read23f9cb2d9765d09c6fb2f193ef3c89ffForm

/**
* Multiple routes resolve to \App\Http\Controllers\AuditorNoticesController::read, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `read['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const read = {
    '/api/v1/auditor/notices/{notice}/read': read6be2ccab75ac033bd4c295bbf929e875,
    '/auditor/notices/{notice}/read': read23f9cb2d9765d09c6fb2f193ef3c89ff,
}

const AuditorNoticesController = { index, readAll, read }

export default AuditorNoticesController