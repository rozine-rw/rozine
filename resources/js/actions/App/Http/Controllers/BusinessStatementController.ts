import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
export const index = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/statements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
index.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return index.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
index.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
index.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
const indexForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
indexForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::index
* @see app/Http/Controllers/BusinessStatementController.php:36
* @route '/api/v1/business/{business}/statements'
*/
indexForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
const upload4b00fd639f13515ab8fabeadb2f06861 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload4b00fd639f13515ab8fabeadb2f06861.url(args, options),
    method: 'post',
})

upload4b00fd639f13515ab8fabeadb2f06861.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/statements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
upload4b00fd639f13515ab8fabeadb2f06861.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return upload4b00fd639f13515ab8fabeadb2f06861.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
upload4b00fd639f13515ab8fabeadb2f06861.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload4b00fd639f13515ab8fabeadb2f06861.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
const upload4b00fd639f13515ab8fabeadb2f06861Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload4b00fd639f13515ab8fabeadb2f06861.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/api/v1/business/{business}/statements'
*/
upload4b00fd639f13515ab8fabeadb2f06861Form.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload4b00fd639f13515ab8fabeadb2f06861.url(args, options),
    method: 'post',
})

upload4b00fd639f13515ab8fabeadb2f06861.form = upload4b00fd639f13515ab8fabeadb2f06861Form
/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/business/{business}/statements'
*/
const uploadfcb7813d9c8474619b5353d9e78c0593 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadfcb7813d9c8474619b5353d9e78c0593.url(args, options),
    method: 'post',
})

uploadfcb7813d9c8474619b5353d9e78c0593.definition = {
    methods: ["post"],
    url: '/business/{business}/statements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/business/{business}/statements'
*/
uploadfcb7813d9c8474619b5353d9e78c0593.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return uploadfcb7813d9c8474619b5353d9e78c0593.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/business/{business}/statements'
*/
uploadfcb7813d9c8474619b5353d9e78c0593.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadfcb7813d9c8474619b5353d9e78c0593.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/business/{business}/statements'
*/
const uploadfcb7813d9c8474619b5353d9e78c0593Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadfcb7813d9c8474619b5353d9e78c0593.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::upload
* @see app/Http/Controllers/BusinessStatementController.php:47
* @route '/business/{business}/statements'
*/
uploadfcb7813d9c8474619b5353d9e78c0593Form.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadfcb7813d9c8474619b5353d9e78c0593.url(args, options),
    method: 'post',
})

uploadfcb7813d9c8474619b5353d9e78c0593.form = uploadfcb7813d9c8474619b5353d9e78c0593Form

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessStatementController::upload, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `upload['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const upload = {
    '/api/v1/business/{business}/statements': upload4b00fd639f13515ab8fabeadb2f06861,
    '/business/{business}/statements': uploadfcb7813d9c8474619b5353d9e78c0593,
}

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
const show6c274aa71c74367bc259c610668b9f5f = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show6c274aa71c74367bc259c610668b9f5f.url(args, options),
    method: 'get',
})

show6c274aa71c74367bc259c610668b9f5f.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/statements/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
show6c274aa71c74367bc259c610668b9f5f.url = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        document: args.document,
    }

    return show6c274aa71c74367bc259c610668b9f5f.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
show6c274aa71c74367bc259c610668b9f5f.get = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show6c274aa71c74367bc259c610668b9f5f.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
show6c274aa71c74367bc259c610668b9f5f.head = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show6c274aa71c74367bc259c610668b9f5f.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
const show6c274aa71c74367bc259c610668b9f5fForm = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show6c274aa71c74367bc259c610668b9f5f.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
show6c274aa71c74367bc259c610668b9f5fForm.get = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show6c274aa71c74367bc259c610668b9f5f.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/api/v1/business/{business}/statements/{document}'
*/
show6c274aa71c74367bc259c610668b9f5fForm.head = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show6c274aa71c74367bc259c610668b9f5f.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show6c274aa71c74367bc259c610668b9f5f.form = show6c274aa71c74367bc259c610668b9f5fForm
/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/business/{business}/statements/{document}'
*/
const showca1cd626a0286b1e6ba18630cd315c26 = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showca1cd626a0286b1e6ba18630cd315c26.url(args, options),
    method: 'get',
})

showca1cd626a0286b1e6ba18630cd315c26.definition = {
    methods: ["get","head"],
    url: '/business/{business}/statements/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/business/{business}/statements/{document}'
*/
showca1cd626a0286b1e6ba18630cd315c26.url = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        document: args.document,
    }

    return showca1cd626a0286b1e6ba18630cd315c26.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/business/{business}/statements/{document}'
*/
showca1cd626a0286b1e6ba18630cd315c26.get = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showca1cd626a0286b1e6ba18630cd315c26.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/business/{business}/statements/{document}'
*/
showca1cd626a0286b1e6ba18630cd315c26.head = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showca1cd626a0286b1e6ba18630cd315c26.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/business/{business}/statements/{document}'
*/
const showca1cd626a0286b1e6ba18630cd315c26Form = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showca1cd626a0286b1e6ba18630cd315c26.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/business/{business}/statements/{document}'
*/
showca1cd626a0286b1e6ba18630cd315c26Form.get = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showca1cd626a0286b1e6ba18630cd315c26.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessStatementController::show
* @see app/Http/Controllers/BusinessStatementController.php:72
* @route '/business/{business}/statements/{document}'
*/
showca1cd626a0286b1e6ba18630cd315c26Form.head = (args: { business: string | number, document: string | number } | [business: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showca1cd626a0286b1e6ba18630cd315c26.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showca1cd626a0286b1e6ba18630cd315c26.form = showca1cd626a0286b1e6ba18630cd315c26Form

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessStatementController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/business/{business}/statements/{document}': show6c274aa71c74367bc259c610668b9f5f,
    '/business/{business}/statements/{document}': showca1cd626a0286b1e6ba18630cd315c26,
}

const BusinessStatementController = { index, upload, show }

export default BusinessStatementController