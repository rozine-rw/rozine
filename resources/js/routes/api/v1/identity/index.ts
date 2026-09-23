import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
import people from './people'
import memberships from './memberships'
import activeRole from './active-role'
import roles from './roles'
/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/identity',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\IdentityController::__invoke
* @see app/Http/Controllers/Api/V1/IdentityController.php:14
* @route '/api/v1/identity'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const identity = {
    show: Object.assign(show, show),
    people: Object.assign(people, people),
    memberships: Object.assign(memberships, memberships),
    activeRole: Object.assign(activeRole, activeRole),
    roles: Object.assign(roles, roles),
}

export default identity