import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/api/v1/changes'
*/
const index5994c3ab51d515e3eb1476388a9e5875 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index5994c3ab51d515e3eb1476388a9e5875.url(options),
    method: 'get',
})

index5994c3ab51d515e3eb1476388a9e5875.definition = {
    methods: ["get","head"],
    url: '/api/v1/changes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/api/v1/changes'
*/
index5994c3ab51d515e3eb1476388a9e5875.url = (options?: RouteQueryOptions) => {
    return index5994c3ab51d515e3eb1476388a9e5875.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/api/v1/changes'
*/
index5994c3ab51d515e3eb1476388a9e5875.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index5994c3ab51d515e3eb1476388a9e5875.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/api/v1/changes'
*/
index5994c3ab51d515e3eb1476388a9e5875.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index5994c3ab51d515e3eb1476388a9e5875.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/api/v1/changes'
*/
const index5994c3ab51d515e3eb1476388a9e5875Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index5994c3ab51d515e3eb1476388a9e5875.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/api/v1/changes'
*/
index5994c3ab51d515e3eb1476388a9e5875Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index5994c3ab51d515e3eb1476388a9e5875.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/api/v1/changes'
*/
index5994c3ab51d515e3eb1476388a9e5875Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index5994c3ab51d515e3eb1476388a9e5875.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index5994c3ab51d515e3eb1476388a9e5875.form = index5994c3ab51d515e3eb1476388a9e5875Form
/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
const index03f0f2a39639379637c840c95c271ba7 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index03f0f2a39639379637c840c95c271ba7.url(options),
    method: 'get',
})

index03f0f2a39639379637c840c95c271ba7.definition = {
    methods: ["get","head"],
    url: '/changes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
index03f0f2a39639379637c840c95c271ba7.url = (options?: RouteQueryOptions) => {
    return index03f0f2a39639379637c840c95c271ba7.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
index03f0f2a39639379637c840c95c271ba7.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index03f0f2a39639379637c840c95c271ba7.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
index03f0f2a39639379637c840c95c271ba7.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index03f0f2a39639379637c840c95c271ba7.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
const index03f0f2a39639379637c840c95c271ba7Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index03f0f2a39639379637c840c95c271ba7.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
index03f0f2a39639379637c840c95c271ba7Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index03f0f2a39639379637c840c95c271ba7.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/changes'
*/
index03f0f2a39639379637c840c95c271ba7Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index03f0f2a39639379637c840c95c271ba7.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index03f0f2a39639379637c840c95c271ba7.form = index03f0f2a39639379637c840c95c271ba7Form
/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/admin/changes'
*/
const indexde8156e13c0406c21945e6b636796737 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexde8156e13c0406c21945e6b636796737.url(options),
    method: 'get',
})

indexde8156e13c0406c21945e6b636796737.definition = {
    methods: ["get","head"],
    url: '/admin/changes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/admin/changes'
*/
indexde8156e13c0406c21945e6b636796737.url = (options?: RouteQueryOptions) => {
    return indexde8156e13c0406c21945e6b636796737.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/admin/changes'
*/
indexde8156e13c0406c21945e6b636796737.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexde8156e13c0406c21945e6b636796737.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/admin/changes'
*/
indexde8156e13c0406c21945e6b636796737.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexde8156e13c0406c21945e6b636796737.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/admin/changes'
*/
const indexde8156e13c0406c21945e6b636796737Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexde8156e13c0406c21945e6b636796737.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/admin/changes'
*/
indexde8156e13c0406c21945e6b636796737Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexde8156e13c0406c21945e6b636796737.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ChangeFeedController::index
* @see app/Http/Controllers/ChangeFeedController.php:13
* @route '/admin/changes'
*/
indexde8156e13c0406c21945e6b636796737Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexde8156e13c0406c21945e6b636796737.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexde8156e13c0406c21945e6b636796737.form = indexde8156e13c0406c21945e6b636796737Form

/**
* Multiple routes resolve to \App\Http\Controllers\ChangeFeedController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/changes': index5994c3ab51d515e3eb1476388a9e5875,
    '/changes': index03f0f2a39639379637c840c95c271ba7,
    '/admin/changes': indexde8156e13c0406c21945e6b636796737,
}

const ChangeFeedController = { index }

export default ChangeFeedController