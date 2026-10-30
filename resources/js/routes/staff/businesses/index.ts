import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import premises from './premises'
import audit from './audit'
/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/businesses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
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

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:174
* @route '/admin/businesses/onboard'
*/
export const onboard = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: onboard.url(options),
    method: 'post',
})

onboard.definition = {
    methods: ["post"],
    url: '/admin/businesses/onboard',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:174
* @route '/admin/businesses/onboard'
*/
onboard.url = (options?: RouteQueryOptions) => {
    return onboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:174
* @route '/admin/businesses/onboard'
*/
onboard.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: onboard.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:174
* @route '/admin/businesses/onboard'
*/
const onboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: onboard.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:174
* @route '/admin/businesses/onboard'
*/
onboardForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: onboard.url(options),
    method: 'post',
})

onboard.form = onboardForm

const businesses = {
    index: Object.assign(index, index),
    premises: Object.assign(premises, premises),
    audit: Object.assign(audit, audit),
    onboard: Object.assign(onboard, onboard),
}

export default businesses