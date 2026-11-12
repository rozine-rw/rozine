import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessAcademyController::index
* @see app/Http/Controllers/BusinessAcademyController.php:36
* @route '/business/{business}/academy'
*/
export const index = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/business/{business}/academy',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessAcademyController::index
* @see app/Http/Controllers/BusinessAcademyController.php:36
* @route '/business/{business}/academy'
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
* @see \App\Http\Controllers\BusinessAcademyController::index
* @see app/Http/Controllers/BusinessAcademyController.php:36
* @route '/business/{business}/academy'
*/
index.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::index
* @see app/Http/Controllers/BusinessAcademyController.php:36
* @route '/business/{business}/academy'
*/
index.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::index
* @see app/Http/Controllers/BusinessAcademyController.php:36
* @route '/business/{business}/academy'
*/
const indexForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::index
* @see app/Http/Controllers/BusinessAcademyController.php:36
* @route '/business/{business}/academy'
*/
indexForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::index
* @see app/Http/Controllers/BusinessAcademyController.php:36
* @route '/business/{business}/academy'
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
* @see \App\Http\Controllers\BusinessAcademyController::show
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
export const show = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}/academy/{module}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessAcademyController::show
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
show.url = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            module: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        module: args.module,
    }

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{module}', parsedArgs.module.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessAcademyController::show
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
show.get = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::show
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
show.head = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::show
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
const showForm = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::show
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
showForm.get = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::show
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
showForm.head = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\BusinessAcademyController::store
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
export const store = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/business/{business}/academy/{module}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessAcademyController::store
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
store.url = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            module: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        module: args.module,
    }

    return store.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{module}', parsedArgs.module.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessAcademyController::store
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
store.post = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::store
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
const storeForm = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::store
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
storeForm.post = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const BusinessAcademyController = { index, show, store }

export default BusinessAcademyController