import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
import operations from './operations'
import statements from './statements'
import ledgers from './ledgers'
/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::start
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:50
* @route '/api/v1/auditor/jobs/{assignment}/report'
*/
export const start = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/api/v1/auditor/jobs/{assignment}/report',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::start
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:50
* @route '/api/v1/auditor/jobs/{assignment}/report'
*/
start.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return start.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::start
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:50
* @route '/api/v1/auditor/jobs/{assignment}/report'
*/
start.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::start
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:50
* @route '/api/v1/auditor/jobs/{assignment}/report'
*/
const startForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::start
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:50
* @route '/api/v1/auditor/jobs/{assignment}/report'
*/
startForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

start.form = startForm

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::show
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:37
* @route '/api/v1/auditor/reports/{report}'
*/
export const show = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/auditor/reports/{report}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::show
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:37
* @route '/api/v1/auditor/reports/{report}'
*/
show.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::show
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:37
* @route '/api/v1/auditor/reports/{report}'
*/
show.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::show
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:37
* @route '/api/v1/auditor/reports/{report}'
*/
show.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::show
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:37
* @route '/api/v1/auditor/reports/{report}'
*/
const showForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::show
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:37
* @route '/api/v1/auditor/reports/{report}'
*/
showForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::show
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:37
* @route '/api/v1/auditor/reports/{report}'
*/
showForm.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::save
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:86
* @route '/api/v1/auditor/reports/{report}/steps'
*/
export const save = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/api/v1/auditor/reports/{report}/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::save
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:86
* @route '/api/v1/auditor/reports/{report}/steps'
*/
save.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return save.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::save
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:86
* @route '/api/v1/auditor/reports/{report}/steps'
*/
save.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::save
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:86
* @route '/api/v1/auditor/reports/{report}/steps'
*/
const saveForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProcedureController::save
* @see app/Http/Controllers/Api/V1/AuditorProcedureController.php:86
* @route '/api/v1/auditor/reports/{report}/steps'
*/
saveForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

save.form = saveForm

const reports = {
    start: Object.assign(start, start),
    operations: Object.assign(operations, operations),
    show: Object.assign(show, show),
    statements: Object.assign(statements, statements),
    ledgers: Object.assign(ledgers, ledgers),
    save: Object.assign(save, save),
}

export default reports