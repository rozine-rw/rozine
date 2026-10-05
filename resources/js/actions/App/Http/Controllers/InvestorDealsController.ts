import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/api/v1/investor/deals'
*/
const index0282307d754d45442065ef1c088d1c84 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index0282307d754d45442065ef1c088d1c84.url(options),
    method: 'get',
})

index0282307d754d45442065ef1c088d1c84.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/api/v1/investor/deals'
*/
index0282307d754d45442065ef1c088d1c84.url = (options?: RouteQueryOptions) => {
    return index0282307d754d45442065ef1c088d1c84.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/api/v1/investor/deals'
*/
index0282307d754d45442065ef1c088d1c84.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index0282307d754d45442065ef1c088d1c84.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/api/v1/investor/deals'
*/
index0282307d754d45442065ef1c088d1c84.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index0282307d754d45442065ef1c088d1c84.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/api/v1/investor/deals'
*/
const index0282307d754d45442065ef1c088d1c84Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index0282307d754d45442065ef1c088d1c84.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/api/v1/investor/deals'
*/
index0282307d754d45442065ef1c088d1c84Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index0282307d754d45442065ef1c088d1c84.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/api/v1/investor/deals'
*/
index0282307d754d45442065ef1c088d1c84Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index0282307d754d45442065ef1c088d1c84.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index0282307d754d45442065ef1c088d1c84.form = index0282307d754d45442065ef1c088d1c84Form
/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
const index242d64da7608c8eb584ec3751fc350b2 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index242d64da7608c8eb584ec3751fc350b2.url(options),
    method: 'get',
})

index242d64da7608c8eb584ec3751fc350b2.definition = {
    methods: ["get","head"],
    url: '/investor/deals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
index242d64da7608c8eb584ec3751fc350b2.url = (options?: RouteQueryOptions) => {
    return index242d64da7608c8eb584ec3751fc350b2.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
index242d64da7608c8eb584ec3751fc350b2.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index242d64da7608c8eb584ec3751fc350b2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
index242d64da7608c8eb584ec3751fc350b2.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index242d64da7608c8eb584ec3751fc350b2.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
const index242d64da7608c8eb584ec3751fc350b2Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index242d64da7608c8eb584ec3751fc350b2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
index242d64da7608c8eb584ec3751fc350b2Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index242d64da7608c8eb584ec3751fc350b2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:16
* @route '/investor/deals'
*/
index242d64da7608c8eb584ec3751fc350b2Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index242d64da7608c8eb584ec3751fc350b2.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index242d64da7608c8eb584ec3751fc350b2.form = index242d64da7608c8eb584ec3751fc350b2Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorDealsController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/investor/deals': index0282307d754d45442065ef1c088d1c84,
    '/investor/deals': index242d64da7608c8eb584ec3751fc350b2,
}

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/api/v1/investor/deals/{campaign}'
*/
const show01e713cee3d6bc5b73e3588151b5355a = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show01e713cee3d6bc5b73e3588151b5355a.url(args, options),
    method: 'get',
})

show01e713cee3d6bc5b73e3588151b5355a.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals/{campaign}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/api/v1/investor/deals/{campaign}'
*/
show01e713cee3d6bc5b73e3588151b5355a.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show01e713cee3d6bc5b73e3588151b5355a.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/api/v1/investor/deals/{campaign}'
*/
show01e713cee3d6bc5b73e3588151b5355a.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show01e713cee3d6bc5b73e3588151b5355a.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/api/v1/investor/deals/{campaign}'
*/
show01e713cee3d6bc5b73e3588151b5355a.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show01e713cee3d6bc5b73e3588151b5355a.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/api/v1/investor/deals/{campaign}'
*/
const show01e713cee3d6bc5b73e3588151b5355aForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show01e713cee3d6bc5b73e3588151b5355a.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/api/v1/investor/deals/{campaign}'
*/
show01e713cee3d6bc5b73e3588151b5355aForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show01e713cee3d6bc5b73e3588151b5355a.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/api/v1/investor/deals/{campaign}'
*/
show01e713cee3d6bc5b73e3588151b5355aForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show01e713cee3d6bc5b73e3588151b5355a.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show01e713cee3d6bc5b73e3588151b5355a.form = show01e713cee3d6bc5b73e3588151b5355aForm
/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/investor/deals/{campaign}'
*/
const showc3def7767f05a5b3f7291057dd7d7cad = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showc3def7767f05a5b3f7291057dd7d7cad.url(args, options),
    method: 'get',
})

showc3def7767f05a5b3f7291057dd7d7cad.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/investor/deals/{campaign}'
*/
showc3def7767f05a5b3f7291057dd7d7cad.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return showc3def7767f05a5b3f7291057dd7d7cad.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/investor/deals/{campaign}'
*/
showc3def7767f05a5b3f7291057dd7d7cad.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showc3def7767f05a5b3f7291057dd7d7cad.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/investor/deals/{campaign}'
*/
showc3def7767f05a5b3f7291057dd7d7cad.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showc3def7767f05a5b3f7291057dd7d7cad.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/investor/deals/{campaign}'
*/
const showc3def7767f05a5b3f7291057dd7d7cadForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showc3def7767f05a5b3f7291057dd7d7cad.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/investor/deals/{campaign}'
*/
showc3def7767f05a5b3f7291057dd7d7cadForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showc3def7767f05a5b3f7291057dd7d7cad.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:25
* @route '/investor/deals/{campaign}'
*/
showc3def7767f05a5b3f7291057dd7d7cadForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showc3def7767f05a5b3f7291057dd7d7cad.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showc3def7767f05a5b3f7291057dd7d7cad.form = showc3def7767f05a5b3f7291057dd7d7cadForm

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorDealsController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/investor/deals/{campaign}': show01e713cee3d6bc5b73e3588151b5355a,
    '/investor/deals/{campaign}': showc3def7767f05a5b3f7291057dd7d7cad,
}

const InvestorDealsController = { index, show }

export default InvestorDealsController