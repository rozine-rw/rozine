import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import operations from './operations'
import disputes from './disputes'
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

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
export const dispute = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dispute.url(args, options),
    method: 'post',
})

dispute.definition = {
    methods: ["post"],
    url: '/business/{business}/audit-reports/{report}/dispute',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
dispute.url = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions) => {
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

    return dispute.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
dispute.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dispute.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
const disputeForm = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: dispute.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
disputeForm.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: dispute.url(args, options),
    method: 'post',
})

dispute.form = disputeForm

const auditReports = {
    operations: Object.assign(operations, operations),
    show: Object.assign(show, show),
    cosign: Object.assign(cosign, cosign),
    dispute: Object.assign(dispute, dispute),
    disputes: Object.assign(disputes, disputes),
}

export default auditReports