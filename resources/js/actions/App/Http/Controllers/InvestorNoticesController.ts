import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
const index09f0c293ca970c830dc35b962ca1684b = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index09f0c293ca970c830dc35b962ca1684b.url(options),
    method: 'get',
})

index09f0c293ca970c830dc35b962ca1684b.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/notices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
index09f0c293ca970c830dc35b962ca1684b.url = (options?: RouteQueryOptions) => {
    return index09f0c293ca970c830dc35b962ca1684b.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
index09f0c293ca970c830dc35b962ca1684b.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index09f0c293ca970c830dc35b962ca1684b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
index09f0c293ca970c830dc35b962ca1684b.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index09f0c293ca970c830dc35b962ca1684b.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
const index09f0c293ca970c830dc35b962ca1684bForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index09f0c293ca970c830dc35b962ca1684b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
index09f0c293ca970c830dc35b962ca1684bForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index09f0c293ca970c830dc35b962ca1684b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/api/v1/investor/notices'
*/
index09f0c293ca970c830dc35b962ca1684bForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index09f0c293ca970c830dc35b962ca1684b.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index09f0c293ca970c830dc35b962ca1684b.form = index09f0c293ca970c830dc35b962ca1684bForm
/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/investor/notices'
*/
const index1a5c81e39804ed9764396c8deb1e9b55 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index1a5c81e39804ed9764396c8deb1e9b55.url(options),
    method: 'get',
})

index1a5c81e39804ed9764396c8deb1e9b55.definition = {
    methods: ["get","head"],
    url: '/investor/notices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/investor/notices'
*/
index1a5c81e39804ed9764396c8deb1e9b55.url = (options?: RouteQueryOptions) => {
    return index1a5c81e39804ed9764396c8deb1e9b55.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/investor/notices'
*/
index1a5c81e39804ed9764396c8deb1e9b55.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index1a5c81e39804ed9764396c8deb1e9b55.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/investor/notices'
*/
index1a5c81e39804ed9764396c8deb1e9b55.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index1a5c81e39804ed9764396c8deb1e9b55.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/investor/notices'
*/
const index1a5c81e39804ed9764396c8deb1e9b55Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index1a5c81e39804ed9764396c8deb1e9b55.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/investor/notices'
*/
index1a5c81e39804ed9764396c8deb1e9b55Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index1a5c81e39804ed9764396c8deb1e9b55.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::index
* @see app/Http/Controllers/InvestorNoticesController.php:17
* @route '/investor/notices'
*/
index1a5c81e39804ed9764396c8deb1e9b55Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index1a5c81e39804ed9764396c8deb1e9b55.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index1a5c81e39804ed9764396c8deb1e9b55.form = index1a5c81e39804ed9764396c8deb1e9b55Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorNoticesController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/investor/notices': index09f0c293ca970c830dc35b962ca1684b,
    '/investor/notices': index1a5c81e39804ed9764396c8deb1e9b55,
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
const readAlle35389918c77ae6ed6b3cee789ef172f = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAlle35389918c77ae6ed6b3cee789ef172f.url(options),
    method: 'post',
})

readAlle35389918c77ae6ed6b3cee789ef172f.definition = {
    methods: ["post"],
    url: '/api/v1/investor/notices/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
readAlle35389918c77ae6ed6b3cee789ef172f.url = (options?: RouteQueryOptions) => {
    return readAlle35389918c77ae6ed6b3cee789ef172f.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
readAlle35389918c77ae6ed6b3cee789ef172f.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAlle35389918c77ae6ed6b3cee789ef172f.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
const readAlle35389918c77ae6ed6b3cee789ef172fForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAlle35389918c77ae6ed6b3cee789ef172f.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
readAlle35389918c77ae6ed6b3cee789ef172fForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAlle35389918c77ae6ed6b3cee789ef172f.url(options),
    method: 'post',
})

readAlle35389918c77ae6ed6b3cee789ef172f.form = readAlle35389918c77ae6ed6b3cee789ef172fForm
/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/investor/notices/read-all'
*/
const readAll3ffd0ee9fde1f5541e204375fa393a1f = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll3ffd0ee9fde1f5541e204375fa393a1f.url(options),
    method: 'post',
})

readAll3ffd0ee9fde1f5541e204375fa393a1f.definition = {
    methods: ["post"],
    url: '/investor/notices/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/investor/notices/read-all'
*/
readAll3ffd0ee9fde1f5541e204375fa393a1f.url = (options?: RouteQueryOptions) => {
    return readAll3ffd0ee9fde1f5541e204375fa393a1f.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/investor/notices/read-all'
*/
readAll3ffd0ee9fde1f5541e204375fa393a1f.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll3ffd0ee9fde1f5541e204375fa393a1f.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/investor/notices/read-all'
*/
const readAll3ffd0ee9fde1f5541e204375fa393a1fForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll3ffd0ee9fde1f5541e204375fa393a1f.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::readAll
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/investor/notices/read-all'
*/
readAll3ffd0ee9fde1f5541e204375fa393a1fForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll3ffd0ee9fde1f5541e204375fa393a1f.url(options),
    method: 'post',
})

readAll3ffd0ee9fde1f5541e204375fa393a1f.form = readAll3ffd0ee9fde1f5541e204375fa393a1fForm

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorNoticesController::readAll, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `readAll['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const readAll = {
    '/api/v1/investor/notices/read-all': readAlle35389918c77ae6ed6b3cee789ef172f,
    '/investor/notices/read-all': readAll3ffd0ee9fde1f5541e204375fa393a1f,
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
const read95d71dd8cf850f01cfe8a4e0113d27ab = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read95d71dd8cf850f01cfe8a4e0113d27ab.url(args, options),
    method: 'post',
})

read95d71dd8cf850f01cfe8a4e0113d27ab.definition = {
    methods: ["post"],
    url: '/api/v1/investor/notices/{notice}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
read95d71dd8cf850f01cfe8a4e0113d27ab.url = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return read95d71dd8cf850f01cfe8a4e0113d27ab.definition.url
            .replace('{notice}', parsedArgs.notice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
read95d71dd8cf850f01cfe8a4e0113d27ab.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read95d71dd8cf850f01cfe8a4e0113d27ab.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
const read95d71dd8cf850f01cfe8a4e0113d27abForm = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read95d71dd8cf850f01cfe8a4e0113d27ab.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
read95d71dd8cf850f01cfe8a4e0113d27abForm.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read95d71dd8cf850f01cfe8a4e0113d27ab.url(args, options),
    method: 'post',
})

read95d71dd8cf850f01cfe8a4e0113d27ab.form = read95d71dd8cf850f01cfe8a4e0113d27abForm
/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/investor/notices/{notice}/read'
*/
const readbe074e60d8adff07e1b77fa925ccc054 = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readbe074e60d8adff07e1b77fa925ccc054.url(args, options),
    method: 'post',
})

readbe074e60d8adff07e1b77fa925ccc054.definition = {
    methods: ["post"],
    url: '/investor/notices/{notice}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/investor/notices/{notice}/read'
*/
readbe074e60d8adff07e1b77fa925ccc054.url = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return readbe074e60d8adff07e1b77fa925ccc054.definition.url
            .replace('{notice}', parsedArgs.notice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/investor/notices/{notice}/read'
*/
readbe074e60d8adff07e1b77fa925ccc054.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readbe074e60d8adff07e1b77fa925ccc054.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/investor/notices/{notice}/read'
*/
const readbe074e60d8adff07e1b77fa925ccc054Form = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readbe074e60d8adff07e1b77fa925ccc054.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/investor/notices/{notice}/read'
*/
readbe074e60d8adff07e1b77fa925ccc054Form.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readbe074e60d8adff07e1b77fa925ccc054.url(args, options),
    method: 'post',
})

readbe074e60d8adff07e1b77fa925ccc054.form = readbe074e60d8adff07e1b77fa925ccc054Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorNoticesController::read, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `read['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const read = {
    '/api/v1/investor/notices/{notice}/read': read95d71dd8cf850f01cfe8a4e0113d27ab,
    '/investor/notices/{notice}/read': readbe074e60d8adff07e1b77fa925ccc054,
}

const InvestorNoticesController = { index, readAll, read }

export default InvestorNoticesController