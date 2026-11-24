import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
const AuditorHomeVersionController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AuditorHomeVersionController.url(options),
    method: 'get',
})

AuditorHomeVersionController.definition = {
    methods: ["get","head"],
    url: '/auditor/version',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
AuditorHomeVersionController.url = (options?: RouteQueryOptions) => {
    return AuditorHomeVersionController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
AuditorHomeVersionController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AuditorHomeVersionController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
AuditorHomeVersionController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: AuditorHomeVersionController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
const AuditorHomeVersionControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditorHomeVersionController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
AuditorHomeVersionControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditorHomeVersionController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
AuditorHomeVersionControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AuditorHomeVersionController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

AuditorHomeVersionController.form = AuditorHomeVersionControllerForm

export default AuditorHomeVersionController