import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::verify
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:100
* @route '/admin/auditors/{party}/office'
*/
export const verify = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verify.url(args, options),
    method: 'post',
})

verify.definition = {
    methods: ["post"],
    url: '/admin/auditors/{party}/office',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::verify
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:100
* @route '/admin/auditors/{party}/office'
*/
verify.url = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return verify.definition.url
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::verify
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:100
* @route '/admin/auditors/{party}/office'
*/
verify.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verify.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::verify
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:100
* @route '/admin/auditors/{party}/office'
*/
const verifyForm = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: verify.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::verify
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:100
* @route '/admin/auditors/{party}/office'
*/
verifyForm.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: verify.url(args, options),
    method: 'post',
})

verify.form = verifyForm

const office = {
    verify: Object.assign(verify, verify),
}

export default office