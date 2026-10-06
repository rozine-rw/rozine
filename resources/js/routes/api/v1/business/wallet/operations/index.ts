import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:44
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
export const show = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/wallet-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:44
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
show.url = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            request_id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        request_id: args.request_id,
    }

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:44
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
show.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:44
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
show.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:44
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
const showForm = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:44
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
showForm.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:44
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
showForm.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const operations = {
    show: Object.assign(show, show),
}

export default operations