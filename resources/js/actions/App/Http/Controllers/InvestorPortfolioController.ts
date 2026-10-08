import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorPortfolioController::show
* @see app/Http/Controllers/InvestorPortfolioController.php:16
* @route '/investor/portfolio'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/investor/portfolio',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorPortfolioController::show
* @see app/Http/Controllers/InvestorPortfolioController.php:16
* @route '/investor/portfolio'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPortfolioController::show
* @see app/Http/Controllers/InvestorPortfolioController.php:16
* @route '/investor/portfolio'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPortfolioController::show
* @see app/Http/Controllers/InvestorPortfolioController.php:16
* @route '/investor/portfolio'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorPortfolioController::show
* @see app/Http/Controllers/InvestorPortfolioController.php:16
* @route '/investor/portfolio'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPortfolioController::show
* @see app/Http/Controllers/InvestorPortfolioController.php:16
* @route '/investor/portfolio'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPortfolioController::show
* @see app/Http/Controllers/InvestorPortfolioController.php:16
* @route '/investor/portfolio'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const InvestorPortfolioController = { show }

export default InvestorPortfolioController