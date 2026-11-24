import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
const InvestorProspectusController40dc7806f84baa04772b4c50cf390547 = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorProspectusController40dc7806f84baa04772b4c50cf390547.url(args, options),
    method: 'get',
})

InvestorProspectusController40dc7806f84baa04772b4c50cf390547.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals/{campaign}/prospectus',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController40dc7806f84baa04772b4c50cf390547.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return InvestorProspectusController40dc7806f84baa04772b4c50cf390547.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController40dc7806f84baa04772b4c50cf390547.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorProspectusController40dc7806f84baa04772b4c50cf390547.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController40dc7806f84baa04772b4c50cf390547.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: InvestorProspectusController40dc7806f84baa04772b4c50cf390547.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
const InvestorProspectusController40dc7806f84baa04772b4c50cf390547Form = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusController40dc7806f84baa04772b4c50cf390547.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController40dc7806f84baa04772b4c50cf390547Form.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusController40dc7806f84baa04772b4c50cf390547.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/api/v1/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController40dc7806f84baa04772b4c50cf390547Form.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusController40dc7806f84baa04772b4c50cf390547.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

InvestorProspectusController40dc7806f84baa04772b4c50cf390547.form = InvestorProspectusController40dc7806f84baa04772b4c50cf390547Form
/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
const InvestorProspectusController51260e8a5c219802a03ed03c701a680e = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorProspectusController51260e8a5c219802a03ed03c701a680e.url(args, options),
    method: 'get',
})

InvestorProspectusController51260e8a5c219802a03ed03c701a680e.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}/prospectus',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController51260e8a5c219802a03ed03c701a680e.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return InvestorProspectusController51260e8a5c219802a03ed03c701a680e.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController51260e8a5c219802a03ed03c701a680e.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorProspectusController51260e8a5c219802a03ed03c701a680e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController51260e8a5c219802a03ed03c701a680e.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: InvestorProspectusController51260e8a5c219802a03ed03c701a680e.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
const InvestorProspectusController51260e8a5c219802a03ed03c701a680eForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusController51260e8a5c219802a03ed03c701a680e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController51260e8a5c219802a03ed03c701a680eForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusController51260e8a5c219802a03ed03c701a680e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
InvestorProspectusController51260e8a5c219802a03ed03c701a680eForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusController51260e8a5c219802a03ed03c701a680e.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

InvestorProspectusController51260e8a5c219802a03ed03c701a680e.form = InvestorProspectusController51260e8a5c219802a03ed03c701a680eForm

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorProspectusController::InvestorProspectusController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `InvestorProspectusController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const InvestorProspectusController = {
    '/api/v1/investor/deals/{campaign}/prospectus': InvestorProspectusController40dc7806f84baa04772b4c50cf390547,
    '/investor/deals/{campaign}/prospectus': InvestorProspectusController51260e8a5c219802a03ed03c701a680e,
}

export default InvestorProspectusController