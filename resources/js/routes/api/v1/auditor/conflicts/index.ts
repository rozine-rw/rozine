import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::index
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:57
* @route '/api/v1/auditor/conflicts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/auditor/conflicts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::index
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:57
* @route '/api/v1/auditor/conflicts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::index
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:57
* @route '/api/v1/auditor/conflicts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::index
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:57
* @route '/api/v1/auditor/conflicts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::index
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:57
* @route '/api/v1/auditor/conflicts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::index
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:57
* @route '/api/v1/auditor/conflicts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::index
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:57
* @route '/api/v1/auditor/conflicts'
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
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::show
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:67
* @route '/api/v1/auditor/jobs/{assignment}/conflict'
*/
export const show = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/auditor/jobs/{assignment}/conflict',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::show
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:67
* @route '/api/v1/auditor/jobs/{assignment}/conflict'
*/
show.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { assignment: args }
    }

    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
    }

    return show.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::show
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:67
* @route '/api/v1/auditor/jobs/{assignment}/conflict'
*/
show.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::show
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:67
* @route '/api/v1/auditor/jobs/{assignment}/conflict'
*/
show.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::show
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:67
* @route '/api/v1/auditor/jobs/{assignment}/conflict'
*/
const showForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::show
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:67
* @route '/api/v1/auditor/jobs/{assignment}/conflict'
*/
showForm.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorJobsController::show
* @see app/Http/Controllers/Api/V1/AuditorJobsController.php:67
* @route '/api/v1/auditor/jobs/{assignment}/conflict'
*/
showForm.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const conflicts = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
}

export default conflicts