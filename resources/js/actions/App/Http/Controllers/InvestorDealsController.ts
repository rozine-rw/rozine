import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
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
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
index0282307d754d45442065ef1c088d1c84.url = (options?: RouteQueryOptions) => {
    return index0282307d754d45442065ef1c088d1c84.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
index0282307d754d45442065ef1c088d1c84.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index0282307d754d45442065ef1c088d1c84.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
index0282307d754d45442065ef1c088d1c84.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index0282307d754d45442065ef1c088d1c84.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
const index0282307d754d45442065ef1c088d1c84Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index0282307d754d45442065ef1c088d1c84.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/api/v1/investor/deals'
*/
index0282307d754d45442065ef1c088d1c84Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index0282307d754d45442065ef1c088d1c84.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
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
* @see app/Http/Controllers/InvestorDealsController.php:18
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
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/investor/deals'
*/
index242d64da7608c8eb584ec3751fc350b2.url = (options?: RouteQueryOptions) => {
    return index242d64da7608c8eb584ec3751fc350b2.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/investor/deals'
*/
index242d64da7608c8eb584ec3751fc350b2.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index242d64da7608c8eb584ec3751fc350b2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/investor/deals'
*/
index242d64da7608c8eb584ec3751fc350b2.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index242d64da7608c8eb584ec3751fc350b2.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/investor/deals'
*/
const index242d64da7608c8eb584ec3751fc350b2Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index242d64da7608c8eb584ec3751fc350b2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
* @route '/investor/deals'
*/
index242d64da7608c8eb584ec3751fc350b2Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index242d64da7608c8eb584ec3751fc350b2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::index
* @see app/Http/Controllers/InvestorDealsController.php:18
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
* @see app/Http/Controllers/InvestorDealsController.php:27
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
* @see app/Http/Controllers/InvestorDealsController.php:27
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
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
show01e713cee3d6bc5b73e3588151b5355a.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show01e713cee3d6bc5b73e3588151b5355a.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
show01e713cee3d6bc5b73e3588151b5355a.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show01e713cee3d6bc5b73e3588151b5355a.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
const show01e713cee3d6bc5b73e3588151b5355aForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show01e713cee3d6bc5b73e3588151b5355a.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/api/v1/investor/deals/{campaign}'
*/
show01e713cee3d6bc5b73e3588151b5355aForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show01e713cee3d6bc5b73e3588151b5355a.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
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
* @see app/Http/Controllers/InvestorDealsController.php:27
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
* @see app/Http/Controllers/InvestorDealsController.php:27
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
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
showc3def7767f05a5b3f7291057dd7d7cad.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showc3def7767f05a5b3f7291057dd7d7cad.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
showc3def7767f05a5b3f7291057dd7d7cad.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showc3def7767f05a5b3f7291057dd7d7cad.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
const showc3def7767f05a5b3f7291057dd7d7cadForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showc3def7767f05a5b3f7291057dd7d7cad.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
showc3def7767f05a5b3f7291057dd7d7cadForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showc3def7767f05a5b3f7291057dd7d7cad.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
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

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
const checkoutfed618ca5d43a84f296cc5eab9b84569 = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkoutfed618ca5d43a84f296cc5eab9b84569.url(args, options),
    method: 'get',
})

checkoutfed618ca5d43a84f296cc5eab9b84569.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals/{campaign}/checkout',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkoutfed618ca5d43a84f296cc5eab9b84569.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return checkoutfed618ca5d43a84f296cc5eab9b84569.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkoutfed618ca5d43a84f296cc5eab9b84569.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkoutfed618ca5d43a84f296cc5eab9b84569.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkoutfed618ca5d43a84f296cc5eab9b84569.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: checkoutfed618ca5d43a84f296cc5eab9b84569.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
const checkoutfed618ca5d43a84f296cc5eab9b84569Form = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkoutfed618ca5d43a84f296cc5eab9b84569.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkoutfed618ca5d43a84f296cc5eab9b84569Form.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkoutfed618ca5d43a84f296cc5eab9b84569.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/api/v1/investor/deals/{campaign}/checkout'
*/
checkoutfed618ca5d43a84f296cc5eab9b84569Form.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkoutfed618ca5d43a84f296cc5eab9b84569.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

checkoutfed618ca5d43a84f296cc5eab9b84569.form = checkoutfed618ca5d43a84f296cc5eab9b84569Form
/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/investor/deals/{campaign}/checkout'
*/
const checkout26104bc63bcc436ba389bf0897c587f7 = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkout26104bc63bcc436ba389bf0897c587f7.url(args, options),
    method: 'get',
})

checkout26104bc63bcc436ba389bf0897c587f7.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}/checkout',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/investor/deals/{campaign}/checkout'
*/
checkout26104bc63bcc436ba389bf0897c587f7.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return checkout26104bc63bcc436ba389bf0897c587f7.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/investor/deals/{campaign}/checkout'
*/
checkout26104bc63bcc436ba389bf0897c587f7.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkout26104bc63bcc436ba389bf0897c587f7.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/investor/deals/{campaign}/checkout'
*/
checkout26104bc63bcc436ba389bf0897c587f7.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: checkout26104bc63bcc436ba389bf0897c587f7.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/investor/deals/{campaign}/checkout'
*/
const checkout26104bc63bcc436ba389bf0897c587f7Form = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkout26104bc63bcc436ba389bf0897c587f7.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/investor/deals/{campaign}/checkout'
*/
checkout26104bc63bcc436ba389bf0897c587f7Form.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkout26104bc63bcc436ba389bf0897c587f7.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::checkout
* @see app/Http/Controllers/InvestorDealsController.php:35
* @route '/investor/deals/{campaign}/checkout'
*/
checkout26104bc63bcc436ba389bf0897c587f7Form.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkout26104bc63bcc436ba389bf0897c587f7.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

checkout26104bc63bcc436ba389bf0897c587f7.form = checkout26104bc63bcc436ba389bf0897c587f7Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorDealsController::checkout, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `checkout['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const checkout = {
    '/api/v1/investor/deals/{campaign}/checkout': checkoutfed618ca5d43a84f296cc5eab9b84569,
    '/investor/deals/{campaign}/checkout': checkout26104bc63bcc436ba389bf0897c587f7,
}

const InvestorDealsController = { index, show, checkout }

export default InvestorDealsController