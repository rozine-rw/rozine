import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
const index8aefc7f83f3ae7768ffe6b4861b916cb = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index8aefc7f83f3ae7768ffe6b4861b916cb.url(args, options),
    method: 'get',
})

index8aefc7f83f3ae7768ffe6b4861b916cb.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/notices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
index8aefc7f83f3ae7768ffe6b4861b916cb.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return index8aefc7f83f3ae7768ffe6b4861b916cb.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
index8aefc7f83f3ae7768ffe6b4861b916cb.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index8aefc7f83f3ae7768ffe6b4861b916cb.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
index8aefc7f83f3ae7768ffe6b4861b916cb.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index8aefc7f83f3ae7768ffe6b4861b916cb.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
const index8aefc7f83f3ae7768ffe6b4861b916cbForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8aefc7f83f3ae7768ffe6b4861b916cb.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
index8aefc7f83f3ae7768ffe6b4861b916cbForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8aefc7f83f3ae7768ffe6b4861b916cb.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/api/v1/business/{business}/notices'
*/
index8aefc7f83f3ae7768ffe6b4861b916cbForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8aefc7f83f3ae7768ffe6b4861b916cb.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index8aefc7f83f3ae7768ffe6b4861b916cb.form = index8aefc7f83f3ae7768ffe6b4861b916cbForm
/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/business/{business}/notices'
*/
const indexf2de82af3ee0c60585c1a0e3ed1ba128 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexf2de82af3ee0c60585c1a0e3ed1ba128.url(args, options),
    method: 'get',
})

indexf2de82af3ee0c60585c1a0e3ed1ba128.definition = {
    methods: ["get","head"],
    url: '/business/{business}/notices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/business/{business}/notices'
*/
indexf2de82af3ee0c60585c1a0e3ed1ba128.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return indexf2de82af3ee0c60585c1a0e3ed1ba128.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/business/{business}/notices'
*/
indexf2de82af3ee0c60585c1a0e3ed1ba128.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexf2de82af3ee0c60585c1a0e3ed1ba128.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/business/{business}/notices'
*/
indexf2de82af3ee0c60585c1a0e3ed1ba128.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexf2de82af3ee0c60585c1a0e3ed1ba128.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/business/{business}/notices'
*/
const indexf2de82af3ee0c60585c1a0e3ed1ba128Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexf2de82af3ee0c60585c1a0e3ed1ba128.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/business/{business}/notices'
*/
indexf2de82af3ee0c60585c1a0e3ed1ba128Form.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexf2de82af3ee0c60585c1a0e3ed1ba128.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::index
* @see app/Http/Controllers/BusinessNoticesController.php:18
* @route '/business/{business}/notices'
*/
indexf2de82af3ee0c60585c1a0e3ed1ba128Form.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexf2de82af3ee0c60585c1a0e3ed1ba128.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexf2de82af3ee0c60585c1a0e3ed1ba128.form = indexf2de82af3ee0c60585c1a0e3ed1ba128Form

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessNoticesController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/business/{business}/notices': index8aefc7f83f3ae7768ffe6b4861b916cb,
    '/business/{business}/notices': indexf2de82af3ee0c60585c1a0e3ed1ba128,
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
const readAll7348062d393f55b7c866cd95e7e652a4 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll7348062d393f55b7c866cd95e7e652a4.url(args, options),
    method: 'post',
})

readAll7348062d393f55b7c866cd95e7e652a4.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/notices/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
readAll7348062d393f55b7c866cd95e7e652a4.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return readAll7348062d393f55b7c866cd95e7e652a4.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
readAll7348062d393f55b7c866cd95e7e652a4.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll7348062d393f55b7c866cd95e7e652a4.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
const readAll7348062d393f55b7c866cd95e7e652a4Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll7348062d393f55b7c866cd95e7e652a4.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/api/v1/business/{business}/notices/read-all'
*/
readAll7348062d393f55b7c866cd95e7e652a4Form.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll7348062d393f55b7c866cd95e7e652a4.url(args, options),
    method: 'post',
})

readAll7348062d393f55b7c866cd95e7e652a4.form = readAll7348062d393f55b7c866cd95e7e652a4Form
/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/business/{business}/notices/read-all'
*/
const readAll714f52e278c7723c0da27d1fbb12d5d7 = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll714f52e278c7723c0da27d1fbb12d5d7.url(args, options),
    method: 'post',
})

readAll714f52e278c7723c0da27d1fbb12d5d7.definition = {
    methods: ["post"],
    url: '/business/{business}/notices/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/business/{business}/notices/read-all'
*/
readAll714f52e278c7723c0da27d1fbb12d5d7.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return readAll714f52e278c7723c0da27d1fbb12d5d7.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/business/{business}/notices/read-all'
*/
readAll714f52e278c7723c0da27d1fbb12d5d7.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll714f52e278c7723c0da27d1fbb12d5d7.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/business/{business}/notices/read-all'
*/
const readAll714f52e278c7723c0da27d1fbb12d5d7Form = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll714f52e278c7723c0da27d1fbb12d5d7.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::readAll
* @see app/Http/Controllers/BusinessNoticesController.php:28
* @route '/business/{business}/notices/read-all'
*/
readAll714f52e278c7723c0da27d1fbb12d5d7Form.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll714f52e278c7723c0da27d1fbb12d5d7.url(args, options),
    method: 'post',
})

readAll714f52e278c7723c0da27d1fbb12d5d7.form = readAll714f52e278c7723c0da27d1fbb12d5d7Form

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessNoticesController::readAll, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `readAll['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const readAll = {
    '/api/v1/business/{business}/notices/read-all': readAll7348062d393f55b7c866cd95e7e652a4,
    '/business/{business}/notices/read-all': readAll714f52e278c7723c0da27d1fbb12d5d7,
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
const read359825e41ed11b4d3a5482eedc2c227f = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read359825e41ed11b4d3a5482eedc2c227f.url(args, options),
    method: 'post',
})

read359825e41ed11b4d3a5482eedc2c227f.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/notices/{notice}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
read359825e41ed11b4d3a5482eedc2c227f.url = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            notice: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        notice: args.notice,
    }

    return read359825e41ed11b4d3a5482eedc2c227f.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{notice}', parsedArgs.notice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
read359825e41ed11b4d3a5482eedc2c227f.post = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read359825e41ed11b4d3a5482eedc2c227f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
const read359825e41ed11b4d3a5482eedc2c227fForm = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read359825e41ed11b4d3a5482eedc2c227f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/api/v1/business/{business}/notices/{notice}/read'
*/
read359825e41ed11b4d3a5482eedc2c227fForm.post = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read359825e41ed11b4d3a5482eedc2c227f.url(args, options),
    method: 'post',
})

read359825e41ed11b4d3a5482eedc2c227f.form = read359825e41ed11b4d3a5482eedc2c227fForm
/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/business/{business}/notices/{notice}/read'
*/
const read7d4e18b8d46f40d27247f944a347089b = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read7d4e18b8d46f40d27247f944a347089b.url(args, options),
    method: 'post',
})

read7d4e18b8d46f40d27247f944a347089b.definition = {
    methods: ["post"],
    url: '/business/{business}/notices/{notice}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/business/{business}/notices/{notice}/read'
*/
read7d4e18b8d46f40d27247f944a347089b.url = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            notice: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        notice: args.notice,
    }

    return read7d4e18b8d46f40d27247f944a347089b.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{notice}', parsedArgs.notice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/business/{business}/notices/{notice}/read'
*/
read7d4e18b8d46f40d27247f944a347089b.post = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read7d4e18b8d46f40d27247f944a347089b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/business/{business}/notices/{notice}/read'
*/
const read7d4e18b8d46f40d27247f944a347089bForm = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read7d4e18b8d46f40d27247f944a347089b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessNoticesController::read
* @see app/Http/Controllers/BusinessNoticesController.php:23
* @route '/business/{business}/notices/{notice}/read'
*/
read7d4e18b8d46f40d27247f944a347089bForm.post = (args: { business: string | number, notice: string | number } | [business: string | number, notice: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read7d4e18b8d46f40d27247f944a347089b.url(args, options),
    method: 'post',
})

read7d4e18b8d46f40d27247f944a347089b.form = read7d4e18b8d46f40d27247f944a347089bForm

/**
* Multiple routes resolve to \App\Http\Controllers\BusinessNoticesController::read, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `read['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const read = {
    '/api/v1/business/{business}/notices/{notice}/read': read359825e41ed11b4d3a5482eedc2c227f,
    '/business/{business}/notices/{notice}/read': read7d4e18b8d46f40d27247f944a347089b,
}

const BusinessNoticesController = { index, readAll, read }

export default BusinessNoticesController