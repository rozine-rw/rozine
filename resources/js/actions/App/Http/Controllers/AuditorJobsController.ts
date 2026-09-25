import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\AuditorJobsController::conflicts
* @see app/Http/Controllers/AuditorJobsController.php:51
* @route '/auditor/conflicts'
*/
export const conflicts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: conflicts.url(options),
    method: 'get',
})

conflicts.definition = {
    methods: ["get","head"],
    url: '/auditor/conflicts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::conflicts
* @see app/Http/Controllers/AuditorJobsController.php:51
* @route '/auditor/conflicts'
*/
conflicts.url = (options?: RouteQueryOptions) => {
    return conflicts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorJobsController::conflicts
* @see app/Http/Controllers/AuditorJobsController.php:51
* @route '/auditor/conflicts'
*/
conflicts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: conflicts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflicts
* @see app/Http/Controllers/AuditorJobsController.php:51
* @route '/auditor/conflicts'
*/
conflicts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: conflicts.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflicts
* @see app/Http/Controllers/AuditorJobsController.php:51
* @route '/auditor/conflicts'
*/
const conflictsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: conflicts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflicts
* @see app/Http/Controllers/AuditorJobsController.php:51
* @route '/auditor/conflicts'
*/
conflictsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: conflicts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflicts
* @see app/Http/Controllers/AuditorJobsController.php:51
* @route '/auditor/conflicts'
*/
conflictsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: conflicts.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

conflicts.form = conflictsForm

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:61
* @route '/auditor/jobs/{assignment}/conflict'
*/
export const conflict = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: conflict.url(args, options),
    method: 'get',
})

conflict.definition = {
    methods: ["get","head"],
    url: '/auditor/jobs/{assignment}/conflict',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:61
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
* @see app/Http/Controllers/AuditorJobsController.php:61
* @route '/auditor/jobs/{assignment}/conflict'
*/
conflict.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: conflict.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:61
* @route '/auditor/jobs/{assignment}/conflict'
*/
conflict.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: conflict.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:61
* @route '/auditor/jobs/{assignment}/conflict'
*/
const conflictForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: conflict.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:61
* @route '/auditor/jobs/{assignment}/conflict'
*/
conflictForm.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: conflict.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::conflict
* @see app/Http/Controllers/AuditorJobsController.php:61
* @route '/auditor/jobs/{assignment}/conflict'
*/
conflictForm.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: conflict.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

conflict.form = conflictForm

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
const respond7687dbc284e00061a73944ccd1558acb = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: respond7687dbc284e00061a73944ccd1558acb.url(args, options),
    method: 'post',
})

respond7687dbc284e00061a73944ccd1558acb.definition = {
    methods: ["post"],
    url: '/auditor/jobs/{assignment}/accept',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
respond7687dbc284e00061a73944ccd1558acb.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return respond7687dbc284e00061a73944ccd1558acb.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
respond7687dbc284e00061a73944ccd1558acb.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: respond7687dbc284e00061a73944ccd1558acb.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
const respond7687dbc284e00061a73944ccd1558acbForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: respond7687dbc284e00061a73944ccd1558acb.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/accept'
*/
respond7687dbc284e00061a73944ccd1558acbForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: respond7687dbc284e00061a73944ccd1558acb.url(args, options),
    method: 'post',
})

respond7687dbc284e00061a73944ccd1558acb.form = respond7687dbc284e00061a73944ccd1558acbForm
/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
const respond1bb5221730f020dd0e2516bea27acc70 = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: respond1bb5221730f020dd0e2516bea27acc70.url(args, options),
    method: 'post',
})

respond1bb5221730f020dd0e2516bea27acc70.definition = {
    methods: ["post"],
    url: '/auditor/jobs/{assignment}/decline',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
respond1bb5221730f020dd0e2516bea27acc70.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return respond1bb5221730f020dd0e2516bea27acc70.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
respond1bb5221730f020dd0e2516bea27acc70.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: respond1bb5221730f020dd0e2516bea27acc70.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
const respond1bb5221730f020dd0e2516bea27acc70Form = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: respond1bb5221730f020dd0e2516bea27acc70.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/decline'
*/
respond1bb5221730f020dd0e2516bea27acc70Form.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: respond1bb5221730f020dd0e2516bea27acc70.url(args, options),
    method: 'post',
})

respond1bb5221730f020dd0e2516bea27acc70.form = respond1bb5221730f020dd0e2516bea27acc70Form
/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
const respondc2ac61974fb25a7e0fc1820147f27896 = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: respondc2ac61974fb25a7e0fc1820147f27896.url(args, options),
    method: 'post',
})

respondc2ac61974fb25a7e0fc1820147f27896.definition = {
    methods: ["post"],
    url: '/auditor/jobs/{assignment}/conflict',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
respondc2ac61974fb25a7e0fc1820147f27896.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return respondc2ac61974fb25a7e0fc1820147f27896.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
respondc2ac61974fb25a7e0fc1820147f27896.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: respondc2ac61974fb25a7e0fc1820147f27896.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
const respondc2ac61974fb25a7e0fc1820147f27896Form = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: respondc2ac61974fb25a7e0fc1820147f27896.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::respond
* @see app/Http/Controllers/AuditorJobsController.php:70
* @route '/auditor/jobs/{assignment}/conflict'
*/
respondc2ac61974fb25a7e0fc1820147f27896Form.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: respondc2ac61974fb25a7e0fc1820147f27896.url(args, options),
    method: 'post',
})

respondc2ac61974fb25a7e0fc1820147f27896.form = respondc2ac61974fb25a7e0fc1820147f27896Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditorJobsController::respond, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `respond['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const respond = {
    '/auditor/jobs/{assignment}/accept': respond7687dbc284e00061a73944ccd1558acb,
    '/auditor/jobs/{assignment}/decline': respond1bb5221730f020dd0e2516bea27acc70,
    '/auditor/jobs/{assignment}/conflict': respondc2ac61974fb25a7e0fc1820147f27896,
}

/**
* @see \App\Http\Controllers\AuditorJobsController::operation
* @see app/Http/Controllers/AuditorJobsController.php:81
* @route '/auditor/assignment-operations/{request_id}'
*/
export const operation = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

operation.definition = {
    methods: ["get","head"],
    url: '/auditor/assignment-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorJobsController::operation
* @see app/Http/Controllers/AuditorJobsController.php:81
* @route '/auditor/assignment-operations/{request_id}'
*/
operation.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { request_id: args }
    }

    if (Array.isArray(args)) {
        args = {
            request_id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        request_id: args.request_id,
    }

    return operation.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorJobsController::operation
* @see app/Http/Controllers/AuditorJobsController.php:81
* @route '/auditor/assignment-operations/{request_id}'
*/
operation.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::operation
* @see app/Http/Controllers/AuditorJobsController.php:81
* @route '/auditor/assignment-operations/{request_id}'
*/
operation.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::operation
* @see app/Http/Controllers/AuditorJobsController.php:81
* @route '/auditor/assignment-operations/{request_id}'
*/
const operationForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::operation
* @see app/Http/Controllers/AuditorJobsController.php:81
* @route '/auditor/assignment-operations/{request_id}'
*/
operationForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorJobsController::operation
* @see app/Http/Controllers/AuditorJobsController.php:81
* @route '/auditor/assignment-operations/{request_id}'
*/
operationForm.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation.form = operationForm

const AuditorJobsController = { index, show, conflicts, conflict, respond, operation }

export default AuditorJobsController