import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::verify
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:89
* @route '/admin/businesses/{business}/premises'
*/
export const verify = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verify.url(args, options),
    method: 'post',
})

verify.definition = {
    methods: ["post"],
    url: '/admin/businesses/{business}/premises',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::verify
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:89
* @route '/admin/businesses/{business}/premises'
*/
verify.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return verify.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::verify
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:89
* @route '/admin/businesses/{business}/premises'
*/
verify.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verify.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::verify
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:89
* @route '/admin/businesses/{business}/premises'
*/
const verifyForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: verify.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::verify
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:89
* @route '/admin/businesses/{business}/premises'
*/
verifyForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: verify.url(args, options),
    method: 'post',
})

verify.form = verifyForm

const premises = {
    verify: Object.assign(verify, verify),
}

export default premises