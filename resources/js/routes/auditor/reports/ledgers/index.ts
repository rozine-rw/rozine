import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
export const show = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/auditor/reports/{report}/ledgers/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
show.url = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
show.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
show.head = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
const showForm = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
showForm.get = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProcedureController::show
* @see app/Http/Controllers/AuditorProcedureController.php:66
* @route '/auditor/reports/{report}/ledgers/{document}'
*/
showForm.head = (args: { report: string | number, document: string | number } | [report: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const ledgers = {
    show: Object.assign(show, show),
}

export default ledgers