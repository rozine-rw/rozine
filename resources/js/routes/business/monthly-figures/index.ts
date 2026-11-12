import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::show
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:35
* @route '/business/{business}/monthly-figures'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}/monthly-figures',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::show
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:35
* @route '/business/{business}/monthly-figures'
*/
show.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::show
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:35
* @route '/business/{business}/monthly-figures'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::show
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:35
* @route '/business/{business}/monthly-figures'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::show
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:35
* @route '/business/{business}/monthly-figures'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::show
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:35
* @route '/business/{business}/monthly-figures'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::show
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:35
* @route '/business/{business}/monthly-figures'
*/
showForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::store
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:48
* @route '/business/{business}/monthly-figures'
*/
export const store = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/business/{business}/monthly-figures',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::store
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:48
* @route '/business/{business}/monthly-figures'
*/
store.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::store
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:48
* @route '/business/{business}/monthly-figures'
*/
store.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::store
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:48
* @route '/business/{business}/monthly-figures'
*/
const storeForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessMonthlyFiguresController::store
* @see app/Http/Controllers/BusinessMonthlyFiguresController.php:48
* @route '/business/{business}/monthly-figures'
*/
storeForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const monthlyFigures = {
    show: Object.assign(show, show),
    store: Object.assign(store, store),
}

export default monthlyFigures