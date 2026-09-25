import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::show
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:24
* @route '/api/v1/business/{business}/audit-reports/{report}'
*/
export const show = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/audit-reports/{report}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::show
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:24
* @route '/api/v1/business/{business}/audit-reports/{report}'
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
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::show
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:24
* @route '/api/v1/business/{business}/audit-reports/{report}'
*/
show.get = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::show
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:24
* @route '/api/v1/business/{business}/audit-reports/{report}'
*/
show.head = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::show
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:24
* @route '/api/v1/business/{business}/audit-reports/{report}'
*/
const showForm = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::show
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:24
* @route '/api/v1/business/{business}/audit-reports/{report}'
*/
showForm.get = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::show
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:24
* @route '/api/v1/business/{business}/audit-reports/{report}'
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
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::cosign
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:34
* @route '/api/v1/business/{business}/audit-reports/{report}/cosign'
*/
export const cosign = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cosign.url(args, options),
    method: 'post',
})

cosign.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/audit-reports/{report}/cosign',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::cosign
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:34
* @route '/api/v1/business/{business}/audit-reports/{report}/cosign'
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
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::cosign
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:34
* @route '/api/v1/business/{business}/audit-reports/{report}/cosign'
*/
cosign.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cosign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::cosign
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:34
* @route '/api/v1/business/{business}/audit-reports/{report}/cosign'
*/
const cosignForm = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cosign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessAuditReportController::cosign
* @see app/Http/Controllers/Api/V1/BusinessAuditReportController.php:34
* @route '/api/v1/business/{business}/audit-reports/{report}/cosign'
*/
cosignForm.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cosign.url(args, options),
    method: 'post',
})

cosign.form = cosignForm

const auditReports = {
    operations: Object.assign(operations, operations),
    show: Object.assign(show, show),
    cosign: Object.assign(cosign, cosign),
}

export default auditReports