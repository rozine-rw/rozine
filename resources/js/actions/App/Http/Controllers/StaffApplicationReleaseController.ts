import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/api/v1/staff/application-operations/{request_id}'
*/
const operationd171ffd321fab1386f6df9102f1c736a = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationd171ffd321fab1386f6df9102f1c736a.url(args, options),
    method: 'get',
})

operationd171ffd321fab1386f6df9102f1c736a.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/application-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/api/v1/staff/application-operations/{request_id}'
*/
operationd171ffd321fab1386f6df9102f1c736a.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return operationd171ffd321fab1386f6df9102f1c736a.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/api/v1/staff/application-operations/{request_id}'
*/
operationd171ffd321fab1386f6df9102f1c736a.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationd171ffd321fab1386f6df9102f1c736a.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/api/v1/staff/application-operations/{request_id}'
*/
operationd171ffd321fab1386f6df9102f1c736a.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operationd171ffd321fab1386f6df9102f1c736a.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/api/v1/staff/application-operations/{request_id}'
*/
const operationd171ffd321fab1386f6df9102f1c736aForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationd171ffd321fab1386f6df9102f1c736a.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/api/v1/staff/application-operations/{request_id}'
*/
operationd171ffd321fab1386f6df9102f1c736aForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationd171ffd321fab1386f6df9102f1c736a.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/api/v1/staff/application-operations/{request_id}'
*/
operationd171ffd321fab1386f6df9102f1c736aForm.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationd171ffd321fab1386f6df9102f1c736a.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operationd171ffd321fab1386f6df9102f1c736a.form = operationd171ffd321fab1386f6df9102f1c736aForm
/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/admin/application-operations/{request_id}'
*/
const operationca9e2d8a5704d565ac878482d2a3b8c9 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationca9e2d8a5704d565ac878482d2a3b8c9.url(args, options),
    method: 'get',
})

operationca9e2d8a5704d565ac878482d2a3b8c9.definition = {
    methods: ["get","head"],
    url: '/admin/application-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/admin/application-operations/{request_id}'
*/
operationca9e2d8a5704d565ac878482d2a3b8c9.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return operationca9e2d8a5704d565ac878482d2a3b8c9.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/admin/application-operations/{request_id}'
*/
operationca9e2d8a5704d565ac878482d2a3b8c9.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationca9e2d8a5704d565ac878482d2a3b8c9.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/admin/application-operations/{request_id}'
*/
operationca9e2d8a5704d565ac878482d2a3b8c9.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operationca9e2d8a5704d565ac878482d2a3b8c9.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/admin/application-operations/{request_id}'
*/
const operationca9e2d8a5704d565ac878482d2a3b8c9Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationca9e2d8a5704d565ac878482d2a3b8c9.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/admin/application-operations/{request_id}'
*/
operationca9e2d8a5704d565ac878482d2a3b8c9Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationca9e2d8a5704d565ac878482d2a3b8c9.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::operation
* @see app/Http/Controllers/StaffApplicationReleaseController.php:44
* @route '/admin/application-operations/{request_id}'
*/
operationca9e2d8a5704d565ac878482d2a3b8c9Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationca9e2d8a5704d565ac878482d2a3b8c9.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operationca9e2d8a5704d565ac878482d2a3b8c9.form = operationca9e2d8a5704d565ac878482d2a3b8c9Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffApplicationReleaseController::operation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `operation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const operation = {
    '/api/v1/staff/application-operations/{request_id}': operationd171ffd321fab1386f6df9102f1c736a,
    '/admin/application-operations/{request_id}': operationca9e2d8a5704d565ac878482d2a3b8c9,
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/api/v1/staff/applications'
*/
const index756f07527b4cca5e78a1d6fb220fba74 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index756f07527b4cca5e78a1d6fb220fba74.url(options),
    method: 'get',
})

index756f07527b4cca5e78a1d6fb220fba74.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/applications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/api/v1/staff/applications'
*/
index756f07527b4cca5e78a1d6fb220fba74.url = (options?: RouteQueryOptions) => {
    return index756f07527b4cca5e78a1d6fb220fba74.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/api/v1/staff/applications'
*/
index756f07527b4cca5e78a1d6fb220fba74.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index756f07527b4cca5e78a1d6fb220fba74.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/api/v1/staff/applications'
*/
index756f07527b4cca5e78a1d6fb220fba74.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index756f07527b4cca5e78a1d6fb220fba74.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/api/v1/staff/applications'
*/
const index756f07527b4cca5e78a1d6fb220fba74Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index756f07527b4cca5e78a1d6fb220fba74.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/api/v1/staff/applications'
*/
index756f07527b4cca5e78a1d6fb220fba74Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index756f07527b4cca5e78a1d6fb220fba74.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/api/v1/staff/applications'
*/
index756f07527b4cca5e78a1d6fb220fba74Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index756f07527b4cca5e78a1d6fb220fba74.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index756f07527b4cca5e78a1d6fb220fba74.form = index756f07527b4cca5e78a1d6fb220fba74Form
/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/admin/applications'
*/
const index671f81e7531403dc53b140c56cc77a26 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index671f81e7531403dc53b140c56cc77a26.url(options),
    method: 'get',
})

index671f81e7531403dc53b140c56cc77a26.definition = {
    methods: ["get","head"],
    url: '/admin/applications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/admin/applications'
*/
index671f81e7531403dc53b140c56cc77a26.url = (options?: RouteQueryOptions) => {
    return index671f81e7531403dc53b140c56cc77a26.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/admin/applications'
*/
index671f81e7531403dc53b140c56cc77a26.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index671f81e7531403dc53b140c56cc77a26.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/admin/applications'
*/
index671f81e7531403dc53b140c56cc77a26.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index671f81e7531403dc53b140c56cc77a26.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/admin/applications'
*/
const index671f81e7531403dc53b140c56cc77a26Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index671f81e7531403dc53b140c56cc77a26.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/admin/applications'
*/
index671f81e7531403dc53b140c56cc77a26Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index671f81e7531403dc53b140c56cc77a26.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:22
* @route '/admin/applications'
*/
index671f81e7531403dc53b140c56cc77a26Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index671f81e7531403dc53b140c56cc77a26.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index671f81e7531403dc53b140c56cc77a26.form = index671f81e7531403dc53b140c56cc77a26Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffApplicationReleaseController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/applications': index756f07527b4cca5e78a1d6fb220fba74,
    '/admin/applications': index671f81e7531403dc53b140c56cc77a26,
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/api/v1/staff/applications/{application}'
*/
const show5ec96efb90040ae8058ef12ca818bbee = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show5ec96efb90040ae8058ef12ca818bbee.url(args, options),
    method: 'get',
})

show5ec96efb90040ae8058ef12ca818bbee.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/applications/{application}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/api/v1/staff/applications/{application}'
*/
show5ec96efb90040ae8058ef12ca818bbee.url = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { application: args }
    }

    if (Array.isArray(args)) {
        args = {
            application: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        application: args.application,
    }

    return show5ec96efb90040ae8058ef12ca818bbee.definition.url
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/api/v1/staff/applications/{application}'
*/
show5ec96efb90040ae8058ef12ca818bbee.get = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show5ec96efb90040ae8058ef12ca818bbee.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/api/v1/staff/applications/{application}'
*/
show5ec96efb90040ae8058ef12ca818bbee.head = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show5ec96efb90040ae8058ef12ca818bbee.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/api/v1/staff/applications/{application}'
*/
const show5ec96efb90040ae8058ef12ca818bbeeForm = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show5ec96efb90040ae8058ef12ca818bbee.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/api/v1/staff/applications/{application}'
*/
show5ec96efb90040ae8058ef12ca818bbeeForm.get = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show5ec96efb90040ae8058ef12ca818bbee.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/api/v1/staff/applications/{application}'
*/
show5ec96efb90040ae8058ef12ca818bbeeForm.head = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show5ec96efb90040ae8058ef12ca818bbee.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show5ec96efb90040ae8058ef12ca818bbee.form = show5ec96efb90040ae8058ef12ca818bbeeForm
/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/admin/applications/{application}'
*/
const showb1e797ef20f36746feab1af8bdfc047b = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showb1e797ef20f36746feab1af8bdfc047b.url(args, options),
    method: 'get',
})

showb1e797ef20f36746feab1af8bdfc047b.definition = {
    methods: ["get","head"],
    url: '/admin/applications/{application}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/admin/applications/{application}'
*/
showb1e797ef20f36746feab1af8bdfc047b.url = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { application: args }
    }

    if (Array.isArray(args)) {
        args = {
            application: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        application: args.application,
    }

    return showb1e797ef20f36746feab1af8bdfc047b.definition.url
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/admin/applications/{application}'
*/
showb1e797ef20f36746feab1af8bdfc047b.get = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showb1e797ef20f36746feab1af8bdfc047b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/admin/applications/{application}'
*/
showb1e797ef20f36746feab1af8bdfc047b.head = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showb1e797ef20f36746feab1af8bdfc047b.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/admin/applications/{application}'
*/
const showb1e797ef20f36746feab1af8bdfc047bForm = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showb1e797ef20f36746feab1af8bdfc047b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/admin/applications/{application}'
*/
showb1e797ef20f36746feab1af8bdfc047bForm.get = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showb1e797ef20f36746feab1af8bdfc047b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:31
* @route '/admin/applications/{application}'
*/
showb1e797ef20f36746feab1af8bdfc047bForm.head = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showb1e797ef20f36746feab1af8bdfc047b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showb1e797ef20f36746feab1af8bdfc047b.form = showb1e797ef20f36746feab1af8bdfc047bForm

/**
* Multiple routes resolve to \App\Http\Controllers\StaffApplicationReleaseController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/staff/applications/{application}': show5ec96efb90040ae8058ef12ca818bbee,
    '/admin/applications/{application}': showb1e797ef20f36746feab1af8bdfc047b,
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/api/v1/staff/applications/{application}/release'
*/
const release2bbc93740ebad828f5f3202531380e3b = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release2bbc93740ebad828f5f3202531380e3b.url(args, options),
    method: 'post',
})

release2bbc93740ebad828f5f3202531380e3b.definition = {
    methods: ["post"],
    url: '/api/v1/staff/applications/{application}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/api/v1/staff/applications/{application}/release'
*/
release2bbc93740ebad828f5f3202531380e3b.url = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { application: args }
    }

    if (Array.isArray(args)) {
        args = {
            application: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        application: args.application,
    }

    return release2bbc93740ebad828f5f3202531380e3b.definition.url
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/api/v1/staff/applications/{application}/release'
*/
release2bbc93740ebad828f5f3202531380e3b.post = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release2bbc93740ebad828f5f3202531380e3b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/api/v1/staff/applications/{application}/release'
*/
const release2bbc93740ebad828f5f3202531380e3bForm = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release2bbc93740ebad828f5f3202531380e3b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/api/v1/staff/applications/{application}/release'
*/
release2bbc93740ebad828f5f3202531380e3bForm.post = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release2bbc93740ebad828f5f3202531380e3b.url(args, options),
    method: 'post',
})

release2bbc93740ebad828f5f3202531380e3b.form = release2bbc93740ebad828f5f3202531380e3bForm
/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/admin/applications/{application}/release'
*/
const release20bcb0c907d03decd4aa999995604681 = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release20bcb0c907d03decd4aa999995604681.url(args, options),
    method: 'post',
})

release20bcb0c907d03decd4aa999995604681.definition = {
    methods: ["post"],
    url: '/admin/applications/{application}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/admin/applications/{application}/release'
*/
release20bcb0c907d03decd4aa999995604681.url = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { application: args }
    }

    if (Array.isArray(args)) {
        args = {
            application: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        application: args.application,
    }

    return release20bcb0c907d03decd4aa999995604681.definition.url
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/admin/applications/{application}/release'
*/
release20bcb0c907d03decd4aa999995604681.post = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release20bcb0c907d03decd4aa999995604681.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/admin/applications/{application}/release'
*/
const release20bcb0c907d03decd4aa999995604681Form = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release20bcb0c907d03decd4aa999995604681.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:38
* @route '/admin/applications/{application}/release'
*/
release20bcb0c907d03decd4aa999995604681Form.post = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release20bcb0c907d03decd4aa999995604681.url(args, options),
    method: 'post',
})

release20bcb0c907d03decd4aa999995604681.form = release20bcb0c907d03decd4aa999995604681Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffApplicationReleaseController::release, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `release['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const release = {
    '/api/v1/staff/applications/{application}/release': release2bbc93740ebad828f5f3202531380e3b,
    '/admin/applications/{application}/release': release20bcb0c907d03decd4aa999995604681,
}

const StaffApplicationReleaseController = { operation, index, show, release }

export default StaffApplicationReleaseController