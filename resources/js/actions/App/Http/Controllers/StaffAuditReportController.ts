import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffAuditReportController::index
* @see app/Http/Controllers/StaffAuditReportController.php:25
* @route '/admin/reports'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/reports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffAuditReportController::index
* @see app/Http/Controllers/StaffAuditReportController.php:25
* @route '/admin/reports'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditReportController::index
* @see app/Http/Controllers/StaffAuditReportController.php:25
* @route '/admin/reports'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::index
* @see app/Http/Controllers/StaffAuditReportController.php:25
* @route '/admin/reports'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::index
* @see app/Http/Controllers/StaffAuditReportController.php:25
* @route '/admin/reports'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::index
* @see app/Http/Controllers/StaffAuditReportController.php:25
* @route '/admin/reports'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::index
* @see app/Http/Controllers/StaffAuditReportController.php:25
* @route '/admin/reports'
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
* @see \App\Http\Controllers\StaffAuditReportController::remind
* @see app/Http/Controllers/StaffAuditReportController.php:35
* @route '/admin/reports/{assignment}/remind'
*/
export const remind = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: remind.url(args, options),
    method: 'post',
})

remind.definition = {
    methods: ["post"],
    url: '/admin/reports/{assignment}/remind',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffAuditReportController::remind
* @see app/Http/Controllers/StaffAuditReportController.php:35
* @route '/admin/reports/{assignment}/remind'
*/
remind.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return remind.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditReportController::remind
* @see app/Http/Controllers/StaffAuditReportController.php:35
* @route '/admin/reports/{assignment}/remind'
*/
remind.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: remind.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::remind
* @see app/Http/Controllers/StaffAuditReportController.php:35
* @route '/admin/reports/{assignment}/remind'
*/
const remindForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: remind.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::remind
* @see app/Http/Controllers/StaffAuditReportController.php:35
* @route '/admin/reports/{assignment}/remind'
*/
remindForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: remind.url(args, options),
    method: 'post',
})

remind.form = remindForm

/**
* @see \App\Http\Controllers\StaffAuditReportController::pdf
* @see app/Http/Controllers/StaffAuditReportController.php:42
* @route '/admin/reports/{report}/pdf'
*/
export const pdf = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdf.url(args, options),
    method: 'get',
})

pdf.definition = {
    methods: ["get","head"],
    url: '/admin/reports/{report}/pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffAuditReportController::pdf
* @see app/Http/Controllers/StaffAuditReportController.php:42
* @route '/admin/reports/{report}/pdf'
*/
pdf.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { report: args }
    }

    if (Array.isArray(args)) {
        args = {
            report: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        report: args.report,
    }

    return pdf.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditReportController::pdf
* @see app/Http/Controllers/StaffAuditReportController.php:42
* @route '/admin/reports/{report}/pdf'
*/
pdf.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::pdf
* @see app/Http/Controllers/StaffAuditReportController.php:42
* @route '/admin/reports/{report}/pdf'
*/
pdf.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pdf.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::pdf
* @see app/Http/Controllers/StaffAuditReportController.php:42
* @route '/admin/reports/{report}/pdf'
*/
const pdfForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::pdf
* @see app/Http/Controllers/StaffAuditReportController.php:42
* @route '/admin/reports/{report}/pdf'
*/
pdfForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditReportController::pdf
* @see app/Http/Controllers/StaffAuditReportController.php:42
* @route '/admin/reports/{report}/pdf'
*/
pdfForm.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pdf.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

pdf.form = pdfForm

const StaffAuditReportController = { index, remind, pdf }

export default StaffAuditReportController