import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:39
* @route '/business/{business}/campaigns/{campaign}'
*/
export const show = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}/campaigns/{campaign}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:39
* @route '/business/{business}/campaigns/{campaign}'
*/
show.url = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            campaign: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        campaign: args.campaign,
    }

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:39
* @route '/business/{business}/campaigns/{campaign}'
*/
show.get = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:39
* @route '/business/{business}/campaigns/{campaign}'
*/
show.head = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:39
* @route '/business/{business}/campaigns/{campaign}'
*/
const showForm = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:39
* @route '/business/{business}/campaigns/{campaign}'
*/
showForm.get = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:39
* @route '/business/{business}/campaigns/{campaign}'
*/
showForm.head = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const campaigns = {
    show: Object.assign(show, show),
}

export default campaigns