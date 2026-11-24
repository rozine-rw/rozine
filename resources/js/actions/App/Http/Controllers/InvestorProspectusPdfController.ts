import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/api/v1/investor/deals/{campaign}/prospectus.pdf'
*/
const InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.url(args, options),
    method: 'get',
})

InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals/{campaign}/prospectus.pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/api/v1/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/api/v1/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/api/v1/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/api/v1/investor/deals/{campaign}/prospectus.pdf'
*/
const InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113ebaForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/api/v1/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113ebaForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/api/v1/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113ebaForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba.form = InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113ebaForm
/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
const InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.url(args, options),
    method: 'get',
})

InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}/prospectus.pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
const InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646cForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646cForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646cForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c.form = InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646cForm

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorProspectusPdfController::InvestorProspectusPdfController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `InvestorProspectusPdfController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const InvestorProspectusPdfController = {
    '/api/v1/investor/deals/{campaign}/prospectus.pdf': InvestorProspectusPdfController44f4fe9bcb0cc686964476eaff113eba,
    '/investor/deals/{campaign}/prospectus.pdf': InvestorProspectusPdfController4fb1bd9801f5dfc0e18066ed855c646c,
}

export default InvestorProspectusPdfController