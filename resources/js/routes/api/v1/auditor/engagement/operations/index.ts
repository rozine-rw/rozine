import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\AuditorEngagementController::show
* @see app/Http/Controllers/Api/V1/AuditorEngagementController.php:51
* @route '/api/v1/auditor/engagement/operations/{request_id}'
*/
export const show = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/auditor/engagement/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\AuditorEngagementController::show
* @see app/Http/Controllers/Api/V1/AuditorEngagementController.php:51
* @route '/api/v1/auditor/engagement/operations/{request_id}'
*/
show.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AuditorEngagementController::show
* @see app/Http/Controllers/Api/V1/AuditorEngagementController.php:51
* @route '/api/v1/auditor/engagement/operations/{request_id}'
*/
show.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorEngagementController::show
* @see app/Http/Controllers/Api/V1/AuditorEngagementController.php:51
* @route '/api/v1/auditor/engagement/operations/{request_id}'
*/
show.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorEngagementController::show
* @see app/Http/Controllers/Api/V1/AuditorEngagementController.php:51
* @route '/api/v1/auditor/engagement/operations/{request_id}'
*/
const showForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorEngagementController::show
* @see app/Http/Controllers/Api/V1/AuditorEngagementController.php:51
* @route '/api/v1/auditor/engagement/operations/{request_id}'
*/
showForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorEngagementController::show
* @see app/Http/Controllers/Api/V1/AuditorEngagementController.php:51
* @route '/api/v1/auditor/engagement/operations/{request_id}'
*/
showForm.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const operations = {
    show: Object.assign(show, show),
}

export default operations