import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::approve
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
export const approve = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/admin/auditors/{party}/licence/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::approve
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
approve.url = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { party: args }
    }

    if (Array.isArray(args)) {
        args = {
            party: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        party: args.party,
    }

    return approve.definition.url
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::approve
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
approve.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::approve
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
const approveForm = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::approve
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
approveForm.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::reject
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
export const reject = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/admin/auditors/{party}/licence/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::reject
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
reject.url = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { party: args }
    }

    if (Array.isArray(args)) {
        args = {
            party: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        party: args.party,
    }

    return reject.definition.url
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::reject
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
reject.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::reject
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
const rejectForm = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::reject
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
rejectForm.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

const licence = {
    approve: Object.assign(approve, approve),
    reject: Object.assign(reject, reject),
}

export default licence