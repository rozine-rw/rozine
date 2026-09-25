import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorProcedureController::start
* @see app/Http/Controllers/AuditorProcedureController.php:59
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
* @see app/Http/Controllers/AuditorProcedureController.php:59
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
* @see app/Http/Controllers/AuditorProcedureController.php:59
* @route '/auditor/jobs/{assignment}/report'
*/
start.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::start
* @see app/Http/Controllers/AuditorProcedureController.php:59
* @route '/auditor/jobs/{assignment}/report'
*/
const startForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::start
* @see app/Http/Controllers/AuditorProcedureController.php:59
* @route '/auditor/jobs/{assignment}/report'
*/
startForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

start.form = startForm

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:109
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
* @see app/Http/Controllers/AuditorProcedureController.php:109
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
* @see app/Http/Controllers/AuditorProcedureController.php:109
* @route '/auditor/report-operations/{request_id}'
*/
operation.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:109
* @route '/auditor/report-operations/{request_id}'
*/
operation.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:109
* @route '/auditor/report-operations/{request_id}'
*/
const operationForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:109
* @route '/auditor/report-operations/{request_id}'
*/
operationForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::operation
* @see app/Http/Controllers/AuditorProcedureController.php:109
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
* @see app/Http/Controllers/AuditorProcedureController.php:46
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
* @see app/Http/Controllers/AuditorProcedureController.php:46
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
* @see app/Http/Controllers/AuditorProcedureController.php:46
* @route '/auditor/reports/{report}'
*/
show.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:46
* @route '/auditor/reports/{report}'
*/
show.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:46
* @route '/auditor/reports/{report}'
*/
const showForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:46
* @route '/auditor/reports/{report}'
*/
showForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:46
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
* @see app/Http/Controllers/AuditorProcedureController.php:66
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
* @see app/Http/Controllers/AuditorProcedureController.php:66
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
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/statements/{document}'
*/
statement.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/statements/{document}'
*/
statement.head = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statement.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/statements/{document}'
*/
const statementForm = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/statements/{document}'
*/
statementForm.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::statement
* @see app/Http/Controllers/AuditorProcedureController.php:66
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
* @see app/Http/Controllers/AuditorProcedureController.php:75
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
* @see app/Http/Controllers/AuditorProcedureController.php:75
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
* @see app/Http/Controllers/AuditorProcedureController.php:75
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
ledger.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ledger.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:75
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
ledger.head = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ledger.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:75
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
const ledgerForm = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ledger.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:75
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
ledgerForm.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ledger.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::ledger
* @see app/Http/Controllers/AuditorProcedureController.php:75
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
* @see app/Http/Controllers/AuditorProcedureController.php:95
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
* @see app/Http/Controllers/AuditorProcedureController.php:95
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
* @see app/Http/Controllers/AuditorProcedureController.php:95
* @route '/auditor/reports/{report}/steps'
*/
save.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::save
* @see app/Http/Controllers/AuditorProcedureController.php:95
* @route '/auditor/reports/{report}/steps'
*/
const saveForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::save
* @see app/Http/Controllers/AuditorProcedureController.php:95
* @route '/auditor/reports/{report}/steps'
*/
saveForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

save.form = saveForm

/**
* @see \App\Http\Controllers\AuditorProcedureController::stepUp
* @see app/Http/Controllers/AuditorProcedureController.php:116
* @route '/auditor/reports/{report}/step-up'
*/
export const stepUp = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stepUp.url(args, options),
    method: 'post',
})

stepUp.definition = {
    methods: ["post"],
    url: '/auditor/reports/{report}/step-up',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::stepUp
* @see app/Http/Controllers/AuditorProcedureController.php:116
* @route '/auditor/reports/{report}/step-up'
*/
stepUp.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return stepUp.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProcedureController::stepUp
* @see app/Http/Controllers/AuditorProcedureController.php:116
* @route '/auditor/reports/{report}/step-up'
*/
stepUp.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stepUp.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::stepUp
* @see app/Http/Controllers/AuditorProcedureController.php:116
* @route '/auditor/reports/{report}/step-up'
*/
const stepUpForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stepUp.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::stepUp
* @see app/Http/Controllers/AuditorProcedureController.php:116
* @route '/auditor/reports/{report}/step-up'
*/
stepUpForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stepUp.url(args, options),
    method: 'post',
})

stepUp.form = stepUpForm

/**
* @see \App\Http\Controllers\AuditorProcedureController::seal
* @see app/Http/Controllers/AuditorProcedureController.php:122
* @route '/auditor/reports/{report}/seal'
*/
export const seal = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: seal.url(args, options),
    method: 'post',
})

seal.definition = {
    methods: ["post"],
    url: '/auditor/reports/{report}/seal',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::seal
* @see app/Http/Controllers/AuditorProcedureController.php:122
* @route '/auditor/reports/{report}/seal'
*/
seal.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return seal.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProcedureController::seal
* @see app/Http/Controllers/AuditorProcedureController.php:122
* @route '/auditor/reports/{report}/seal'
*/
seal.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: seal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::seal
* @see app/Http/Controllers/AuditorProcedureController.php:122
* @route '/auditor/reports/{report}/seal'
*/
const sealForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: seal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::seal
* @see app/Http/Controllers/AuditorProcedureController.php:122
* @route '/auditor/reports/{report}/seal'
*/
sealForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: seal.url(args, options),
    method: 'post',
})

seal.form = sealForm

/**
* @see \App\Http\Controllers\AuditorProcedureController::requestChanges
* @see app/Http/Controllers/AuditorProcedureController.php:131
* @route '/auditor/reports/{report}/request-changes'
*/
export const requestChanges = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestChanges.url(args, options),
    method: 'post',
})

requestChanges.definition = {
    methods: ["post"],
    url: '/auditor/reports/{report}/request-changes',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::requestChanges
* @see app/Http/Controllers/AuditorProcedureController.php:131
* @route '/auditor/reports/{report}/request-changes'
*/
requestChanges.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return requestChanges.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProcedureController::requestChanges
* @see app/Http/Controllers/AuditorProcedureController.php:131
* @route '/auditor/reports/{report}/request-changes'
*/
requestChanges.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestChanges.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::requestChanges
* @see app/Http/Controllers/AuditorProcedureController.php:131
* @route '/auditor/reports/{report}/request-changes'
*/
const requestChangesForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestChanges.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::requestChanges
* @see app/Http/Controllers/AuditorProcedureController.php:131
* @route '/auditor/reports/{report}/request-changes'
*/
requestChangesForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestChanges.url(args, options),
    method: 'post',
})

requestChanges.form = requestChangesForm

/**
* @see \App\Http\Controllers\AuditorProcedureController::reject
* @see app/Http/Controllers/AuditorProcedureController.php:136
* @route '/auditor/reports/{report}/reject'
*/
export const reject = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/auditor/reports/{report}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::reject
* @see app/Http/Controllers/AuditorProcedureController.php:136
* @route '/auditor/reports/{report}/reject'
*/
reject.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return reject.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProcedureController::reject
* @see app/Http/Controllers/AuditorProcedureController.php:136
* @route '/auditor/reports/{report}/reject'
*/
reject.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::reject
* @see app/Http/Controllers/AuditorProcedureController.php:136
* @route '/auditor/reports/{report}/reject'
*/
const rejectForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::reject
* @see app/Http/Controllers/AuditorProcedureController.php:136
* @route '/auditor/reports/{report}/reject'
*/
rejectForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

/**
* @see \App\Http\Controllers\AuditorProcedureController::amend
* @see app/Http/Controllers/AuditorProcedureController.php:141
* @route '/auditor/reports/{report}/amend'
*/
export const amend = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: amend.url(args, options),
    method: 'post',
})

amend.definition = {
    methods: ["post"],
    url: '/auditor/reports/{report}/amend',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::amend
* @see app/Http/Controllers/AuditorProcedureController.php:141
* @route '/auditor/reports/{report}/amend'
*/
amend.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return amend.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProcedureController::amend
* @see app/Http/Controllers/AuditorProcedureController.php:141
* @route '/auditor/reports/{report}/amend'
*/
amend.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: amend.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::amend
* @see app/Http/Controllers/AuditorProcedureController.php:141
* @route '/auditor/reports/{report}/amend'
*/
const amendForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: amend.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::amend
* @see app/Http/Controllers/AuditorProcedureController.php:141
* @route '/auditor/reports/{report}/amend'
*/
amendForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: amend.url(args, options),
    method: 'post',
})

amend.form = amendForm

const AuditorProcedureController = { start, operation, show, statement, ledger, save, stepUp, seal, requestChanges, reject, amend }

export default AuditorProcedureController