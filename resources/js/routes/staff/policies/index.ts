import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffPolicyChangeController::propose
* @see app/Http/Controllers/StaffPolicyChangeController.php:33
* @route '/admin/policies/changes'
*/
export const propose = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: propose.url(options),
    method: 'post',
})

propose.definition = {
    methods: ["post"],
    url: '/admin/policies/changes',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::propose
* @see app/Http/Controllers/StaffPolicyChangeController.php:33
* @route '/admin/policies/changes'
*/
propose.url = (options?: RouteQueryOptions) => {
    return propose.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::propose
* @see app/Http/Controllers/StaffPolicyChangeController.php:33
* @route '/admin/policies/changes'
*/
propose.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: propose.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::propose
* @see app/Http/Controllers/StaffPolicyChangeController.php:33
* @route '/admin/policies/changes'
*/
const proposeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: propose.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::propose
* @see app/Http/Controllers/StaffPolicyChangeController.php:33
* @route '/admin/policies/changes'
*/
proposeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: propose.url(options),
    method: 'post',
})

propose.form = proposeForm

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::approve
* @see app/Http/Controllers/StaffPolicyChangeController.php:39
* @route '/admin/policies/changes/{change}/approve'
*/
export const approve = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/admin/policies/changes/{change}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::approve
* @see app/Http/Controllers/StaffPolicyChangeController.php:39
* @route '/admin/policies/changes/{change}/approve'
*/
approve.url = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { change: args }
    }

    if (Array.isArray(args)) {
        args = {
            change: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        change: args.change,
    }

    return approve.definition.url
            .replace('{change}', parsedArgs.change.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::approve
* @see app/Http/Controllers/StaffPolicyChangeController.php:39
* @route '/admin/policies/changes/{change}/approve'
*/
approve.post = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::approve
* @see app/Http/Controllers/StaffPolicyChangeController.php:39
* @route '/admin/policies/changes/{change}/approve'
*/
const approveForm = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::approve
* @see app/Http/Controllers/StaffPolicyChangeController.php:39
* @route '/admin/policies/changes/{change}/approve'
*/
approveForm.post = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::reject
* @see app/Http/Controllers/StaffPolicyChangeController.php:44
* @route '/admin/policies/changes/{change}/reject'
*/
export const reject = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/admin/policies/changes/{change}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::reject
* @see app/Http/Controllers/StaffPolicyChangeController.php:44
* @route '/admin/policies/changes/{change}/reject'
*/
reject.url = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { change: args }
    }

    if (Array.isArray(args)) {
        args = {
            change: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        change: args.change,
    }

    return reject.definition.url
            .replace('{change}', parsedArgs.change.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::reject
* @see app/Http/Controllers/StaffPolicyChangeController.php:44
* @route '/admin/policies/changes/{change}/reject'
*/
reject.post = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::reject
* @see app/Http/Controllers/StaffPolicyChangeController.php:44
* @route '/admin/policies/changes/{change}/reject'
*/
const rejectForm = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffPolicyChangeController::reject
* @see app/Http/Controllers/StaffPolicyChangeController.php:44
* @route '/admin/policies/changes/{change}/reject'
*/
rejectForm.post = (args: { change: string | number } | [change: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

const policies = {
    propose: Object.assign(propose, propose),
    approve: Object.assign(approve, approve),
    reject: Object.assign(reject, reject),
}

export default policies