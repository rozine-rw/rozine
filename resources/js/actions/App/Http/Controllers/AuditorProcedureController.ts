import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorProcedureController::start
* @see app/Http/Controllers/AuditorProcedureController.php:50
* @route '/auditor/jobs/{assignment}/report'
*/
export const start = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/auditor/jobs/{assignment}/report',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::start
* @see app/Http/Controllers/AuditorProcedureController.php:50
* @route '/auditor/jobs/{assignment}/report'
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
* @see \App\Http\Controllers\AuditorProcedureController::start
* @see app/Http/Controllers/AuditorProcedureController.php:50
* @route '/auditor/jobs/{assignment}/report'
*/
start.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::start
* @see app/Http/Controllers/AuditorProcedureController.php:50
* @route '/auditor/jobs/{assignment}/report'
*/
const startForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::start
* @see app/Http/Controllers/AuditorProcedureController.php:50
* @route '/auditor/jobs/{assignment}/report'
*/
startForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

start.form = startForm

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:100
* @route '/auditor/report-operations/{request_id}'
*/
export const operation = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

operation.definition = {
    methods: ["get","head"],
    url: '/auditor/report-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:100
* @route '/auditor/report-operations/{request_id}'
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
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:100
* @route '/auditor/report-operations/{request_id}'
*/
operation.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:100
* @route '/auditor/report-operations/{request_id}'
*/
operation.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:100
* @route '/auditor/report-operations/{request_id}'
*/
const operationForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:100
* @route '/auditor/report-operations/{request_id}'
*/
operationForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:100
* @route '/auditor/report-operations/{request_id}'
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
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:37
* @route '/auditor/reports/{report}'
*/
export const show = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/auditor/reports/{report}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:37
* @route '/auditor/reports/{report}'
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
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:37
* @route '/auditor/reports/{report}'
*/
show.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:37
* @route '/auditor/reports/{report}'
*/
show.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:37
* @route '/auditor/reports/{report}'
*/
const showForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:37
* @route '/auditor/reports/{report}'
*/
showForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:37
* @route '/auditor/reports/{report}'
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
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:57
* @route '/auditor/reports/{report}/statements/{document}'
*/
export const statement = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement.url(args, options),
    method: 'get',
})

statement.definition = {
    methods: ["get","head"],
    url: '/auditor/reports/{report}/statements/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:57
* @route '/auditor/reports/{report}/statements/{document}'
*/
statement.url = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            report: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        report: args.report,
        document: args.document,
    }

    return statement.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:57
* @route '/auditor/reports/{report}/statements/{document}'
*/
statement.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:57
* @route '/auditor/reports/{report}/statements/{document}'
*/
statement.head = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statement.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:57
* @route '/auditor/reports/{report}/statements/{document}'
*/
const statementForm = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:57
* @route '/auditor/reports/{report}/statements/{document}'
*/
statementForm.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:57
* @route '/auditor/reports/{report}/statements/{document}'
*/
statementForm.head = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

statement.form = statementForm

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
export const ledger = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ledger.url(args, options),
    method: 'get',
})

ledger.definition = {
    methods: ["get","head"],
    url: '/auditor/reports/{report}/ledgers/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
ledger.url = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            report: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        report: args.report,
        document: args.document,
    }

    return ledger.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
ledger.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ledger.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
ledger.head = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ledger.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
const ledgerForm = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ledger.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
ledgerForm.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ledger.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
ledgerForm.head = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ledger.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ledger.form = ledgerForm

/**
* @see \App\Http\Controllers\AuditorProcedureController::save
* @see app/Http/Controllers/AuditorProcedureController.php:86
* @route '/auditor/reports/{report}/steps'
*/
export const save = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/auditor/reports/{report}/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::save
* @see app/Http/Controllers/AuditorProcedureController.php:86
* @route '/auditor/reports/{report}/steps'
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
* @see \App\Http\Controllers\AuditorProcedureController::save
* @see app/Http/Controllers/AuditorProcedureController.php:86
* @route '/auditor/reports/{report}/steps'
*/
save.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::save
* @see app/Http/Controllers/AuditorProcedureController.php:86
* @route '/auditor/reports/{report}/steps'
*/
const saveForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::save
* @see app/Http/Controllers/AuditorProcedureController.php:86
* @route '/auditor/reports/{report}/steps'
*/
saveForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

save.form = saveForm

const AuditorProcedureController = { start, operation, show, statement, ledger, save }

export default AuditorProcedureController