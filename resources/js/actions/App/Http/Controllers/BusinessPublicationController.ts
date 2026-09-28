import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
const show51b4a0b5c803031f6cbfb3e4ab8d02a9 = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, options),
    method: 'get',
})

show51b4a0b5c803031f6cbfb3e4ab8d02a9.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/applications/{application}/publish',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
show51b4a0b5c803031f6cbfb3e4ab8d02a9.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            application: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        application: args.application,
    }

    return show51b4a0b5c803031f6cbfb3e4ab8d02a9.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
show51b4a0b5c803031f6cbfb3e4ab8d02a9.get = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
show51b4a0b5c803031f6cbfb3e4ab8d02a9.head = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
const show51b4a0b5c803031f6cbfb3e4ab8d02a9Form = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
show51b4a0b5c803031f6cbfb3e4ab8d02a9Form.get = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
show51b4a0b5c803031f6cbfb3e4ab8d02a9Form.head = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show51b4a0b5c803031f6cbfb3e4ab8d02a9.form = show51b4a0b5c803031f6cbfb3e4ab8d02a9Form
/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/business/{business}/applications/{application}/publish'
*/
const show2eb50c88b01989597b0db869fe4537e6 = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show2eb50c88b01989597b0db869fe4537e6.url(args, options),
    method: 'get',
})

show2eb50c88b01989597b0db869fe4537e6.definition = {
    methods: ["get","head"],
    url: '/business/{business}/applications/{application}/publish',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/business/{business}/applications/{application}/publish'
*/
show2eb50c88b01989597b0db869fe4537e6.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            application: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        application: args.application,
    }

    return show2eb50c88b01989597b0db869fe4537e6.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/business/{business}/applications/{application}/publish'
*/
show2eb50c88b01989597b0db869fe4537e6.get = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show2eb50c88b01989597b0db869fe4537e6.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/business/{business}/applications/{application}/publish'
*/
show2eb50c88b01989597b0db869fe4537e6.head = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show2eb50c88b01989597b0db869fe4537e6.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/business/{business}/applications/{application}/publish'
*/
const show2eb50c88b01989597b0db869fe4537e6Form = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show2eb50c88b01989597b0db869fe4537e6.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/business/{business}/applications/{application}/publish'
*/
show2eb50c88b01989597b0db869fe4537e6Form.get = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show2eb50c88b01989597b0db869fe4537e6.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::show
* @see app/Http/Controllers/BusinessPublicationController.php:23
* @route '/business/{business}/applications/{application}/publish'
*/
show2eb50c88b01989597b0db869fe4537e6Form.head = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show2eb50c88b01989597b0db869fe4537e6.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show2eb50c88b01989597b0db869fe4537e6.form = show2eb50c88b01989597b0db869fe4537e6Form

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessPublicationController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/business/{business}/applications/{application}/publish': show51b4a0b5c803031f6cbfb3e4ab8d02a9,
    '/business/{business}/applications/{application}/publish': show2eb50c88b01989597b0db869fe4537e6,
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
const publish51b4a0b5c803031f6cbfb3e4ab8d02a9 = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: publish51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, options),
    method: 'post',
})

publish51b4a0b5c803031f6cbfb3e4ab8d02a9.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/applications/{application}/publish',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
publish51b4a0b5c803031f6cbfb3e4ab8d02a9.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            application: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        application: args.application,
    }

    return publish51b4a0b5c803031f6cbfb3e4ab8d02a9.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
publish51b4a0b5c803031f6cbfb3e4ab8d02a9.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: publish51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
const publish51b4a0b5c803031f6cbfb3e4ab8d02a9Form = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: publish51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/api/v1/business/{business}/applications/{application}/publish'
*/
publish51b4a0b5c803031f6cbfb3e4ab8d02a9Form.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: publish51b4a0b5c803031f6cbfb3e4ab8d02a9.url(args, options),
    method: 'post',
})

publish51b4a0b5c803031f6cbfb3e4ab8d02a9.form = publish51b4a0b5c803031f6cbfb3e4ab8d02a9Form
/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/business/{business}/applications/{application}/publish'
*/
const publish2eb50c88b01989597b0db869fe4537e6 = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: publish2eb50c88b01989597b0db869fe4537e6.url(args, options),
    method: 'post',
})

publish2eb50c88b01989597b0db869fe4537e6.definition = {
    methods: ["post"],
    url: '/business/{business}/applications/{application}/publish',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/business/{business}/applications/{application}/publish'
*/
publish2eb50c88b01989597b0db869fe4537e6.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            application: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        application: args.application,
    }

    return publish2eb50c88b01989597b0db869fe4537e6.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/business/{business}/applications/{application}/publish'
*/
publish2eb50c88b01989597b0db869fe4537e6.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: publish2eb50c88b01989597b0db869fe4537e6.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/business/{business}/applications/{application}/publish'
*/
const publish2eb50c88b01989597b0db869fe4537e6Form = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: publish2eb50c88b01989597b0db869fe4537e6.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::publish
* @see app/Http/Controllers/BusinessPublicationController.php:31
* @route '/business/{business}/applications/{application}/publish'
*/
publish2eb50c88b01989597b0db869fe4537e6Form.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: publish2eb50c88b01989597b0db869fe4537e6.url(args, options),
    method: 'post',
})

publish2eb50c88b01989597b0db869fe4537e6.form = publish2eb50c88b01989597b0db869fe4537e6Form

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessPublicationController::publish, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `publish['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const publish = {
    '/api/v1/business/{business}/applications/{application}/publish': publish51b4a0b5c803031f6cbfb3e4ab8d02a9,
    '/business/{business}/applications/{application}/publish': publish2eb50c88b01989597b0db869fe4537e6,
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/api/v1/business/{business}/campaigns/{campaign}'
*/
const campaign070f368f6b9f5ddf7455004ddb4a3190 = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: campaign070f368f6b9f5ddf7455004ddb4a3190.url(args, options),
    method: 'get',
})

campaign070f368f6b9f5ddf7455004ddb4a3190.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/campaigns/{campaign}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/api/v1/business/{business}/campaigns/{campaign}'
*/
campaign070f368f6b9f5ddf7455004ddb4a3190.url = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions) => {
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

    return campaign070f368f6b9f5ddf7455004ddb4a3190.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/api/v1/business/{business}/campaigns/{campaign}'
*/
campaign070f368f6b9f5ddf7455004ddb4a3190.get = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: campaign070f368f6b9f5ddf7455004ddb4a3190.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/api/v1/business/{business}/campaigns/{campaign}'
*/
campaign070f368f6b9f5ddf7455004ddb4a3190.head = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: campaign070f368f6b9f5ddf7455004ddb4a3190.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/api/v1/business/{business}/campaigns/{campaign}'
*/
const campaign070f368f6b9f5ddf7455004ddb4a3190Form = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: campaign070f368f6b9f5ddf7455004ddb4a3190.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/api/v1/business/{business}/campaigns/{campaign}'
*/
campaign070f368f6b9f5ddf7455004ddb4a3190Form.get = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: campaign070f368f6b9f5ddf7455004ddb4a3190.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/api/v1/business/{business}/campaigns/{campaign}'
*/
campaign070f368f6b9f5ddf7455004ddb4a3190Form.head = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: campaign070f368f6b9f5ddf7455004ddb4a3190.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

campaign070f368f6b9f5ddf7455004ddb4a3190.form = campaign070f368f6b9f5ddf7455004ddb4a3190Form
/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/business/{business}/campaigns/{campaign}'
*/
const campaign4a95ac8b31023e9da2a17d044e38c264 = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: campaign4a95ac8b31023e9da2a17d044e38c264.url(args, options),
    method: 'get',
})

campaign4a95ac8b31023e9da2a17d044e38c264.definition = {
    methods: ["get","head"],
    url: '/business/{business}/campaigns/{campaign}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/business/{business}/campaigns/{campaign}'
*/
campaign4a95ac8b31023e9da2a17d044e38c264.url = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions) => {
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

    return campaign4a95ac8b31023e9da2a17d044e38c264.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/business/{business}/campaigns/{campaign}'
*/
campaign4a95ac8b31023e9da2a17d044e38c264.get = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: campaign4a95ac8b31023e9da2a17d044e38c264.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/business/{business}/campaigns/{campaign}'
*/
campaign4a95ac8b31023e9da2a17d044e38c264.head = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: campaign4a95ac8b31023e9da2a17d044e38c264.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/business/{business}/campaigns/{campaign}'
*/
const campaign4a95ac8b31023e9da2a17d044e38c264Form = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: campaign4a95ac8b31023e9da2a17d044e38c264.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/business/{business}/campaigns/{campaign}'
*/
campaign4a95ac8b31023e9da2a17d044e38c264Form.get = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: campaign4a95ac8b31023e9da2a17d044e38c264.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::campaign
* @see app/Http/Controllers/BusinessPublicationController.php:40
* @route '/business/{business}/campaigns/{campaign}'
*/
campaign4a95ac8b31023e9da2a17d044e38c264Form.head = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: campaign4a95ac8b31023e9da2a17d044e38c264.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

campaign4a95ac8b31023e9da2a17d044e38c264.form = campaign4a95ac8b31023e9da2a17d044e38c264Form

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessPublicationController::campaign, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `campaign['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const campaign = {
    '/api/v1/business/{business}/campaigns/{campaign}': campaign070f368f6b9f5ddf7455004ddb4a3190,
    '/business/{business}/campaigns/{campaign}': campaign4a95ac8b31023e9da2a17d044e38c264,
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/api/v1/business/{business}/campaigns/{campaign}/cancel'
*/
const canceleeb65e4c829438f8f1e728694a4227aa = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: canceleeb65e4c829438f8f1e728694a4227aa.url(args, options),
    method: 'post',
})

canceleeb65e4c829438f8f1e728694a4227aa.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/campaigns/{campaign}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/api/v1/business/{business}/campaigns/{campaign}/cancel'
*/
canceleeb65e4c829438f8f1e728694a4227aa.url = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions) => {
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

    return canceleeb65e4c829438f8f1e728694a4227aa.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/api/v1/business/{business}/campaigns/{campaign}/cancel'
*/
canceleeb65e4c829438f8f1e728694a4227aa.post = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: canceleeb65e4c829438f8f1e728694a4227aa.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/api/v1/business/{business}/campaigns/{campaign}/cancel'
*/
const canceleeb65e4c829438f8f1e728694a4227aaForm = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: canceleeb65e4c829438f8f1e728694a4227aa.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/api/v1/business/{business}/campaigns/{campaign}/cancel'
*/
canceleeb65e4c829438f8f1e728694a4227aaForm.post = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: canceleeb65e4c829438f8f1e728694a4227aa.url(args, options),
    method: 'post',
})

canceleeb65e4c829438f8f1e728694a4227aa.form = canceleeb65e4c829438f8f1e728694a4227aaForm
/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/business/{business}/campaigns/{campaign}/cancel'
*/
const cancel423724725e86668093dd71fbc2f7778f = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel423724725e86668093dd71fbc2f7778f.url(args, options),
    method: 'post',
})

cancel423724725e86668093dd71fbc2f7778f.definition = {
    methods: ["post"],
    url: '/business/{business}/campaigns/{campaign}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/business/{business}/campaigns/{campaign}/cancel'
*/
cancel423724725e86668093dd71fbc2f7778f.url = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions) => {
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

    return cancel423724725e86668093dd71fbc2f7778f.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/business/{business}/campaigns/{campaign}/cancel'
*/
cancel423724725e86668093dd71fbc2f7778f.post = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel423724725e86668093dd71fbc2f7778f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/business/{business}/campaigns/{campaign}/cancel'
*/
const cancel423724725e86668093dd71fbc2f7778fForm = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel423724725e86668093dd71fbc2f7778f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessPublicationController::cancel
* @see app/Http/Controllers/BusinessPublicationController.php:48
* @route '/business/{business}/campaigns/{campaign}/cancel'
*/
cancel423724725e86668093dd71fbc2f7778fForm.post = (args: { business: string | number, campaign: string | number } | [business: string | number, campaign: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel423724725e86668093dd71fbc2f7778f.url(args, options),
    method: 'post',
})

cancel423724725e86668093dd71fbc2f7778f.form = cancel423724725e86668093dd71fbc2f7778fForm

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessPublicationController::cancel, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `cancel['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const cancel = {
    '/api/v1/business/{business}/campaigns/{campaign}/cancel': canceleeb65e4c829438f8f1e728694a4227aa,
    '/business/{business}/campaigns/{campaign}/cancel': cancel423724725e86668093dd71fbc2f7778f,
}

const BusinessPublicationController = { show, publish, campaign, cancel }

export default BusinessPublicationController