import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
export const data = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: data.url(options),
    method: 'get',
})

data.definition = {
    methods: ["get","head"],
    url: '/admin/repayments/data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
data.url = (options?: RouteQueryOptions) => {
    return data.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
data.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: data.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
data.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: data.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
const dataForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: data.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
dataForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: data.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffServicingController::__invoke
* @see app/Http/Controllers/StaffServicingController.php:16
* @route '/admin/repayments/data'
*/
dataForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: data.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

data.form = dataForm

const repayments = {
    data: Object.assign(data, data),
}

export default repayments