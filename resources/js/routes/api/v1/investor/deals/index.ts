import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
export const show = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals/{campaign}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
show.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return show.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
show.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
show.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
const showForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
showForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
showForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
export const prospectus = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: prospectus.url(args, options),
    method: 'get',
})

prospectus.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals/{campaign}/prospectus',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
prospectus.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return prospectus.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
prospectus.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: prospectus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
prospectus.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: prospectus.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
const prospectusForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: prospectus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
prospectusForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: prospectus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
prospectusForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: prospectus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

prospectus.form = prospectusForm

const deals = {
    show: Object.assign(show, show),
    prospectus: Object.assign(prospectus, prospectus),
}

export default deals