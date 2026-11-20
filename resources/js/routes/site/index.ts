import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import investor from './investor'
import business from './business'
import waitlist from './waitlist'
/**
* @see \App\Http\Controllers\SiteController::join
* @see app/Http/Controllers/SiteController.php:110
* @route '/join'
*/
export const join = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: join.url(options),
    method: 'get',
})

join.definition = {
    methods: ["get","head"],
    url: '/join',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteController::join
* @see app/Http/Controllers/SiteController.php:110
* @route '/join'
*/
join.url = (options?: RouteQueryOptions) => {
    return join.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteController::join
* @see app/Http/Controllers/SiteController.php:110
* @route '/join'
*/
join.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: join.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SiteController::join
* @see app/Http/Controllers/SiteController.php:110
* @route '/join'
*/
join.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: join.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SiteController::join
* @see app/Http/Controllers/SiteController.php:110
* @route '/join'
*/
const joinForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: join.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SiteController::join
* @see app/Http/Controllers/SiteController.php:110
* @route '/join'
*/
joinForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: join.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SiteController::join
* @see app/Http/Controllers/SiteController.php:110
* @route '/join'
*/
joinForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: join.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

join.form = joinForm

const site = {
    join: Object.assign(join, join),
    investor: Object.assign(investor, investor),
    business: Object.assign(business, business),
    waitlist: Object.assign(waitlist, waitlist),
}

export default site