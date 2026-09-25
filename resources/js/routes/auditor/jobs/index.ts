import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\AuditorJobsController::index
* @see app/Http/Controllers/AuditorJobsController.php:31
* @route '/auditor/jobs'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/auditor/jobs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::index
* @see app/Http/Controllers/AuditorJobsController.php:31
* @route '/auditor/jobs'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorJobsController::index
* @see app/Http/Controllers/AuditorJobsController.php:31
* @route '/auditor/jobs'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::index
* @see app/Http/Controllers/AuditorJobsController.php:31
* @route '/auditor/jobs'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::index
* @see app/Http/Controllers/AuditorJobsController.php:31
* @route '/auditor/jobs'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::index
* @see app/Http/Controllers/AuditorJobsController.php:31
* @route '/auditor/jobs'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::index
* @see app/Http/Controllers/AuditorJobsController.php:31
* @route '/auditor/jobs'
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
* @see \App\Http\Controllers\AuditorJobsController::show
* @see app/Http/Controllers/AuditorJobsController.php:41
* @route '/auditor/jobs/{assignment}'
*/
export const show = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/auditor/jobs/{assignment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::show
* @see app/Http/Controllers/AuditorJobsController.php:41
* @route '/auditor/jobs/{assignment}'
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
* @see \App\Http\Controllers\AuditorJobsController::show
* @see app/Http/Controllers/AuditorJobsController.php:41
* @route '/auditor/jobs/{assignment}'
*/
show.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::show
* @see app/Http/Controllers/AuditorJobsController.php:41
* @route '/auditor/jobs/{assignment}'
*/
show.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::show
* @see app/Http/Controllers/AuditorJobsController.php:41
* @route '/auditor/jobs/{assignment}'
*/
const showForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::show
* @see app/Http/Controllers/AuditorJobsController.php:41
* @route '/auditor/jobs/{assignment}'
*/
showForm.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::show
* @see app/Http/Controllers/AuditorJobsController.php:41
* @route '/auditor/jobs/{assignment}'
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

/**
* @see \App\Http\Controllers\AuditorJobsController::accept
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
export const accept = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(args, options),
    method: 'post',
})

accept.definition = {
    methods: ["post"],
    url: '/auditor/jobs/{assignment}/accept',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::accept
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
accept.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return accept.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorJobsController::accept
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
accept.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::accept
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
const acceptForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::accept
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
acceptForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(args, options),
    method: 'post',
})

accept.form = acceptForm

/**
* @see \App\Http\Controllers\AuditorJobsController::decline
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
export const decline = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decline.url(args, options),
    method: 'post',
})

decline.definition = {
    methods: ["post"],
    url: '/auditor/jobs/{assignment}/decline',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::decline
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
decline.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return decline.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorJobsController::decline
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
decline.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decline.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::decline
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
const declineForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decline.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::decline
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
declineForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decline.url(args, options),
    method: 'post',
})

decline.form = declineForm

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
export const conflict = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: conflict.url(args, options),
    method: 'post',
})

conflict.definition = {
    methods: ["post"],
    url: '/auditor/jobs/{assignment}/conflict',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
conflict.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return conflict.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
conflict.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: conflict.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
const conflictForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: conflict.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
conflictForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: conflict.url(args, options),
    method: 'post',
})

conflict.form = conflictForm

const jobs = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    accept: Object.assign(accept, accept),
    decline: Object.assign(decline, decline),
    conflict: Object.assign(conflict, conflict),
    operations: Object.assign(operations, operations),
}

export default jobs