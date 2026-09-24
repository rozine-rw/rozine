import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/investor'
*/
const RoleHomeControllere709e67aade7642dd3bd8469e0997d3b = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.url(options),
    method: 'get',
})

RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.definition = {
    methods: ["get","head"],
    url: '/investor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/investor'
*/
RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.url = (options?: RouteQueryOptions) => {
    return RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/investor'
*/
RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/investor'
*/
RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/investor'
*/
const RoleHomeControllere709e67aade7642dd3bd8469e0997d3bForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/investor'
*/
RoleHomeControllere709e67aade7642dd3bd8469e0997d3bForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/investor'
*/
RoleHomeControllere709e67aade7642dd3bd8469e0997d3bForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

RoleHomeControllere709e67aade7642dd3bd8469e0997d3b.form = RoleHomeControllere709e67aade7642dd3bd8469e0997d3bForm
/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
const RoleHomeController813da5a24c0bb640ea94a1923c9f0b54 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.url(options),
    method: 'get',
})

RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.definition = {
    methods: ["get","head"],
    url: '/business',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.url = (options?: RouteQueryOptions) => {
    return RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
const RoleHomeController813da5a24c0bb640ea94a1923c9f0b54Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
RoleHomeController813da5a24c0bb640ea94a1923c9f0b54Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/business'
*/
RoleHomeController813da5a24c0bb640ea94a1923c9f0b54Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

RoleHomeController813da5a24c0bb640ea94a1923c9f0b54.form = RoleHomeController813da5a24c0bb640ea94a1923c9f0b54Form
/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/auditor'
*/
const RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.url(options),
    method: 'get',
})

RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.definition = {
    methods: ["get","head"],
    url: '/auditor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/auditor'
*/
RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.url = (options?: RouteQueryOptions) => {
    return RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/auditor'
*/
RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/auditor'
*/
RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/auditor'
*/
const RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/auditor'
*/
RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/RoleHomeController.php:15
* @route '/auditor'
*/
RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5.form = RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5Form

/**
* Multiple routes resolve to \App\Http\Controllers\RoleHomeController::RoleHomeController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `RoleHomeController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const RoleHomeController = {
    '/investor': RoleHomeControllere709e67aade7642dd3bd8469e0997d3b,
    '/business': RoleHomeController813da5a24c0bb640ea94a1923c9f0b54,
    '/auditor': RoleHomeController37cc84ee0664e52c0cf7796b3ef725e5,
}

export default RoleHomeController