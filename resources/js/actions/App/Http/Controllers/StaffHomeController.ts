import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
const StaffHomeController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffHomeController.url(options),
    method: 'get',
})

StaffHomeController.definition = {
    methods: ["get","head"],
    url: '/admin',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
StaffHomeController.url = (options?: RouteQueryOptions) => {
    return StaffHomeController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
StaffHomeController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffHomeController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
StaffHomeController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffHomeController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
const StaffHomeControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffHomeController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
StaffHomeControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffHomeController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffHomeController::__invoke
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/StaffHomeController.php:15
* @route '/admin'
*/
StaffHomeControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffHomeController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffHomeController.form = StaffHomeControllerForm

export default StaffHomeController