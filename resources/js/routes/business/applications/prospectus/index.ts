import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessProspectusController::show
* @see app/Http/Controllers/BusinessProspectusController.php:42
* @route '/business/{business}/applications/{application}/prospectus'
*/
export const show = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}/applications/{application}/prospectus',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessProspectusController::show
* @see app/Http/Controllers/BusinessProspectusController.php:42
* @route '/business/{business}/applications/{application}/prospectus'
*/
show.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessProspectusController::show
* @see app/Http/Controllers/BusinessProspectusController.php:42
* @route '/business/{business}/applications/{application}/prospectus'
*/
show.get = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::show
* @see app/Http/Controllers/BusinessProspectusController.php:42
* @route '/business/{business}/applications/{application}/prospectus'
*/
show.head = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::show
* @see app/Http/Controllers/BusinessProspectusController.php:42
* @route '/business/{business}/applications/{application}/prospectus'
*/
const showForm = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::show
* @see app/Http/Controllers/BusinessProspectusController.php:42
* @route '/business/{business}/applications/{application}/prospectus'
*/
showForm.get = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::show
* @see app/Http/Controllers/BusinessProspectusController.php:42
* @route '/business/{business}/applications/{application}/prospectus'
*/
showForm.head = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\BusinessProspectusController::save
* @see app/Http/Controllers/BusinessProspectusController.php:51
* @route '/business/{business}/applications/{application}/prospectus'
*/
export const save = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: save.url(args, options),
    method: 'put',
})

save.definition = {
    methods: ["put"],
    url: '/business/{business}/applications/{application}/prospectus',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\BusinessProspectusController::save
* @see app/Http/Controllers/BusinessProspectusController.php:51
* @route '/business/{business}/applications/{application}/prospectus'
*/
save.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
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

    return save.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessProspectusController::save
* @see app/Http/Controllers/BusinessProspectusController.php:51
* @route '/business/{business}/applications/{application}/prospectus'
*/
save.put = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: save.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::save
* @see app/Http/Controllers/BusinessProspectusController.php:51
* @route '/business/{business}/applications/{application}/prospectus'
*/
const saveForm = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::save
* @see app/Http/Controllers/BusinessProspectusController.php:51
* @route '/business/{business}/applications/{application}/prospectus'
*/
saveForm.put = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

save.form = saveForm

/**
* @see \App\Http\Controllers\BusinessProspectusController::sign
* @see app/Http/Controllers/BusinessProspectusController.php:60
* @route '/business/{business}/applications/{application}/prospectus/sign'
*/
export const sign = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sign.url(args, options),
    method: 'post',
})

sign.definition = {
    methods: ["post"],
    url: '/business/{business}/applications/{application}/prospectus/sign',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessProspectusController::sign
* @see app/Http/Controllers/BusinessProspectusController.php:60
* @route '/business/{business}/applications/{application}/prospectus/sign'
*/
sign.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
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

    return sign.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessProspectusController::sign
* @see app/Http/Controllers/BusinessProspectusController.php:60
* @route '/business/{business}/applications/{application}/prospectus/sign'
*/
sign.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::sign
* @see app/Http/Controllers/BusinessProspectusController.php:60
* @route '/business/{business}/applications/{application}/prospectus/sign'
*/
const signForm = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: sign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::sign
* @see app/Http/Controllers/BusinessProspectusController.php:60
* @route '/business/{business}/applications/{application}/prospectus/sign'
*/
signForm.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: sign.url(args, options),
    method: 'post',
})

sign.form = signForm

const prospectus = {
    show: Object.assign(show, show),
    save: Object.assign(save, save),
    sign: Object.assign(sign, sign),
}

export default prospectus