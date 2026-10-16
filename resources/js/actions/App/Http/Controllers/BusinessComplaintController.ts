import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessComplaintController::store
* @see app/Http/Controllers/BusinessComplaintController.php:29
* @route '/business/{business}/complaints'
*/
export const store = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/business/{business}/complaints',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessComplaintController::store
* @see app/Http/Controllers/BusinessComplaintController.php:29
* @route '/business/{business}/complaints'
*/
store.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return store.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessComplaintController::store
* @see app/Http/Controllers/BusinessComplaintController.php:29
* @route '/business/{business}/complaints'
*/
store.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessComplaintController::store
* @see app/Http/Controllers/BusinessComplaintController.php:29
* @route '/business/{business}/complaints'
*/
const storeForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessComplaintController::store
* @see app/Http/Controllers/BusinessComplaintController.php:29
* @route '/business/{business}/complaints'
*/
storeForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\BusinessComplaintController::reply
* @see app/Http/Controllers/BusinessComplaintController.php:37
* @route '/business/{business}/complaints/{complaint}/messages'
*/
export const reply = (args: { business: string | number, complaint: string | number } | [business: string | number, complaint: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reply.url(args, options),
    method: 'post',
})

reply.definition = {
    methods: ["post"],
    url: '/business/{business}/complaints/{complaint}/messages',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessComplaintController::reply
* @see app/Http/Controllers/BusinessComplaintController.php:37
* @route '/business/{business}/complaints/{complaint}/messages'
*/
reply.url = (args: { business: string | number, complaint: string | number } | [business: string | number, complaint: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            complaint: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        complaint: args.complaint,
    }

    return reply.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{complaint}', parsedArgs.complaint.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessComplaintController::reply
* @see app/Http/Controllers/BusinessComplaintController.php:37
* @route '/business/{business}/complaints/{complaint}/messages'
*/
reply.post = (args: { business: string | number, complaint: string | number } | [business: string | number, complaint: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reply.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessComplaintController::reply
* @see app/Http/Controllers/BusinessComplaintController.php:37
* @route '/business/{business}/complaints/{complaint}/messages'
*/
const replyForm = (args: { business: string | number, complaint: string | number } | [business: string | number, complaint: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reply.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessComplaintController::reply
* @see app/Http/Controllers/BusinessComplaintController.php:37
* @route '/business/{business}/complaints/{complaint}/messages'
*/
replyForm.post = (args: { business: string | number, complaint: string | number } | [business: string | number, complaint: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reply.url(args, options),
    method: 'post',
})

reply.form = replyForm

const BusinessComplaintController = { store, reply }

export default BusinessComplaintController