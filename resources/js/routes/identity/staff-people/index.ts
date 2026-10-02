import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\IdentityManagementController::record
* @see app/Http/Controllers/IdentityManagementController.php:31
* @route '/identity/staff-people'
*/
export const record = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: record.url(options),
    method: 'post',
})

record.definition = {
    methods: ["post"],
    url: '/identity/staff-people',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\IdentityManagementController::record
* @see app/Http/Controllers/IdentityManagementController.php:31
* @route '/identity/staff-people'
*/
record.url = (options?: RouteQueryOptions) => {
    return record.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\IdentityManagementController::record
* @see app/Http/Controllers/IdentityManagementController.php:31
* @route '/identity/staff-people'
*/
record.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: record.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::record
* @see app/Http/Controllers/IdentityManagementController.php:31
* @route '/identity/staff-people'
*/
const recordForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: record.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::record
* @see app/Http/Controllers/IdentityManagementController.php:31
* @route '/identity/staff-people'
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