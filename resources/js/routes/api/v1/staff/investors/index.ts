import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/investors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorDirectoryController::index
* @see app/Http/Controllers/StaffInvestorDirectoryController.php:27
* @route '/api/v1/staff/investors'
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

const investors = {
    index: Object.assign(index, index),
}

export default investors