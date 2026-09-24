import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff-access',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
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

const staffAccess = {
    show: Object.assign(show, show),
}

export default staffAccess