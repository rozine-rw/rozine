import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
export const store = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/auditor/jobs/{assignment}/statement-verification',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
store.url = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
store.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
const storeForm = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorStatementVerificationController::store
* @see app/Http/Controllers/AuditorStatementVerificationController.php:42
* @route '/auditor/jobs/{assignment}/statement-verification'
*/
storeForm.post = (args: { assignment: string | number } | [assignment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const verification = {
    store: Object.assign(store, store),
    operations: Object.assign(operations, operations),
}

export default verification