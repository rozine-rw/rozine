import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessAuditReportController::operation
* @see app/Http/Controllers/BusinessAuditReportController.php:42
* @route '/business/audit-report-operations/{request_id}'
*/
export const operation = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

operation.definition = {
    methods: ["get","head"],
    url: '/business/audit-report-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessAuditReportController::operation
* @see app/Http/Controllers/BusinessAuditReportController.php:42
* @route '/business/audit-report-operations/{request_id}'
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
* @see \App\Http\Controllers\BusinessAuditReportController::operation
* @see app/Http/Controllers/BusinessAuditReportController.php:42
* @route '/business/audit-report-operations/{request_id}'
*/
operation.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::operation
* @see app/Http/Controllers/BusinessAuditReportController.php:42
* @route '/business/audit-report-operations/{request_id}'
*/
operation.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::operation
* @see app/Http/Controllers/BusinessAuditReportController.php:42
* @route '/business/audit-report-operations/{request_id}'
*/
const operationForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::operation
* @see app/Http/Controllers/BusinessAuditReportController.php:42
* @route '/business/audit-report-operations/{request_id}'
*/
operationForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::operation
* @see app/Http/Controllers/BusinessAuditReportController.php:42
* @route '/business/audit-report-operations/{request_id}'
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

/**
* @see \App\Http\Controllers\BusinessAuditReportController::show
* @see app/Http/Controllers/BusinessAuditReportController.php:24
* @route '/business/{business}/audit-reports/{report}'
*/
export const show = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}/audit-reports/{report}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessAuditReportController::show
* @see app/Http/Controllers/BusinessAuditReportController.php:24
* @route '/business/{business}/audit-reports/{report}'
*/
show.url = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        report: args.report,
    }

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessAuditReportController::show
* @see app/Http/Controllers/BusinessAuditReportController.php:24
* @route '/business/{business}/audit-reports/{report}'
*/
show.get = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::show
* @see app/Http/Controllers/BusinessAuditReportController.php:24
* @route '/business/{business}/audit-reports/{report}'
*/
show.head = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::show
* @see app/Http/Controllers/BusinessAuditReportController.php:24
* @route '/business/{business}/audit-reports/{report}'
*/
const showForm = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::show
* @see app/Http/Controllers/BusinessAuditReportController.php:24
* @route '/business/{business}/audit-reports/{report}'
*/
showForm.get = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::show
* @see app/Http/Controllers/BusinessAuditReportController.php:24
* @route '/business/{business}/audit-reports/{report}'
*/
showForm.head = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\BusinessAuditReportController::cosign
* @see app/Http/Controllers/BusinessAuditReportController.php:34
* @route '/business/{business}/audit-reports/{report}/cosign'
*/
export const cosign = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cosign.url(args, options),
    method: 'post',
})

cosign.definition = {
    methods: ["post"],
    url: '/business/{business}/audit-reports/{report}/cosign',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessAuditReportController::cosign
* @see app/Http/Controllers/BusinessAuditReportController.php:34
* @route '/business/{business}/audit-reports/{report}/cosign'
*/
cosign.url = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        report: args.report,
    }

    return cosign.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessAuditReportController::cosign
* @see app/Http/Controllers/BusinessAuditReportController.php:34
* @route '/business/{business}/audit-reports/{report}/cosign'
*/
cosign.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cosign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::cosign
* @see app/Http/Controllers/BusinessAuditReportController.php:34
* @route '/business/{business}/audit-reports/{report}/cosign'
*/
const cosignForm = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cosign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessAuditReportController::cosign
* @see app/Http/Controllers/BusinessAuditReportController.php:34
* @route '/business/{business}/audit-reports/{report}/cosign'
*/
cosignForm.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cosign.url(args, options),
    method: 'post',
})

cosign.form = cosignForm

const BusinessAuditReportController = { operation, show, cosign }

export default BusinessAuditReportController