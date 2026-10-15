import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessContactController::company
* @see app/Http/Controllers/BusinessContactController.php:27
* @route '/business/{business}/profile/company'
*/
export const company = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: company.url(args, options),
    method: 'put',
})

company.definition = {
    methods: ["put"],
    url: '/business/{business}/profile/company',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\BusinessContactController::company
* @see app/Http/Controllers/BusinessContactController.php:27
* @route '/business/{business}/profile/company'
*/
company.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return company.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessContactController::company
* @see app/Http/Controllers/BusinessContactController.php:27
* @route '/business/{business}/profile/company'
*/
company.put = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: company.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\BusinessContactController::company
* @see app/Http/Controllers/BusinessContactController.php:27
* @route '/business/{business}/profile/company'
*/
const companyForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: company.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessContactController::company
* @see app/Http/Controllers/BusinessContactController.php:27
* @route '/business/{business}/profile/company'
*/
companyForm.put = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: company.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

company.form = companyForm

const profile = {
    company: Object.assign(company, company),
}

export default profile