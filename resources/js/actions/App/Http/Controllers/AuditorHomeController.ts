import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:28
* @route '/auditor'
*/
const AuditorHomeController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AuditorHomeController.url(options),
    method: 'get',
})

AuditorHomeController.definition = {
    methods: ["get","head"],
    url: '/auditor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:28
* @route '/auditor'
*/
AuditorHomeController.url = (options?: RouteQueryOptions) => {
    return AuditorHomeController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:28
* @route '/auditor'
*/
AuditorHomeController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AuditorHomeController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:28
* @route '/auditor'
*/
AuditorHomeController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: AuditorHomeController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:28
* @route '/auditor'
*/
const AuditorHomeControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditorHomeController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:28
* @route '/auditor'
*/
AuditorHomeControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditorHomeController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:28
* @route '/auditor'
*/
AuditorHomeControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditorHomeController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

AuditorHomeController.form = AuditorHomeControllerForm

export default AuditorHomeController