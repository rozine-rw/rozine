import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:24
* @route '/'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:24
* @route '/'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:24
* @route '/'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:24
* @route '/'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:24
* @route '/'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:24
* @route '/'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:24
* @route '/'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\SiteController::storeInvestor
* @see app/Http/Controllers/SiteController.php:32
* @route '/investor'
*/
export const storeInvestor = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeInvestor.url(options),
    method: 'post',
})

storeInvestor.definition = {
    methods: ["post"],
    url: '/investor',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SiteController::storeInvestor
* @see app/Http/Controllers/SiteController.php:32
* @route '/investor'
*/
storeInvestor.url = (options?: RouteQueryOptions) => {
    return storeInvestor.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteController::storeInvestor
* @see app/Http/Controllers/SiteController.php:32
* @route '/investor'
*/
storeInvestor.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeInvestor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeInvestor
* @see app/Http/Controllers/SiteController.php:32
* @route '/investor'
*/
const storeInvestorForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeInvestor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeInvestor
* @see app/Http/Controllers/SiteController.php:32
* @route '/investor'
*/
storeInvestorForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeInvestor.url(options),
    method: 'post',
})

storeInvestor.form = storeInvestorForm

/**
* @see \App\Http\Controllers\SiteController::storeBusiness
* @see app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
export const storeBusiness = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBusiness.url(options),
    method: 'post',
})

storeBusiness.definition = {
    methods: ["post"],
    url: '/business',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SiteController::storeBusiness
* @see app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
storeBusiness.url = (options?: RouteQueryOptions) => {
    return storeBusiness.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteController::storeBusiness
* @see app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
storeBusiness.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBusiness.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeBusiness
* @see app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
const storeBusinessForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBusiness.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeBusiness
* @see app/Http/Controllers/SiteController.php:49
* @route '/business'
*/
storeBusinessForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBusiness.url(options),
    method: 'post',
})

storeBusiness.form = storeBusinessForm

const SiteController = { index, storeInvestor, storeBusiness }

export default SiteController