import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/api/v1/business/{business}/repayments'
*/
const pay2ad7d97281c3dff34bb12cd1d03b6d4d = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay2ad7d97281c3dff34bb12cd1d03b6d4d.url(args, options),
    method: 'post',
})

pay2ad7d97281c3dff34bb12cd1d03b6d4d.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/repayments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/api/v1/business/{business}/repayments'
*/
pay2ad7d97281c3dff34bb12cd1d03b6d4d.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return pay2ad7d97281c3dff34bb12cd1d03b6d4d.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/api/v1/business/{business}/repayments'
*/
pay2ad7d97281c3dff34bb12cd1d03b6d4d.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay2ad7d97281c3dff34bb12cd1d03b6d4d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/api/v1/business/{business}/repayments'
*/
const pay2ad7d97281c3dff34bb12cd1d03b6d4dForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay2ad7d97281c3dff34bb12cd1d03b6d4d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/api/v1/business/{business}/repayments'
*/
pay2ad7d97281c3dff34bb12cd1d03b6d4dForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay2ad7d97281c3dff34bb12cd1d03b6d4d.url(args, options),
    method: 'post',
})

pay2ad7d97281c3dff34bb12cd1d03b6d4d.form = pay2ad7d97281c3dff34bb12cd1d03b6d4dForm
/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/business/{business}/repayments'
*/
const pay6a9937ee5fe55dd859e09800922b0c9f = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay6a9937ee5fe55dd859e09800922b0c9f.url(args, options),
    method: 'post',
})

pay6a9937ee5fe55dd859e09800922b0c9f.definition = {
    methods: ["post"],
    url: '/business/{business}/repayments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/business/{business}/repayments'
*/
pay6a9937ee5fe55dd859e09800922b0c9f.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return pay6a9937ee5fe55dd859e09800922b0c9f.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/business/{business}/repayments'
*/
pay6a9937ee5fe55dd859e09800922b0c9f.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay6a9937ee5fe55dd859e09800922b0c9f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/business/{business}/repayments'
*/
const pay6a9937ee5fe55dd859e09800922b0c9fForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay6a9937ee5fe55dd859e09800922b0c9f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::pay
* @see app/Http/Controllers/BusinessRepaymentController.php:16
* @route '/business/{business}/repayments'
*/
pay6a9937ee5fe55dd859e09800922b0c9fForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay6a9937ee5fe55dd859e09800922b0c9f.url(args, options),
    method: 'post',
})

pay6a9937ee5fe55dd859e09800922b0c9f.form = pay6a9937ee5fe55dd859e09800922b0c9fForm

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessRepaymentController::pay, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `pay['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const pay = {
    '/api/v1/business/{business}/repayments': pay2ad7d97281c3dff34bb12cd1d03b6d4d,
    '/business/{business}/repayments': pay6a9937ee5fe55dd859e09800922b0c9f,
}

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/api/v1/business/{business}/repayment-operations/{request_id}'
*/
const operation51da79a50d67617dfb7d31ed81d2dbc2 = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation51da79a50d67617dfb7d31ed81d2dbc2.url(args, options),
    method: 'get',
})

operation51da79a50d67617dfb7d31ed81d2dbc2.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/repayment-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/api/v1/business/{business}/repayment-operations/{request_id}'
*/
operation51da79a50d67617dfb7d31ed81d2dbc2.url = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            request_id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        request_id: args.request_id,
    }

    return operation51da79a50d67617dfb7d31ed81d2dbc2.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/api/v1/business/{business}/repayment-operations/{request_id}'
*/
operation51da79a50d67617dfb7d31ed81d2dbc2.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation51da79a50d67617dfb7d31ed81d2dbc2.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/api/v1/business/{business}/repayment-operations/{request_id}'
*/
operation51da79a50d67617dfb7d31ed81d2dbc2.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation51da79a50d67617dfb7d31ed81d2dbc2.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/api/v1/business/{business}/repayment-operations/{request_id}'
*/
const operation51da79a50d67617dfb7d31ed81d2dbc2Form = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation51da79a50d67617dfb7d31ed81d2dbc2.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/api/v1/business/{business}/repayment-operations/{request_id}'
*/
operation51da79a50d67617dfb7d31ed81d2dbc2Form.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation51da79a50d67617dfb7d31ed81d2dbc2.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/api/v1/business/{business}/repayment-operations/{request_id}'
*/
operation51da79a50d67617dfb7d31ed81d2dbc2Form.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation51da79a50d67617dfb7d31ed81d2dbc2.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation51da79a50d67617dfb7d31ed81d2dbc2.form = operation51da79a50d67617dfb7d31ed81d2dbc2Form
/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/business/{business}/repayment-operations/{request_id}'
*/
const operation87ce39e586b291eee97cd4ecd19f6c9c = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation87ce39e586b291eee97cd4ecd19f6c9c.url(args, options),
    method: 'get',
})

operation87ce39e586b291eee97cd4ecd19f6c9c.definition = {
    methods: ["get","head"],
    url: '/business/{business}/repayment-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/business/{business}/repayment-operations/{request_id}'
*/
operation87ce39e586b291eee97cd4ecd19f6c9c.url = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            request_id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        request_id: args.request_id,
    }

    return operation87ce39e586b291eee97cd4ecd19f6c9c.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/business/{business}/repayment-operations/{request_id}'
*/
operation87ce39e586b291eee97cd4ecd19f6c9c.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation87ce39e586b291eee97cd4ecd19f6c9c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/business/{business}/repayment-operations/{request_id}'
*/
operation87ce39e586b291eee97cd4ecd19f6c9c.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation87ce39e586b291eee97cd4ecd19f6c9c.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/business/{business}/repayment-operations/{request_id}'
*/
const operation87ce39e586b291eee97cd4ecd19f6c9cForm = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation87ce39e586b291eee97cd4ecd19f6c9c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/business/{business}/repayment-operations/{request_id}'
*/
operation87ce39e586b291eee97cd4ecd19f6c9cForm.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation87ce39e586b291eee97cd4ecd19f6c9c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessRepaymentController::operation
* @see app/Http/Controllers/BusinessRepaymentController.php:24
* @route '/business/{business}/repayment-operations/{request_id}'
*/
operation87ce39e586b291eee97cd4ecd19f6c9cForm.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation87ce39e586b291eee97cd4ecd19f6c9c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation87ce39e586b291eee97cd4ecd19f6c9c.form = operation87ce39e586b291eee97cd4ecd19f6c9cForm

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessRepaymentController::operation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `operation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const operation = {
    '/api/v1/business/{business}/repayment-operations/{request_id}': operation51da79a50d67617dfb7d31ed81d2dbc2,
    '/business/{business}/repayment-operations/{request_id}': operation87ce39e586b291eee97cd4ecd19f6c9c,
}

const BusinessRepaymentController = { pay, operation }

export default BusinessRepaymentController