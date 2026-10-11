import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EnterAppController::__invoke
* @see app/Http/Controllers/EnterAppController.php:17
* @route '/enter'
*/
const EnterAppController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EnterAppController.url(options),
    method: 'get',
})

EnterAppController.definition = {
    methods: ["get","head"],
    url: '/enter',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EnterAppController::__invoke
* @see app/Http/Controllers/EnterAppController.php:17
* @route '/enter'
*/
EnterAppController.url = (options?: RouteQueryOptions) => {
    return EnterAppController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EnterAppController::__invoke
* @see app/Http/Controllers/EnterAppController.php:17
* @route '/enter'
*/
EnterAppController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EnterAppController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EnterAppController::__invoke
* @see app/Http/Controllers/EnterAppController.php:17
* @route '/enter'
*/
EnterAppController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EnterAppController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EnterAppController::__invoke
* @see app/Http/Controllers/EnterAppController.php:17
* @route '/enter'
*/
const EnterAppControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EnterAppController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EnterAppController::__invoke
* @see app/Http/Controllers/EnterAppController.php:17
* @route '/enter'
*/
EnterAppControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EnterAppController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EnterAppController::__invoke
* @see app/Http/Controllers/EnterAppController.php:17
* @route '/enter'
*/
EnterAppControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EnterAppController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EnterAppController.form = EnterAppControllerForm

export default EnterAppController