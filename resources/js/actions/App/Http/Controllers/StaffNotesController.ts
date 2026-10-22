import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffNotesController::__invoke
* @see app/Http/Controllers/StaffNotesController.php:20
* @route '/admin/notes'
*/
const StaffNotesController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffNotesController.url(options),
    method: 'get',
})

StaffNotesController.definition = {
    methods: ["get","head"],
    url: '/admin/notes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffNotesController::__invoke
* @see app/Http/Controllers/StaffNotesController.php:20
* @route '/admin/notes'
*/
StaffNotesController.url = (options?: RouteQueryOptions) => {
    return StaffNotesController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffNotesController::__invoke
* @see app/Http/Controllers/StaffNotesController.php:20
* @route '/admin/notes'
*/
StaffNotesController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffNotesController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffNotesController::__invoke
* @see app/Http/Controllers/StaffNotesController.php:20
* @route '/admin/notes'
*/
StaffNotesController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffNotesController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffNotesController::__invoke
* @see app/Http/Controllers/StaffNotesController.php:20
* @route '/admin/notes'
*/
const StaffNotesControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffNotesController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffNotesController::__invoke
* @see app/Http/Controllers/StaffNotesController.php:20
* @route '/admin/notes'
*/
StaffNotesControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffNotesController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffNotesController::__invoke
* @see app/Http/Controllers/StaffNotesController.php:20
* @route '/admin/notes'
*/
StaffNotesControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffNotesController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffNotesController.form = StaffNotesControllerForm

export default StaffNotesController