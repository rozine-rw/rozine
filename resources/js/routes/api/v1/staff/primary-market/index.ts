import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/primary-market',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffPrimaryMarketController::__invoke
* @see app/Http/Controllers/StaffPrimaryMarketController.php:24
* @route '/api/v1/staff/primary-market'
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

const primaryMarket = {
    index: Object.assign(index, index),
}

export default primaryMarket