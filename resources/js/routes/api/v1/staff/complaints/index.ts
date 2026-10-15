import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/complaints',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
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

const complaints = {
    index: Object.assign(index, index),
}

export default complaints