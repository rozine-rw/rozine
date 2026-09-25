import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:21
* @route '/auditor/engagement'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/auditor/engagement',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:21
* @route '/auditor/engagement'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:21
* @route '/auditor/engagement'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:21
* @route '/auditor/engagement'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:21
* @route '/auditor/engagement'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:21
* @route '/auditor/engagement'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:21
* @route '/auditor/engagement'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\AuditorEngagementController::accept
* @see app/Http/Controllers/AuditorEngagementController.php:28
* @route '/auditor/engagement/accept'
*/
export const accept = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(options),
    method: 'post',
})

accept.definition = {
    methods: ["post"],
    url: '/auditor/engagement/accept',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorEngagementController::accept
* @see app/Http/Controllers/AuditorEngagementController.php:28
* @route '/auditor/engagement/accept'
*/
accept.url = (options?: RouteQueryOptions) => {
    return accept.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorEngagementController::accept
* @see app/Http/Controllers/AuditorEngagementController.php:28
* @route '/auditor/engagement/accept'
*/
accept.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::accept
* @see app/Http/Controllers/AuditorEngagementController.php:28
* @route '/auditor/engagement/accept'
*/
const acceptForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::accept
* @see app/Http/Controllers/AuditorEngagementController.php:28
* @route '/auditor/engagement/accept'
*/
acceptForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(options),
    method: 'post',
})

accept.form = acceptForm

/**
* @see \App\Http\Controllers\AuditorEngagementController::operation
* @see app/Http/Controllers/AuditorEngagementController.php:36
* @route '/auditor/engagement/operations/{request_id}'
*/
export const operation = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

operation.definition = {
    methods: ["get","head"],
    url: '/auditor/engagement/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorEngagementController::operation
* @see app/Http/Controllers/AuditorEngagementController.php:36
* @route '/auditor/engagement/operations/{request_id}'
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
* @see \App\Http\Controllers\AuditorEngagementController::operation
* @see app/Http/Controllers/AuditorEngagementController.php:36
* @route '/auditor/engagement/operations/{request_id}'
*/
operation.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::operation
* @see app/Http/Controllers/AuditorEngagementController.php:36
* @route '/auditor/engagement/operations/{request_id}'
*/
operation.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::operation
* @see app/Http/Controllers/AuditorEngagementController.php:36
* @route '/auditor/engagement/operations/{request_id}'
*/
const operationForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::operation
* @see app/Http/Controllers/AuditorEngagementController.php:36
* @route '/auditor/engagement/operations/{request_id}'
*/
operationForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::operation
* @see app/Http/Controllers/AuditorEngagementController.php:36
* @route '/auditor/engagement/operations/{request_id}'
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

const AuditorEngagementController = { show, accept, operation }

export default AuditorEngagementController