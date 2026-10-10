import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import licence from './licence'
/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/auditors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
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

const auditors = {
    index: Object.assign(index, index),
    licence: Object.assign(licence, licence),
}

export default auditors