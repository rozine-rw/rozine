import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:29
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
* @see app/Http/Controllers/AuditorEngagementController.php:29
* @route '/auditor/engagement'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:29
* @route '/auditor/engagement'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:29
* @route '/auditor/engagement'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:29
* @route '/auditor/engagement'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:29
* @route '/auditor/engagement'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::show
* @see app/Http/Controllers/AuditorEngagementController.php:29
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
* @see app/Http/Controllers/AuditorEngagementController.php:43
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
* @see app/Http/Controllers/AuditorEngagementController.php:43
* @route '/auditor/engagement/accept'
*/
accept.url = (options?: RouteQueryOptions) => {
    return accept.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorEngagementController::accept
* @see app/Http/Controllers/AuditorEngagementController.php:43
* @route '/auditor/engagement/accept'
*/
accept.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::accept
* @see app/Http/Controllers/AuditorEngagementController.php:43
* @route '/auditor/engagement/accept'
*/
const acceptForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorEngagementController::accept
* @see app/Http/Controllers/AuditorEngagementController.php:43
* @route '/auditor/engagement/accept'
*/
acceptForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(options),
    method: 'post',
})

accept.form = acceptForm

const engagement = {
    show: Object.assign(show, show),
    accept: Object.assign(accept, accept),
    operations: Object.assign(operations, operations),
}

export default engagement