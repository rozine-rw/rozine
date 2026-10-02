import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::record
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:32
* @route '/api/v1/identity/staff-people'
*/
export const record = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: record.url(options),
    method: 'post',
})

record.definition = {
    methods: ["post"],
    url: '/api/v1/identity/staff-people',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::record
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:32
* @route '/api/v1/identity/staff-people'
*/
record.url = (options?: RouteQueryOptions) => {
    return record.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::record
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:32
* @route '/api/v1/identity/staff-people'
*/
record.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: record.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::record
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:32
* @route '/api/v1/identity/staff-people'
*/
const recordForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: record.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityManagementController::record
* @see app/Http/Controllers/Api/V1/IdentityManagementController.php:32
* @route '/api/v1/identity/staff-people'
*/
recordForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: record.url(options),
    method: 'post',
})

record.form = recordForm

const staffPeople = {
    record: Object.assign(record, record),
}

export default staffPeople