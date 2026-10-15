import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorComplaintController::store
* @see app/Http/Controllers/InvestorComplaintController.php:26
* @route '/investor/complaints'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/investor/complaints',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorComplaintController::store
* @see app/Http/Controllers/InvestorComplaintController.php:26
* @route '/investor/complaints'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorComplaintController::store
* @see app/Http/Controllers/InvestorComplaintController.php:26
* @route '/investor/complaints'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorComplaintController::store
* @see app/Http/Controllers/InvestorComplaintController.php:26
* @route '/investor/complaints'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorComplaintController::store
* @see app/Http/Controllers/InvestorComplaintController.php:26
* @route '/investor/complaints'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\InvestorComplaintController::reply
* @see app/Http/Controllers/InvestorComplaintController.php:32
* @route '/investor/complaints/{complaint}/messages'
*/
export const reply = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reply.url(args, options),
    method: 'post',
})

reply.definition = {
    methods: ["post"],
    url: '/investor/complaints/{complaint}/messages',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorComplaintController::reply
* @see app/Http/Controllers/InvestorComplaintController.php:32
* @route '/investor/complaints/{complaint}/messages'
*/
reply.url = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { complaint: args }
    }

    if (Array.isArray(args)) {
        args = {
            complaint: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        complaint: args.complaint,
    }

    return reply.definition.url
            .replace('{complaint}', parsedArgs.complaint.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorComplaintController::reply
* @see app/Http/Controllers/InvestorComplaintController.php:32
* @route '/investor/complaints/{complaint}/messages'
*/
reply.post = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reply.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorComplaintController::reply
* @see app/Http/Controllers/InvestorComplaintController.php:32
* @route '/investor/complaints/{complaint}/messages'
*/
const replyForm = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reply.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorComplaintController::reply
* @see app/Http/Controllers/InvestorComplaintController.php:32
* @route '/investor/complaints/{complaint}/messages'
*/
replyForm.post = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reply.url(args, options),
    method: 'post',
})

reply.form = replyForm

const InvestorComplaintController = { store, reply }

export default InvestorComplaintController