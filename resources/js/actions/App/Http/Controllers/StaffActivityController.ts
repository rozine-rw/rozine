import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/api/v1/staff/activity'
*/
const index8726ce07a177c5d66af63cc44820e1cd = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index8726ce07a177c5d66af63cc44820e1cd.url(options),
    method: 'get',
})

index8726ce07a177c5d66af63cc44820e1cd.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/activity',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/api/v1/staff/activity'
*/
index8726ce07a177c5d66af63cc44820e1cd.url = (options?: RouteQueryOptions) => {
    return index8726ce07a177c5d66af63cc44820e1cd.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/api/v1/staff/activity'
*/
index8726ce07a177c5d66af63cc44820e1cd.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index8726ce07a177c5d66af63cc44820e1cd.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/api/v1/staff/activity'
*/
index8726ce07a177c5d66af63cc44820e1cd.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index8726ce07a177c5d66af63cc44820e1cd.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/api/v1/staff/activity'
*/
const index8726ce07a177c5d66af63cc44820e1cdForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8726ce07a177c5d66af63cc44820e1cd.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/api/v1/staff/activity'
*/
index8726ce07a177c5d66af63cc44820e1cdForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8726ce07a177c5d66af63cc44820e1cd.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/api/v1/staff/activity'
*/
index8726ce07a177c5d66af63cc44820e1cdForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8726ce07a177c5d66af63cc44820e1cd.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index8726ce07a177c5d66af63cc44820e1cd.form = index8726ce07a177c5d66af63cc44820e1cdForm
/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/admin/activity'
*/
const index683bf58d8794f19f8147b54b83f171e7 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index683bf58d8794f19f8147b54b83f171e7.url(options),
    method: 'get',
})

index683bf58d8794f19f8147b54b83f171e7.definition = {
    methods: ["get","head"],
    url: '/admin/activity',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/admin/activity'
*/
index683bf58d8794f19f8147b54b83f171e7.url = (options?: RouteQueryOptions) => {
    return index683bf58d8794f19f8147b54b83f171e7.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/admin/activity'
*/
index683bf58d8794f19f8147b54b83f171e7.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index683bf58d8794f19f8147b54b83f171e7.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/admin/activity'
*/
index683bf58d8794f19f8147b54b83f171e7.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index683bf58d8794f19f8147b54b83f171e7.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/admin/activity'
*/
const index683bf58d8794f19f8147b54b83f171e7Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index683bf58d8794f19f8147b54b83f171e7.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/admin/activity'
*/
index683bf58d8794f19f8147b54b83f171e7Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index683bf58d8794f19f8147b54b83f171e7.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffActivityController::index
* @see app/Http/Controllers/StaffActivityController.php:23
* @route '/admin/activity'
*/
index683bf58d8794f19f8147b54b83f171e7Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index683bf58d8794f19f8147b54b83f171e7.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index683bf58d8794f19f8147b54b83f171e7.form = index683bf58d8794f19f8147b54b83f171e7Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffActivityController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/activity': index8726ce07a177c5d66af63cc44820e1cd,
    '/admin/activity': index683bf58d8794f19f8147b54b83f171e7,
}

const StaffActivityController = { index }

export default StaffActivityController