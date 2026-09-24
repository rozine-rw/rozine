import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
const StaffAccessController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffAccessController.url(options),
    method: 'get',
})

StaffAccessController.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff-access',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
StaffAccessController.url = (options?: RouteQueryOptions) => {
    return StaffAccessController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
StaffAccessController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffAccessController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
StaffAccessController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffAccessController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
const StaffAccessControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffAccessController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
StaffAccessControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffAccessController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\StaffAccessController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/Api/V1/StaffAccessController.php:14
* @route '/api/v1/staff-access'
*/
StaffAccessControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffAccessController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffAccessController.form = StaffAccessControllerForm

export default StaffAccessController