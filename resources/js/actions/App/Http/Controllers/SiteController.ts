import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:31
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
* @see app/Http/Controllers/SiteController.php:31
* @route '/'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:31
* @route '/'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:31
* @route '/'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:31
* @route '/'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:31
* @route '/'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SiteController::index
* @see app/Http/Controllers/SiteController.php:31
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

/**
* @see \App\Http\Controllers\SiteController::storeInvestor
* @see app/Http/Controllers/SiteController.php:41
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
* @see app/Http/Controllers/SiteController.php:41
* @route '/investor'
*/
storeInvestor.url = (options?: RouteQueryOptions) => {
    return storeInvestor.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteController::storeInvestor
* @see app/Http/Controllers/SiteController.php:41
* @route '/investor'
*/
storeInvestor.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeInvestor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeInvestor
* @see app/Http/Controllers/SiteController.php:41
* @route '/investor'
*/
const storeInvestorForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeInvestor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeInvestor
* @see app/Http/Controllers/SiteController.php:41
* @route '/investor'
*/
storeInvestorForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeInvestor.url(options),
    method: 'post',
})

storeInvestor.form = storeInvestorForm

/**
* @see \App\Http\Controllers\SiteController::storeBusiness
* @see app/Http/Controllers/SiteController.php:58
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
* @see app/Http/Controllers/SiteController.php:58
* @route '/business'
*/
storeBusiness.url = (options?: RouteQueryOptions) => {
    return storeBusiness.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteController::storeBusiness
* @see app/Http/Controllers/SiteController.php:58
* @route '/business'
*/
storeBusiness.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBusiness.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeBusiness
* @see app/Http/Controllers/SiteController.php:58
* @route '/business'
*/
const storeBusinessForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBusiness.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeBusiness
* @see app/Http/Controllers/SiteController.php:58
* @route '/business'
*/
storeBusinessForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBusiness.url(options),
    method: 'post',
})

storeBusiness.form = storeBusinessForm

/**
* @see \App\Http\Controllers\SiteController::storeWaitlist
* @see app/Http/Controllers/SiteController.php:83
* @route '/waitlist'
*/
export const storeWaitlist = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeWaitlist.url(options),
    method: 'post',
})

storeWaitlist.definition = {
    methods: ["post"],
    url: '/waitlist',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SiteController::storeWaitlist
* @see app/Http/Controllers/SiteController.php:83
* @route '/waitlist'
*/
storeWaitlist.url = (options?: RouteQueryOptions) => {
    return storeWaitlist.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteController::storeWaitlist
* @see app/Http/Controllers/SiteController.php:83
* @route '/waitlist'
*/
storeWaitlist.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeWaitlist.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeWaitlist
* @see app/Http/Controllers/SiteController.php:83
* @route '/waitlist'
*/
const storeWaitlistForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeWaitlist.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SiteController::storeWaitlist
* @see app/Http/Controllers/SiteController.php:83
* @route '/waitlist'
*/
storeWaitlistForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeWaitlist.url(options),
    method: 'post',
})

storeWaitlist.form = storeWaitlistForm

const SiteController = { index, join, storeInvestor, storeBusiness, storeWaitlist }

export default SiteController