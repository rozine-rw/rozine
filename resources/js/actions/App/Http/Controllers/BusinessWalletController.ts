import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
const show0909235a1329923f70e5a3fe9b249f5e = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show0909235a1329923f70e5a3fe9b249f5e.url(args, options),
    method: 'get',
})

show0909235a1329923f70e5a3fe9b249f5e.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
show0909235a1329923f70e5a3fe9b249f5e.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return show0909235a1329923f70e5a3fe9b249f5e.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
show0909235a1329923f70e5a3fe9b249f5e.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show0909235a1329923f70e5a3fe9b249f5e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
show0909235a1329923f70e5a3fe9b249f5e.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show0909235a1329923f70e5a3fe9b249f5e.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
const show0909235a1329923f70e5a3fe9b249f5eForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show0909235a1329923f70e5a3fe9b249f5e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
show0909235a1329923f70e5a3fe9b249f5eForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show0909235a1329923f70e5a3fe9b249f5e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/api/v1/business/{business}/wallet'
*/
show0909235a1329923f70e5a3fe9b249f5eForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show0909235a1329923f70e5a3fe9b249f5e.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show0909235a1329923f70e5a3fe9b249f5e.form = show0909235a1329923f70e5a3fe9b249f5eForm
/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/business/{business}/wallet'
*/
const showdbcb8bf61f9724959f536e1be3f493ad = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showdbcb8bf61f9724959f536e1be3f493ad.url(args, options),
    method: 'get',
})

showdbcb8bf61f9724959f536e1be3f493ad.definition = {
    methods: ["get","head"],
    url: '/business/{business}/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/business/{business}/wallet'
*/
showdbcb8bf61f9724959f536e1be3f493ad.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return showdbcb8bf61f9724959f536e1be3f493ad.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/business/{business}/wallet'
*/
showdbcb8bf61f9724959f536e1be3f493ad.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showdbcb8bf61f9724959f536e1be3f493ad.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/business/{business}/wallet'
*/
showdbcb8bf61f9724959f536e1be3f493ad.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showdbcb8bf61f9724959f536e1be3f493ad.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/business/{business}/wallet'
*/
const showdbcb8bf61f9724959f536e1be3f493adForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showdbcb8bf61f9724959f536e1be3f493ad.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/business/{business}/wallet'
*/
showdbcb8bf61f9724959f536e1be3f493adForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showdbcb8bf61f9724959f536e1be3f493ad.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::show
* @see app/Http/Controllers/BusinessWalletController.php:30
* @route '/business/{business}/wallet'
*/
showdbcb8bf61f9724959f536e1be3f493adForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showdbcb8bf61f9724959f536e1be3f493ad.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showdbcb8bf61f9724959f536e1be3f493ad.form = showdbcb8bf61f9724959f536e1be3f493adForm

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessWalletController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/business/{business}/wallet': show0909235a1329923f70e5a3fe9b249f5e,
    '/business/{business}/wallet': showdbcb8bf61f9724959f536e1be3f493ad,
}

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
const statement6ead2fe4dd43f7916d908296fca98957 = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement6ead2fe4dd43f7916d908296fca98957.url(args, options),
    method: 'get',
})

statement6ead2fe4dd43f7916d908296fca98957.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/wallet/statement.{format}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statement6ead2fe4dd43f7916d908296fca98957.url = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            format: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        format: args.format,
    }

    return statement6ead2fe4dd43f7916d908296fca98957.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{format}', parsedArgs.format.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statement6ead2fe4dd43f7916d908296fca98957.get = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement6ead2fe4dd43f7916d908296fca98957.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statement6ead2fe4dd43f7916d908296fca98957.head = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statement6ead2fe4dd43f7916d908296fca98957.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
const statement6ead2fe4dd43f7916d908296fca98957Form = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement6ead2fe4dd43f7916d908296fca98957.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statement6ead2fe4dd43f7916d908296fca98957Form.get = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement6ead2fe4dd43f7916d908296fca98957.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/api/v1/business/{business}/wallet/statement.{format}'
*/
statement6ead2fe4dd43f7916d908296fca98957Form.head = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement6ead2fe4dd43f7916d908296fca98957.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

statement6ead2fe4dd43f7916d908296fca98957.form = statement6ead2fe4dd43f7916d908296fca98957Form
/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/business/{business}/wallet/statement.{format}'
*/
const statement3568a67ed2ef32ed0dd55abe0e69f03f = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement3568a67ed2ef32ed0dd55abe0e69f03f.url(args, options),
    method: 'get',
})

statement3568a67ed2ef32ed0dd55abe0e69f03f.definition = {
    methods: ["get","head"],
    url: '/business/{business}/wallet/statement.{format}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/business/{business}/wallet/statement.{format}'
*/
statement3568a67ed2ef32ed0dd55abe0e69f03f.url = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            format: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        format: args.format,
    }

    return statement3568a67ed2ef32ed0dd55abe0e69f03f.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{format}', parsedArgs.format.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/business/{business}/wallet/statement.{format}'
*/
statement3568a67ed2ef32ed0dd55abe0e69f03f.get = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statement3568a67ed2ef32ed0dd55abe0e69f03f.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/business/{business}/wallet/statement.{format}'
*/
statement3568a67ed2ef32ed0dd55abe0e69f03f.head = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statement3568a67ed2ef32ed0dd55abe0e69f03f.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/business/{business}/wallet/statement.{format}'
*/
const statement3568a67ed2ef32ed0dd55abe0e69f03fForm = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement3568a67ed2ef32ed0dd55abe0e69f03f.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/business/{business}/wallet/statement.{format}'
*/
statement3568a67ed2ef32ed0dd55abe0e69f03fForm.get = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement3568a67ed2ef32ed0dd55abe0e69f03f.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::statement
* @see app/Http/Controllers/BusinessWalletController.php:41
* @route '/business/{business}/wallet/statement.{format}'
*/
statement3568a67ed2ef32ed0dd55abe0e69f03fForm.head = (args: { business: string | number, format: string | number } | [business: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statement3568a67ed2ef32ed0dd55abe0e69f03f.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

statement3568a67ed2ef32ed0dd55abe0e69f03f.form = statement3568a67ed2ef32ed0dd55abe0e69f03fForm

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessWalletController::statement, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `statement['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const statement = {
    '/api/v1/business/{business}/wallet/statement.{format}': statement6ead2fe4dd43f7916d908296fca98957,
    '/business/{business}/wallet/statement.{format}': statement3568a67ed2ef32ed0dd55abe0e69f03f,
}

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
*/
const deposit67ba38e680ff8fff6341767beb1a7da7 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit67ba38e680ff8fff6341767beb1a7da7.url(args, options),
    method: 'post',
})

deposit67ba38e680ff8fff6341767beb1a7da7.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/wallet/deposits',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
*/
deposit67ba38e680ff8fff6341767beb1a7da7.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return deposit67ba38e680ff8fff6341767beb1a7da7.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
*/
deposit67ba38e680ff8fff6341767beb1a7da7.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit67ba38e680ff8fff6341767beb1a7da7.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
*/
const deposit67ba38e680ff8fff6341767beb1a7da7Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit67ba38e680ff8fff6341767beb1a7da7.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/api/v1/business/{business}/wallet/deposits'
*/
deposit67ba38e680ff8fff6341767beb1a7da7Form.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deposit67ba38e680ff8fff6341767beb1a7da7.url(args, options),
    method: 'post',
})

deposit67ba38e680ff8fff6341767beb1a7da7.form = deposit67ba38e680ff8fff6341767beb1a7da7Form
/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/business/{business}/wallet/deposits'
*/
const depositeef68cf850a668e1c48fe0b67b35da8f = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: depositeef68cf850a668e1c48fe0b67b35da8f.url(args, options),
    method: 'post',
})

depositeef68cf850a668e1c48fe0b67b35da8f.definition = {
    methods: ["post"],
    url: '/business/{business}/wallet/deposits',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/business/{business}/wallet/deposits'
*/
depositeef68cf850a668e1c48fe0b67b35da8f.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return depositeef68cf850a668e1c48fe0b67b35da8f.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/business/{business}/wallet/deposits'
*/
depositeef68cf850a668e1c48fe0b67b35da8f.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: depositeef68cf850a668e1c48fe0b67b35da8f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/business/{business}/wallet/deposits'
*/
const depositeef68cf850a668e1c48fe0b67b35da8fForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: depositeef68cf850a668e1c48fe0b67b35da8f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::deposit
* @see app/Http/Controllers/BusinessWalletController.php:58
* @route '/business/{business}/wallet/deposits'
*/
depositeef68cf850a668e1c48fe0b67b35da8fForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: depositeef68cf850a668e1c48fe0b67b35da8f.url(args, options),
    method: 'post',
})

depositeef68cf850a668e1c48fe0b67b35da8f.form = depositeef68cf850a668e1c48fe0b67b35da8fForm

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessWalletController::deposit, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `deposit['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const deposit = {
    '/api/v1/business/{business}/wallet/deposits': deposit67ba38e680ff8fff6341767beb1a7da7,
    '/business/{business}/wallet/deposits': depositeef68cf850a668e1c48fe0b67b35da8f,
}

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
const withdraw668947fa634d1862d7d8df89b21ca6c9 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw668947fa634d1862d7d8df89b21ca6c9.url(args, options),
    method: 'post',
})

withdraw668947fa634d1862d7d8df89b21ca6c9.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/wallet/withdrawals',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
withdraw668947fa634d1862d7d8df89b21ca6c9.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return withdraw668947fa634d1862d7d8df89b21ca6c9.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
withdraw668947fa634d1862d7d8df89b21ca6c9.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw668947fa634d1862d7d8df89b21ca6c9.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
const withdraw668947fa634d1862d7d8df89b21ca6c9Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdraw668947fa634d1862d7d8df89b21ca6c9.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/api/v1/business/{business}/wallet/withdrawals'
*/
withdraw668947fa634d1862d7d8df89b21ca6c9Form.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdraw668947fa634d1862d7d8df89b21ca6c9.url(args, options),
    method: 'post',
})

withdraw668947fa634d1862d7d8df89b21ca6c9.form = withdraw668947fa634d1862d7d8df89b21ca6c9Form
/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/business/{business}/wallet/withdrawals'
*/
const withdrawaba0464d204cf0624504ca349087eaca = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdrawaba0464d204cf0624504ca349087eaca.url(args, options),
    method: 'post',
})

withdrawaba0464d204cf0624504ca349087eaca.definition = {
    methods: ["post"],
    url: '/business/{business}/wallet/withdrawals',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/business/{business}/wallet/withdrawals'
*/
withdrawaba0464d204cf0624504ca349087eaca.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { business: args }
    }

    if (Array.isArray(args)) {
        args = {
            business: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
    }

    return withdrawaba0464d204cf0624504ca349087eaca.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/business/{business}/wallet/withdrawals'
*/
withdrawaba0464d204cf0624504ca349087eaca.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdrawaba0464d204cf0624504ca349087eaca.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/business/{business}/wallet/withdrawals'
*/
const withdrawaba0464d204cf0624504ca349087eacaForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdrawaba0464d204cf0624504ca349087eaca.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::withdraw
* @see app/Http/Controllers/BusinessWalletController.php:68
* @route '/business/{business}/wallet/withdrawals'
*/
withdrawaba0464d204cf0624504ca349087eacaForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdrawaba0464d204cf0624504ca349087eaca.url(args, options),
    method: 'post',
})

withdrawaba0464d204cf0624504ca349087eaca.form = withdrawaba0464d204cf0624504ca349087eacaForm

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessWalletController::withdraw, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `withdraw['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const withdraw = {
    '/api/v1/business/{business}/wallet/withdrawals': withdraw668947fa634d1862d7d8df89b21ca6c9,
    '/business/{business}/wallet/withdrawals': withdrawaba0464d204cf0624504ca349087eaca,
}

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
const operationb818dd4d8e21a3ea193e0216d3e45e17 = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationb818dd4d8e21a3ea193e0216d3e45e17.url(args, options),
    method: 'get',
})

operationb818dd4d8e21a3ea193e0216d3e45e17.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/wallet-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
operationb818dd4d8e21a3ea193e0216d3e45e17.url = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions) => {
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

    return operationb818dd4d8e21a3ea193e0216d3e45e17.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
operationb818dd4d8e21a3ea193e0216d3e45e17.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationb818dd4d8e21a3ea193e0216d3e45e17.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
operationb818dd4d8e21a3ea193e0216d3e45e17.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operationb818dd4d8e21a3ea193e0216d3e45e17.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
const operationb818dd4d8e21a3ea193e0216d3e45e17Form = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationb818dd4d8e21a3ea193e0216d3e45e17.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
operationb818dd4d8e21a3ea193e0216d3e45e17Form.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationb818dd4d8e21a3ea193e0216d3e45e17.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/api/v1/business/{business}/wallet-operations/{request_id}'
*/
operationb818dd4d8e21a3ea193e0216d3e45e17Form.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationb818dd4d8e21a3ea193e0216d3e45e17.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operationb818dd4d8e21a3ea193e0216d3e45e17.form = operationb818dd4d8e21a3ea193e0216d3e45e17Form
/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/business/{business}/wallet-operations/{request_id}'
*/
const operation95e82b26681f7a5c5e3b7dff41ac86fa = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation95e82b26681f7a5c5e3b7dff41ac86fa.url(args, options),
    method: 'get',
})

operation95e82b26681f7a5c5e3b7dff41ac86fa.definition = {
    methods: ["get","head"],
    url: '/business/{business}/wallet-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/business/{business}/wallet-operations/{request_id}'
*/
operation95e82b26681f7a5c5e3b7dff41ac86fa.url = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions) => {
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

    return operation95e82b26681f7a5c5e3b7dff41ac86fa.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/business/{business}/wallet-operations/{request_id}'
*/
operation95e82b26681f7a5c5e3b7dff41ac86fa.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation95e82b26681f7a5c5e3b7dff41ac86fa.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/business/{business}/wallet-operations/{request_id}'
*/
operation95e82b26681f7a5c5e3b7dff41ac86fa.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation95e82b26681f7a5c5e3b7dff41ac86fa.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/business/{business}/wallet-operations/{request_id}'
*/
const operation95e82b26681f7a5c5e3b7dff41ac86faForm = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation95e82b26681f7a5c5e3b7dff41ac86fa.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/business/{business}/wallet-operations/{request_id}'
*/
operation95e82b26681f7a5c5e3b7dff41ac86faForm.get = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation95e82b26681f7a5c5e3b7dff41ac86fa.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessWalletController::operation
* @see app/Http/Controllers/BusinessWalletController.php:78
* @route '/business/{business}/wallet-operations/{request_id}'
*/
operation95e82b26681f7a5c5e3b7dff41ac86faForm.head = (args: { business: string | number, request_id: string | number } | [business: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation95e82b26681f7a5c5e3b7dff41ac86fa.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation95e82b26681f7a5c5e3b7dff41ac86fa.form = operation95e82b26681f7a5c5e3b7dff41ac86faForm

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessWalletController::operation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `operation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const operation = {
    '/api/v1/business/{business}/wallet-operations/{request_id}': operationb818dd4d8e21a3ea193e0216d3e45e17,
    '/business/{business}/wallet-operations/{request_id}': operation95e82b26681f7a5c5e3b7dff41ac86fa,
}

const BusinessWalletController = { show, statement, deposit, withdraw, operation }

export default BusinessWalletController