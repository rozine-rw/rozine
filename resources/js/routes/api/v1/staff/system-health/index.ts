import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/system-health',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSystemHealthController::__invoke
* @see app/Http/Controllers/StaffSystemHealthController.php:20
* @route '/api/v1/staff/system-health'
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

const systemHealth = {
    index: Object.assign(index, index),
}

export default systemHealth