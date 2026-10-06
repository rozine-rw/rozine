import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/api/v1/staff/audit-assignments/operations/{request_id}'
*/
const operationfd4d4190c0fa997f8917c6f4b8b5fea0 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationfd4d4190c0fa997f8917c6f4b8b5fea0.url(args, options),
    method: 'get',
})

operationfd4d4190c0fa997f8917c6f4b8b5fea0.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/audit-assignments/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/api/v1/staff/audit-assignments/operations/{request_id}'
*/
operationfd4d4190c0fa997f8917c6f4b8b5fea0.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return operationfd4d4190c0fa997f8917c6f4b8b5fea0.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/api/v1/staff/audit-assignments/operations/{request_id}'
*/
operationfd4d4190c0fa997f8917c6f4b8b5fea0.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationfd4d4190c0fa997f8917c6f4b8b5fea0.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/api/v1/staff/audit-assignments/operations/{request_id}'
*/
operationfd4d4190c0fa997f8917c6f4b8b5fea0.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operationfd4d4190c0fa997f8917c6f4b8b5fea0.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/api/v1/staff/audit-assignments/operations/{request_id}'
*/
const operationfd4d4190c0fa997f8917c6f4b8b5fea0Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationfd4d4190c0fa997f8917c6f4b8b5fea0.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/api/v1/staff/audit-assignments/operations/{request_id}'
*/
operationfd4d4190c0fa997f8917c6f4b8b5fea0Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationfd4d4190c0fa997f8917c6f4b8b5fea0.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/api/v1/staff/audit-assignments/operations/{request_id}'
*/
operationfd4d4190c0fa997f8917c6f4b8b5fea0Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationfd4d4190c0fa997f8917c6f4b8b5fea0.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operationfd4d4190c0fa997f8917c6f4b8b5fea0.form = operationfd4d4190c0fa997f8917c6f4b8b5fea0Form
/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/admin/audit-assignments/operations/{request_id}'
*/
const operationbc41e1ba7f16e2a7df9996676b612cd8 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationbc41e1ba7f16e2a7df9996676b612cd8.url(args, options),
    method: 'get',
})

operationbc41e1ba7f16e2a7df9996676b612cd8.definition = {
    methods: ["get","head"],
    url: '/admin/audit-assignments/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/admin/audit-assignments/operations/{request_id}'
*/
operationbc41e1ba7f16e2a7df9996676b612cd8.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return operationbc41e1ba7f16e2a7df9996676b612cd8.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/admin/audit-assignments/operations/{request_id}'
*/
operationbc41e1ba7f16e2a7df9996676b612cd8.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationbc41e1ba7f16e2a7df9996676b612cd8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/admin/audit-assignments/operations/{request_id}'
*/
operationbc41e1ba7f16e2a7df9996676b612cd8.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operationbc41e1ba7f16e2a7df9996676b612cd8.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/admin/audit-assignments/operations/{request_id}'
*/
const operationbc41e1ba7f16e2a7df9996676b612cd8Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationbc41e1ba7f16e2a7df9996676b612cd8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/admin/audit-assignments/operations/{request_id}'
*/
operationbc41e1ba7f16e2a7df9996676b612cd8Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationbc41e1ba7f16e2a7df9996676b612cd8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::operation
* @see app/Http/Controllers/AuditOperationsController.php:35
* @route '/admin/audit-assignments/operations/{request_id}'
*/
operationbc41e1ba7f16e2a7df9996676b612cd8Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationbc41e1ba7f16e2a7df9996676b612cd8.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operationbc41e1ba7f16e2a7df9996676b612cd8.form = operationbc41e1ba7f16e2a7df9996676b612cd8Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditOperationsController::operation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `operation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const operation = {
    '/api/v1/staff/audit-assignments/operations/{request_id}': operationfd4d4190c0fa997f8917c6f4b8b5fea0,
    '/admin/audit-assignments/operations/{request_id}': operationbc41e1ba7f16e2a7df9996676b612cd8,
}

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/api/v1/staff/audit-assignments/{assignment}'
*/
const showdd643f5f6fc6e1b6c94a89e89d8c3139 = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showdd643f5f6fc6e1b6c94a89e89d8c3139.url(args, options),
    method: 'get',
})

showdd643f5f6fc6e1b6c94a89e89d8c3139.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/audit-assignments/{assignment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/api/v1/staff/audit-assignments/{assignment}'
*/
showdd643f5f6fc6e1b6c94a89e89d8c3139.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return showdd643f5f6fc6e1b6c94a89e89d8c3139.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/api/v1/staff/audit-assignments/{assignment}'
*/
showdd643f5f6fc6e1b6c94a89e89d8c3139.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showdd643f5f6fc6e1b6c94a89e89d8c3139.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/api/v1/staff/audit-assignments/{assignment}'
*/
showdd643f5f6fc6e1b6c94a89e89d8c3139.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showdd643f5f6fc6e1b6c94a89e89d8c3139.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/api/v1/staff/audit-assignments/{assignment}'
*/
const showdd643f5f6fc6e1b6c94a89e89d8c3139Form = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showdd643f5f6fc6e1b6c94a89e89d8c3139.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/api/v1/staff/audit-assignments/{assignment}'
*/
showdd643f5f6fc6e1b6c94a89e89d8c3139Form.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showdd643f5f6fc6e1b6c94a89e89d8c3139.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/api/v1/staff/audit-assignments/{assignment}'
*/
showdd643f5f6fc6e1b6c94a89e89d8c3139Form.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showdd643f5f6fc6e1b6c94a89e89d8c3139.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showdd643f5f6fc6e1b6c94a89e89d8c3139.form = showdd643f5f6fc6e1b6c94a89e89d8c3139Form
/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
const show8992998ecc5ad53ddc520bb2312d4e79 = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show8992998ecc5ad53ddc520bb2312d4e79.url(args, options),
    method: 'get',
})

show8992998ecc5ad53ddc520bb2312d4e79.definition = {
    methods: ["get","head"],
    url: '/admin/audit-assignments/{assignment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
show8992998ecc5ad53ddc520bb2312d4e79.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show8992998ecc5ad53ddc520bb2312d4e79.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
show8992998ecc5ad53ddc520bb2312d4e79.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show8992998ecc5ad53ddc520bb2312d4e79.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
show8992998ecc5ad53ddc520bb2312d4e79.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show8992998ecc5ad53ddc520bb2312d4e79.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
const show8992998ecc5ad53ddc520bb2312d4e79Form = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show8992998ecc5ad53ddc520bb2312d4e79.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
show8992998ecc5ad53ddc520bb2312d4e79Form.get = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show8992998ecc5ad53ddc520bb2312d4e79.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::show
* @see app/Http/Controllers/AuditOperationsController.php:20
* @route '/admin/audit-assignments/{assignment}'
*/
show8992998ecc5ad53ddc520bb2312d4e79Form.head = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show8992998ecc5ad53ddc520bb2312d4e79.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show8992998ecc5ad53ddc520bb2312d4e79.form = show8992998ecc5ad53ddc520bb2312d4e79Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditOperationsController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/staff/audit-assignments/{assignment}': showdd643f5f6fc6e1b6c94a89e89d8c3139,
    '/admin/audit-assignments/{assignment}': show8992998ecc5ad53ddc520bb2312d4e79,
}

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/redispatch'
*/
const resolve49dbda9db6fd386eb4eb175937590a41 = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve49dbda9db6fd386eb4eb175937590a41.url(args, options),
    method: 'post',
})

resolve49dbda9db6fd386eb4eb175937590a41.definition = {
    methods: ["post"],
    url: '/api/v1/staff/audit-assignments/{assignment}/redispatch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/redispatch'
*/
resolve49dbda9db6fd386eb4eb175937590a41.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return resolve49dbda9db6fd386eb4eb175937590a41.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/redispatch'
*/
resolve49dbda9db6fd386eb4eb175937590a41.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve49dbda9db6fd386eb4eb175937590a41.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/redispatch'
*/
const resolve49dbda9db6fd386eb4eb175937590a41Form = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve49dbda9db6fd386eb4eb175937590a41.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/redispatch'
*/
resolve49dbda9db6fd386eb4eb175937590a41Form.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve49dbda9db6fd386eb4eb175937590a41.url(args, options),
    method: 'post',
})

resolve49dbda9db6fd386eb4eb175937590a41.form = resolve49dbda9db6fd386eb4eb175937590a41Form
/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/close'
*/
const resolve49573df9c14470d05a10c5eb6a68bc4d = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve49573df9c14470d05a10c5eb6a68bc4d.url(args, options),
    method: 'post',
})

resolve49573df9c14470d05a10c5eb6a68bc4d.definition = {
    methods: ["post"],
    url: '/api/v1/staff/audit-assignments/{assignment}/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/close'
*/
resolve49573df9c14470d05a10c5eb6a68bc4d.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return resolve49573df9c14470d05a10c5eb6a68bc4d.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/close'
*/
resolve49573df9c14470d05a10c5eb6a68bc4d.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve49573df9c14470d05a10c5eb6a68bc4d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/close'
*/
const resolve49573df9c14470d05a10c5eb6a68bc4dForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve49573df9c14470d05a10c5eb6a68bc4d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/api/v1/staff/audit-assignments/{assignment}/close'
*/
resolve49573df9c14470d05a10c5eb6a68bc4dForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve49573df9c14470d05a10c5eb6a68bc4d.url(args, options),
    method: 'post',
})

resolve49573df9c14470d05a10c5eb6a68bc4d.form = resolve49573df9c14470d05a10c5eb6a68bc4dForm
/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
const resolvee0b477214d7e4eafcd232a33158e84e3 = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolvee0b477214d7e4eafcd232a33158e84e3.url(args, options),
    method: 'post',
})

resolvee0b477214d7e4eafcd232a33158e84e3.definition = {
    methods: ["post"],
    url: '/admin/audit-assignments/{assignment}/redispatch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
resolvee0b477214d7e4eafcd232a33158e84e3.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return resolvee0b477214d7e4eafcd232a33158e84e3.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
resolvee0b477214d7e4eafcd232a33158e84e3.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolvee0b477214d7e4eafcd232a33158e84e3.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
const resolvee0b477214d7e4eafcd232a33158e84e3Form = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolvee0b477214d7e4eafcd232a33158e84e3.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/redispatch'
*/
resolvee0b477214d7e4eafcd232a33158e84e3Form.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolvee0b477214d7e4eafcd232a33158e84e3.url(args, options),
    method: 'post',
})

resolvee0b477214d7e4eafcd232a33158e84e3.form = resolvee0b477214d7e4eafcd232a33158e84e3Form
/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
const resolve09035c74ffeb3e17385b9bad9a7e2525 = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve09035c74ffeb3e17385b9bad9a7e2525.url(args, options),
    method: 'post',
})

resolve09035c74ffeb3e17385b9bad9a7e2525.definition = {
    methods: ["post"],
    url: '/admin/audit-assignments/{assignment}/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
resolve09035c74ffeb3e17385b9bad9a7e2525.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return resolve09035c74ffeb3e17385b9bad9a7e2525.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
resolve09035c74ffeb3e17385b9bad9a7e2525.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve09035c74ffeb3e17385b9bad9a7e2525.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
const resolve09035c74ffeb3e17385b9bad9a7e2525Form = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve09035c74ffeb3e17385b9bad9a7e2525.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditOperationsController::resolve
* @see app/Http/Controllers/AuditOperationsController.php:27
* @route '/admin/audit-assignments/{assignment}/close'
*/
resolve09035c74ffeb3e17385b9bad9a7e2525Form.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve09035c74ffeb3e17385b9bad9a7e2525.url(args, options),
    method: 'post',
})

resolve09035c74ffeb3e17385b9bad9a7e2525.form = resolve09035c74ffeb3e17385b9bad9a7e2525Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditOperationsController::resolve, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `resolve['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const resolve = {
    '/api/v1/staff/audit-assignments/{assignment}/redispatch': resolve49dbda9db6fd386eb4eb175937590a41,
    '/api/v1/staff/audit-assignments/{assignment}/close': resolve49573df9c14470d05a10c5eb6a68bc4d,
    '/admin/audit-assignments/{assignment}/redispatch': resolvee0b477214d7e4eafcd232a33158e84e3,
    '/admin/audit-assignments/{assignment}/close': resolve09035c74ffeb3e17385b9bad9a7e2525,
}

const AuditOperationsController = { operation, show, resolve }

export default AuditOperationsController