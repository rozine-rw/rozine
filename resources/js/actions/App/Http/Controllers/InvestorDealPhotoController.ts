import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
const InvestorDealPhotoController = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorDealPhotoController.url(args, options),
    method: 'get',
})

InvestorDealPhotoController.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}/photos/{photo}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
InvestorDealPhotoController.url = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
            photo: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
        photo: args.photo,
    }

    return InvestorDealPhotoController.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace('{photo}', parsedArgs.photo.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
InvestorDealPhotoController.get = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: InvestorDealPhotoController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
InvestorDealPhotoController.head = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: InvestorDealPhotoController.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
const InvestorDealPhotoControllerForm = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorDealPhotoController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
InvestorDealPhotoControllerForm.get = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorDealPhotoController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
InvestorDealPhotoControllerForm.head = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: InvestorDealPhotoController.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

InvestorDealPhotoController.form = InvestorDealPhotoControllerForm

export default InvestorDealPhotoController