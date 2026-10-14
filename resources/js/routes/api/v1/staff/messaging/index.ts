import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/messaging/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffMessagingController::__invoke
* @see app/Http/Controllers/StaffMessagingController.php:24
* @route '/api/v1/staff/messaging/history'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

const messaging = {
    index: Object.assign(index, index),
}

export default messaging