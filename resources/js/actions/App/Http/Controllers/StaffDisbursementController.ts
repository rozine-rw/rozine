import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/api/v1/staff/disbursements'
*/
const index5435a1a33e8b5d37a9d157fe79470a2b = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index5435a1a33e8b5d37a9d157fe79470a2b.url(options),
    method: 'get',
})

index5435a1a33e8b5d37a9d157fe79470a2b.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/disbursements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/api/v1/staff/disbursements'
*/
index5435a1a33e8b5d37a9d157fe79470a2b.url = (options?: RouteQueryOptions) => {
    return index5435a1a33e8b5d37a9d157fe79470a2b.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/api/v1/staff/disbursements'
*/
index5435a1a33e8b5d37a9d157fe79470a2b.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index5435a1a33e8b5d37a9d157fe79470a2b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/api/v1/staff/disbursements'
*/
index5435a1a33e8b5d37a9d157fe79470a2b.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index5435a1a33e8b5d37a9d157fe79470a2b.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/api/v1/staff/disbursements'
*/
const index5435a1a33e8b5d37a9d157fe79470a2bForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index5435a1a33e8b5d37a9d157fe79470a2b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/api/v1/staff/disbursements'
*/
index5435a1a33e8b5d37a9d157fe79470a2bForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index5435a1a33e8b5d37a9d157fe79470a2b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/api/v1/staff/disbursements'
*/
index5435a1a33e8b5d37a9d157fe79470a2bForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index5435a1a33e8b5d37a9d157fe79470a2b.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index5435a1a33e8b5d37a9d157fe79470a2b.form = index5435a1a33e8b5d37a9d157fe79470a2bForm
/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/admin/disbursements'
*/
const index3c80cf0e27ef4ec8afa159eb71c1be96 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index3c80cf0e27ef4ec8afa159eb71c1be96.url(options),
    method: 'get',
})

index3c80cf0e27ef4ec8afa159eb71c1be96.definition = {
    methods: ["get","head"],
    url: '/admin/disbursements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/admin/disbursements'
*/
index3c80cf0e27ef4ec8afa159eb71c1be96.url = (options?: RouteQueryOptions) => {
    return index3c80cf0e27ef4ec8afa159eb71c1be96.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/admin/disbursements'
*/
index3c80cf0e27ef4ec8afa159eb71c1be96.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index3c80cf0e27ef4ec8afa159eb71c1be96.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/admin/disbursements'
*/
index3c80cf0e27ef4ec8afa159eb71c1be96.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index3c80cf0e27ef4ec8afa159eb71c1be96.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/admin/disbursements'
*/
const index3c80cf0e27ef4ec8afa159eb71c1be96Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index3c80cf0e27ef4ec8afa159eb71c1be96.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/admin/disbursements'
*/
index3c80cf0e27ef4ec8afa159eb71c1be96Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index3c80cf0e27ef4ec8afa159eb71c1be96.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:28
* @route '/admin/disbursements'
*/
index3c80cf0e27ef4ec8afa159eb71c1be96Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index3c80cf0e27ef4ec8afa159eb71c1be96.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index3c80cf0e27ef4ec8afa159eb71c1be96.form = index3c80cf0e27ef4ec8afa159eb71c1be96Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffDisbursementController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/disbursements': index5435a1a33e8b5d37a9d157fe79470a2b,
    '/admin/disbursements': index3c80cf0e27ef4ec8afa159eb71c1be96,
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/api/v1/staff/disbursements/operations/{request_id}'
*/
const operation84754b86e23e0a52bed4fe5f32689e40 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation84754b86e23e0a52bed4fe5f32689e40.url(args, options),
    method: 'get',
})

operation84754b86e23e0a52bed4fe5f32689e40.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/disbursements/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/api/v1/staff/disbursements/operations/{request_id}'
*/
operation84754b86e23e0a52bed4fe5f32689e40.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return operation84754b86e23e0a52bed4fe5f32689e40.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/api/v1/staff/disbursements/operations/{request_id}'
*/
operation84754b86e23e0a52bed4fe5f32689e40.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation84754b86e23e0a52bed4fe5f32689e40.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/api/v1/staff/disbursements/operations/{request_id}'
*/
operation84754b86e23e0a52bed4fe5f32689e40.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation84754b86e23e0a52bed4fe5f32689e40.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/api/v1/staff/disbursements/operations/{request_id}'
*/
const operation84754b86e23e0a52bed4fe5f32689e40Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation84754b86e23e0a52bed4fe5f32689e40.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/api/v1/staff/disbursements/operations/{request_id}'
*/
operation84754b86e23e0a52bed4fe5f32689e40Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation84754b86e23e0a52bed4fe5f32689e40.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/api/v1/staff/disbursements/operations/{request_id}'
*/
operation84754b86e23e0a52bed4fe5f32689e40Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation84754b86e23e0a52bed4fe5f32689e40.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation84754b86e23e0a52bed4fe5f32689e40.form = operation84754b86e23e0a52bed4fe5f32689e40Form
/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/admin/disbursements/operations/{request_id}'
*/
const operation315abbdacb5534d3cef19e7b391a03c8 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation315abbdacb5534d3cef19e7b391a03c8.url(args, options),
    method: 'get',
})

operation315abbdacb5534d3cef19e7b391a03c8.definition = {
    methods: ["get","head"],
    url: '/admin/disbursements/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/admin/disbursements/operations/{request_id}'
*/
operation315abbdacb5534d3cef19e7b391a03c8.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return operation315abbdacb5534d3cef19e7b391a03c8.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/admin/disbursements/operations/{request_id}'
*/
operation315abbdacb5534d3cef19e7b391a03c8.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation315abbdacb5534d3cef19e7b391a03c8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/admin/disbursements/operations/{request_id}'
*/
operation315abbdacb5534d3cef19e7b391a03c8.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation315abbdacb5534d3cef19e7b391a03c8.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/admin/disbursements/operations/{request_id}'
*/
const operation315abbdacb5534d3cef19e7b391a03c8Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation315abbdacb5534d3cef19e7b391a03c8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/admin/disbursements/operations/{request_id}'
*/
operation315abbdacb5534d3cef19e7b391a03c8Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation315abbdacb5534d3cef19e7b391a03c8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::operation
* @see app/Http/Controllers/StaffDisbursementController.php:55
* @route '/admin/disbursements/operations/{request_id}'
*/
operation315abbdacb5534d3cef19e7b391a03c8Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation315abbdacb5534d3cef19e7b391a03c8.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation315abbdacb5534d3cef19e7b391a03c8.form = operation315abbdacb5534d3cef19e7b391a03c8Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffDisbursementController::operation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `operation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const operation = {
    '/api/v1/staff/disbursements/operations/{request_id}': operation84754b86e23e0a52bed4fe5f32689e40,
    '/admin/disbursements/operations/{request_id}': operation315abbdacb5534d3cef19e7b391a03c8,
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
const showc2b1a11cccfc79aa097c820c01709a5e = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showc2b1a11cccfc79aa097c820c01709a5e.url(args, options),
    method: 'get',
})

showc2b1a11cccfc79aa097c820c01709a5e.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/disbursements/{disbursement}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
showc2b1a11cccfc79aa097c820c01709a5e.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return showc2b1a11cccfc79aa097c820c01709a5e.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
showc2b1a11cccfc79aa097c820c01709a5e.get = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showc2b1a11cccfc79aa097c820c01709a5e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
showc2b1a11cccfc79aa097c820c01709a5e.head = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showc2b1a11cccfc79aa097c820c01709a5e.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
const showc2b1a11cccfc79aa097c820c01709a5eForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showc2b1a11cccfc79aa097c820c01709a5e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
showc2b1a11cccfc79aa097c820c01709a5eForm.get = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showc2b1a11cccfc79aa097c820c01709a5e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
showc2b1a11cccfc79aa097c820c01709a5eForm.head = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showc2b1a11cccfc79aa097c820c01709a5e.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showc2b1a11cccfc79aa097c820c01709a5e.form = showc2b1a11cccfc79aa097c820c01709a5eForm
/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/admin/disbursements/{disbursement}'
*/
const showb64b846488d1fe03c280c9082f4bd27c = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showb64b846488d1fe03c280c9082f4bd27c.url(args, options),
    method: 'get',
})

showb64b846488d1fe03c280c9082f4bd27c.definition = {
    methods: ["get","head"],
    url: '/admin/disbursements/{disbursement}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/admin/disbursements/{disbursement}'
*/
showb64b846488d1fe03c280c9082f4bd27c.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return showb64b846488d1fe03c280c9082f4bd27c.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/admin/disbursements/{disbursement}'
*/
showb64b846488d1fe03c280c9082f4bd27c.get = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showb64b846488d1fe03c280c9082f4bd27c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/admin/disbursements/{disbursement}'
*/
showb64b846488d1fe03c280c9082f4bd27c.head = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showb64b846488d1fe03c280c9082f4bd27c.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/admin/disbursements/{disbursement}'
*/
const showb64b846488d1fe03c280c9082f4bd27cForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showb64b846488d1fe03c280c9082f4bd27c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/admin/disbursements/{disbursement}'
*/
showb64b846488d1fe03c280c9082f4bd27cForm.get = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showb64b846488d1fe03c280c9082f4bd27c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:33
* @route '/admin/disbursements/{disbursement}'
*/
showb64b846488d1fe03c280c9082f4bd27cForm.head = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showb64b846488d1fe03c280c9082f4bd27c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showb64b846488d1fe03c280c9082f4bd27c.form = showb64b846488d1fe03c280c9082f4bd27cForm

/**
* Multiple routes resolve to \App\Http\Controllers\StaffDisbursementController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/staff/disbursements/{disbursement}': showc2b1a11cccfc79aa097c820c01709a5e,
    '/admin/disbursements/{disbursement}': showb64b846488d1fe03c280c9082f4bd27c,
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/api/v1/staff/disbursements/{disbursement}/step-up'
*/
const stepUp438f5fa45d2a11683a7c3a3597651401 = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stepUp438f5fa45d2a11683a7c3a3597651401.url(args, options),
    method: 'post',
})

stepUp438f5fa45d2a11683a7c3a3597651401.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/step-up',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/api/v1/staff/disbursements/{disbursement}/step-up'
*/
stepUp438f5fa45d2a11683a7c3a3597651401.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return stepUp438f5fa45d2a11683a7c3a3597651401.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/api/v1/staff/disbursements/{disbursement}/step-up'
*/
stepUp438f5fa45d2a11683a7c3a3597651401.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stepUp438f5fa45d2a11683a7c3a3597651401.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/api/v1/staff/disbursements/{disbursement}/step-up'
*/
const stepUp438f5fa45d2a11683a7c3a3597651401Form = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stepUp438f5fa45d2a11683a7c3a3597651401.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/api/v1/staff/disbursements/{disbursement}/step-up'
*/
stepUp438f5fa45d2a11683a7c3a3597651401Form.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stepUp438f5fa45d2a11683a7c3a3597651401.url(args, options),
    method: 'post',
})

stepUp438f5fa45d2a11683a7c3a3597651401.form = stepUp438f5fa45d2a11683a7c3a3597651401Form
/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/admin/disbursements/{disbursement}/step-up'
*/
const stepUpbfb6d04f22eaf597c60a5c89a9f80ed4 = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stepUpbfb6d04f22eaf597c60a5c89a9f80ed4.url(args, options),
    method: 'post',
})

stepUpbfb6d04f22eaf597c60a5c89a9f80ed4.definition = {
    methods: ["post"],
    url: '/admin/disbursements/{disbursement}/step-up',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/admin/disbursements/{disbursement}/step-up'
*/
stepUpbfb6d04f22eaf597c60a5c89a9f80ed4.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return stepUpbfb6d04f22eaf597c60a5c89a9f80ed4.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/admin/disbursements/{disbursement}/step-up'
*/
stepUpbfb6d04f22eaf597c60a5c89a9f80ed4.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stepUpbfb6d04f22eaf597c60a5c89a9f80ed4.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/admin/disbursements/{disbursement}/step-up'
*/
const stepUpbfb6d04f22eaf597c60a5c89a9f80ed4Form = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stepUpbfb6d04f22eaf597c60a5c89a9f80ed4.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::stepUp
* @see app/Http/Controllers/StaffDisbursementController.php:47
* @route '/admin/disbursements/{disbursement}/step-up'
*/
stepUpbfb6d04f22eaf597c60a5c89a9f80ed4Form.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stepUpbfb6d04f22eaf597c60a5c89a9f80ed4.url(args, options),
    method: 'post',
})

stepUpbfb6d04f22eaf597c60a5c89a9f80ed4.form = stepUpbfb6d04f22eaf597c60a5c89a9f80ed4Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffDisbursementController::stepUp, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `stepUp['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const stepUp = {
    '/api/v1/staff/disbursements/{disbursement}/step-up': stepUp438f5fa45d2a11683a7c3a3597651401,
    '/admin/disbursements/{disbursement}/step-up': stepUpbfb6d04f22eaf597c60a5c89a9f80ed4,
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/authorize'
*/
const commanda79abe32e4edd94fedd29f229b96310a = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commanda79abe32e4edd94fedd29f229b96310a.url(args, options),
    method: 'post',
})

commanda79abe32e4edd94fedd29f229b96310a.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/authorize',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/authorize'
*/
commanda79abe32e4edd94fedd29f229b96310a.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return commanda79abe32e4edd94fedd29f229b96310a.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/authorize'
*/
commanda79abe32e4edd94fedd29f229b96310a.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commanda79abe32e4edd94fedd29f229b96310a.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/authorize'
*/
const commanda79abe32e4edd94fedd29f229b96310aForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commanda79abe32e4edd94fedd29f229b96310a.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/authorize'
*/
commanda79abe32e4edd94fedd29f229b96310aForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commanda79abe32e4edd94fedd29f229b96310a.url(args, options),
    method: 'post',
})

commanda79abe32e4edd94fedd29f229b96310a.form = commanda79abe32e4edd94fedd29f229b96310aForm
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/approve'
*/
const commandc4a721d1a7ab6eaa7742565f252e20f5 = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commandc4a721d1a7ab6eaa7742565f252e20f5.url(args, options),
    method: 'post',
})

commandc4a721d1a7ab6eaa7742565f252e20f5.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/approve'
*/
commandc4a721d1a7ab6eaa7742565f252e20f5.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return commandc4a721d1a7ab6eaa7742565f252e20f5.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/approve'
*/
commandc4a721d1a7ab6eaa7742565f252e20f5.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commandc4a721d1a7ab6eaa7742565f252e20f5.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/approve'
*/
const commandc4a721d1a7ab6eaa7742565f252e20f5Form = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commandc4a721d1a7ab6eaa7742565f252e20f5.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/approve'
*/
commandc4a721d1a7ab6eaa7742565f252e20f5Form.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commandc4a721d1a7ab6eaa7742565f252e20f5.url(args, options),
    method: 'post',
})

commandc4a721d1a7ab6eaa7742565f252e20f5.form = commandc4a721d1a7ab6eaa7742565f252e20f5Form
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/reject'
*/
const commandb30a0a5fd991ba9904c5ff8d04e57374 = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commandb30a0a5fd991ba9904c5ff8d04e57374.url(args, options),
    method: 'post',
})

commandb30a0a5fd991ba9904c5ff8d04e57374.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/reject'
*/
commandb30a0a5fd991ba9904c5ff8d04e57374.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return commandb30a0a5fd991ba9904c5ff8d04e57374.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/reject'
*/
commandb30a0a5fd991ba9904c5ff8d04e57374.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commandb30a0a5fd991ba9904c5ff8d04e57374.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/reject'
*/
const commandb30a0a5fd991ba9904c5ff8d04e57374Form = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commandb30a0a5fd991ba9904c5ff8d04e57374.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/reject'
*/
commandb30a0a5fd991ba9904c5ff8d04e57374Form.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commandb30a0a5fd991ba9904c5ff8d04e57374.url(args, options),
    method: 'post',
})

commandb30a0a5fd991ba9904c5ff8d04e57374.form = commandb30a0a5fd991ba9904c5ff8d04e57374Form
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
const command244788699fc5a675afe594978c9e7dce = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command244788699fc5a675afe594978c9e7dce.url(args, options),
    method: 'post',
})

command244788699fc5a675afe594978c9e7dce.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/hold',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
command244788699fc5a675afe594978c9e7dce.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return command244788699fc5a675afe594978c9e7dce.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
command244788699fc5a675afe594978c9e7dce.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command244788699fc5a675afe594978c9e7dce.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
const command244788699fc5a675afe594978c9e7dceForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command244788699fc5a675afe594978c9e7dce.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
command244788699fc5a675afe594978c9e7dceForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command244788699fc5a675afe594978c9e7dce.url(args, options),
    method: 'post',
})

command244788699fc5a675afe594978c9e7dce.form = command244788699fc5a675afe594978c9e7dceForm
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
const commanda95538c2d086e51df6ef88d5294300bf = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commanda95538c2d086e51df6ef88d5294300bf.url(args, options),
    method: 'post',
})

commanda95538c2d086e51df6ef88d5294300bf.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/release-hold',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
commanda95538c2d086e51df6ef88d5294300bf.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return commanda95538c2d086e51df6ef88d5294300bf.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
commanda95538c2d086e51df6ef88d5294300bf.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commanda95538c2d086e51df6ef88d5294300bf.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
const commanda95538c2d086e51df6ef88d5294300bfForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commanda95538c2d086e51df6ef88d5294300bf.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
commanda95538c2d086e51df6ef88d5294300bfForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commanda95538c2d086e51df6ef88d5294300bf.url(args, options),
    method: 'post',
})

commanda95538c2d086e51df6ef88d5294300bf.form = commanda95538c2d086e51df6ef88d5294300bfForm
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
const command7438a9c8c1a4cf4995f9bc2dd016d39d = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command7438a9c8c1a4cf4995f9bc2dd016d39d.url(args, options),
    method: 'post',
})

command7438a9c8c1a4cf4995f9bc2dd016d39d.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/requery',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
command7438a9c8c1a4cf4995f9bc2dd016d39d.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return command7438a9c8c1a4cf4995f9bc2dd016d39d.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
command7438a9c8c1a4cf4995f9bc2dd016d39d.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command7438a9c8c1a4cf4995f9bc2dd016d39d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
const command7438a9c8c1a4cf4995f9bc2dd016d39dForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command7438a9c8c1a4cf4995f9bc2dd016d39d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
command7438a9c8c1a4cf4995f9bc2dd016d39dForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command7438a9c8c1a4cf4995f9bc2dd016d39d.url(args, options),
    method: 'post',
})

command7438a9c8c1a4cf4995f9bc2dd016d39d.form = command7438a9c8c1a4cf4995f9bc2dd016d39dForm
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/authorize'
*/
const command78286b79ee58c8f3882c2e139d3a7cee = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command78286b79ee58c8f3882c2e139d3a7cee.url(args, options),
    method: 'post',
})

command78286b79ee58c8f3882c2e139d3a7cee.definition = {
    methods: ["post"],
    url: '/admin/disbursements/{disbursement}/authorize',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/authorize'
*/
command78286b79ee58c8f3882c2e139d3a7cee.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return command78286b79ee58c8f3882c2e139d3a7cee.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/authorize'
*/
command78286b79ee58c8f3882c2e139d3a7cee.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command78286b79ee58c8f3882c2e139d3a7cee.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/authorize'
*/
const command78286b79ee58c8f3882c2e139d3a7ceeForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command78286b79ee58c8f3882c2e139d3a7cee.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/authorize'
*/
command78286b79ee58c8f3882c2e139d3a7ceeForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command78286b79ee58c8f3882c2e139d3a7cee.url(args, options),
    method: 'post',
})

command78286b79ee58c8f3882c2e139d3a7cee.form = command78286b79ee58c8f3882c2e139d3a7ceeForm
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/approve'
*/
const command5c074ddbec0ee82c5afbbc728f4363ae = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command5c074ddbec0ee82c5afbbc728f4363ae.url(args, options),
    method: 'post',
})

command5c074ddbec0ee82c5afbbc728f4363ae.definition = {
    methods: ["post"],
    url: '/admin/disbursements/{disbursement}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/approve'
*/
command5c074ddbec0ee82c5afbbc728f4363ae.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return command5c074ddbec0ee82c5afbbc728f4363ae.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/approve'
*/
command5c074ddbec0ee82c5afbbc728f4363ae.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command5c074ddbec0ee82c5afbbc728f4363ae.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/approve'
*/
const command5c074ddbec0ee82c5afbbc728f4363aeForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command5c074ddbec0ee82c5afbbc728f4363ae.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/approve'
*/
command5c074ddbec0ee82c5afbbc728f4363aeForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command5c074ddbec0ee82c5afbbc728f4363ae.url(args, options),
    method: 'post',
})

command5c074ddbec0ee82c5afbbc728f4363ae.form = command5c074ddbec0ee82c5afbbc728f4363aeForm
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/reject'
*/
const command87f45b87dc15d524882e0c431da72725 = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command87f45b87dc15d524882e0c431da72725.url(args, options),
    method: 'post',
})

command87f45b87dc15d524882e0c431da72725.definition = {
    methods: ["post"],
    url: '/admin/disbursements/{disbursement}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/reject'
*/
command87f45b87dc15d524882e0c431da72725.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return command87f45b87dc15d524882e0c431da72725.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/reject'
*/
command87f45b87dc15d524882e0c431da72725.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command87f45b87dc15d524882e0c431da72725.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/reject'
*/
const command87f45b87dc15d524882e0c431da72725Form = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command87f45b87dc15d524882e0c431da72725.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/reject'
*/
command87f45b87dc15d524882e0c431da72725Form.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command87f45b87dc15d524882e0c431da72725.url(args, options),
    method: 'post',
})

command87f45b87dc15d524882e0c431da72725.form = command87f45b87dc15d524882e0c431da72725Form
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/hold'
*/
const command5f9accf2ac47f5cb4bc10cb623b33756 = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command5f9accf2ac47f5cb4bc10cb623b33756.url(args, options),
    method: 'post',
})

command5f9accf2ac47f5cb4bc10cb623b33756.definition = {
    methods: ["post"],
    url: '/admin/disbursements/{disbursement}/hold',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/hold'
*/
command5f9accf2ac47f5cb4bc10cb623b33756.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return command5f9accf2ac47f5cb4bc10cb623b33756.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/hold'
*/
command5f9accf2ac47f5cb4bc10cb623b33756.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command5f9accf2ac47f5cb4bc10cb623b33756.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/hold'
*/
const command5f9accf2ac47f5cb4bc10cb623b33756Form = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command5f9accf2ac47f5cb4bc10cb623b33756.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/hold'
*/
command5f9accf2ac47f5cb4bc10cb623b33756Form.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command5f9accf2ac47f5cb4bc10cb623b33756.url(args, options),
    method: 'post',
})

command5f9accf2ac47f5cb4bc10cb623b33756.form = command5f9accf2ac47f5cb4bc10cb623b33756Form
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/release-hold'
*/
const commanda0c13bd5be9473e5429e12997f4d6d7d = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commanda0c13bd5be9473e5429e12997f4d6d7d.url(args, options),
    method: 'post',
})

commanda0c13bd5be9473e5429e12997f4d6d7d.definition = {
    methods: ["post"],
    url: '/admin/disbursements/{disbursement}/release-hold',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/release-hold'
*/
commanda0c13bd5be9473e5429e12997f4d6d7d.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return commanda0c13bd5be9473e5429e12997f4d6d7d.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/release-hold'
*/
commanda0c13bd5be9473e5429e12997f4d6d7d.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commanda0c13bd5be9473e5429e12997f4d6d7d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/release-hold'
*/
const commanda0c13bd5be9473e5429e12997f4d6d7dForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commanda0c13bd5be9473e5429e12997f4d6d7d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/release-hold'
*/
commanda0c13bd5be9473e5429e12997f4d6d7dForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commanda0c13bd5be9473e5429e12997f4d6d7d.url(args, options),
    method: 'post',
})

commanda0c13bd5be9473e5429e12997f4d6d7d.form = commanda0c13bd5be9473e5429e12997f4d6d7dForm
/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/requery'
*/
const command9cefe3374e0707fa61de841335d492c1 = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command9cefe3374e0707fa61de841335d492c1.url(args, options),
    method: 'post',
})

command9cefe3374e0707fa61de841335d492c1.definition = {
    methods: ["post"],
    url: '/admin/disbursements/{disbursement}/requery',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/requery'
*/
command9cefe3374e0707fa61de841335d492c1.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return command9cefe3374e0707fa61de841335d492c1.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/requery'
*/
command9cefe3374e0707fa61de841335d492c1.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: command9cefe3374e0707fa61de841335d492c1.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/requery'
*/
const command9cefe3374e0707fa61de841335d492c1Form = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command9cefe3374e0707fa61de841335d492c1.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::command
* @see app/Http/Controllers/StaffDisbursementController.php:38
* @route '/admin/disbursements/{disbursement}/requery'
*/
command9cefe3374e0707fa61de841335d492c1Form.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: command9cefe3374e0707fa61de841335d492c1.url(args, options),
    method: 'post',
})

command9cefe3374e0707fa61de841335d492c1.form = command9cefe3374e0707fa61de841335d492c1Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffDisbursementController::command, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `command['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const command = {
    '/api/v1/staff/disbursements/{disbursement}/authorize': commanda79abe32e4edd94fedd29f229b96310a,
    '/api/v1/staff/disbursements/{disbursement}/approve': commandc4a721d1a7ab6eaa7742565f252e20f5,
    '/api/v1/staff/disbursements/{disbursement}/reject': commandb30a0a5fd991ba9904c5ff8d04e57374,
    '/api/v1/staff/disbursements/{disbursement}/hold': command244788699fc5a675afe594978c9e7dce,
    '/api/v1/staff/disbursements/{disbursement}/release-hold': commanda95538c2d086e51df6ef88d5294300bf,
    '/api/v1/staff/disbursements/{disbursement}/requery': command7438a9c8c1a4cf4995f9bc2dd016d39d,
    '/admin/disbursements/{disbursement}/authorize': command78286b79ee58c8f3882c2e139d3a7cee,
    '/admin/disbursements/{disbursement}/approve': command5c074ddbec0ee82c5afbbc728f4363ae,
    '/admin/disbursements/{disbursement}/reject': command87f45b87dc15d524882e0c431da72725,
    '/admin/disbursements/{disbursement}/hold': command5f9accf2ac47f5cb4bc10cb623b33756,
    '/admin/disbursements/{disbursement}/release-hold': commanda0c13bd5be9473e5429e12997f4d6d7d,
    '/admin/disbursements/{disbursement}/requery': command9cefe3374e0707fa61de841335d492c1,
}

const StaffDisbursementController = { index, operation, show, stepUp, command }

export default StaffDisbursementController