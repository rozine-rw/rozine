import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
export const version = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: version.url(options),
    method: 'get',
})

version.definition = {
    methods: ["get","head"],
    url: '/auditor/version',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
version.url = (options?: RouteQueryOptions) => {
    return version.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
version.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: version.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
version.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: version.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
const versionForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: version.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
versionForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: version.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeVersionController::__invoke
* @see app/Http/Controllers/AuditorHomeVersionController.php:18
* @route '/auditor/version'
*/
versionForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: version.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

version.form = versionForm

const home = {
    version: Object.assign(version, version),
}

export default home