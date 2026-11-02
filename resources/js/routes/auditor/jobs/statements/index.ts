import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::show
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
export const show = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/auditor/jobs/{assignment}/statements/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::show
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
show.url = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        document: args.document,
    }

    return show.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::show
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
show.get = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::show
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
show.head = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::show
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
const showForm = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::show
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
showForm.get = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::show
* @see app/Http/Controllers/AuditorStatementVerificationController.php:28
* @route '/auditor/jobs/{assignment}/statements/{document}'
*/
showForm.head = (args: { assignment: string | number, document: string | number } | [assignment: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const statements = {
    show: Object.assign(show, show),
}

export default statements