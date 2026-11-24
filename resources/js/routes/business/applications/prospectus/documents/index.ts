import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessProspectusController::store
* @see app/Http/Controllers/BusinessProspectusController.php:63
* @route '/business/{business}/applications/{application}/prospectus/documents'
*/
export const store = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/business/{business}/applications/{application}/prospectus/documents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessProspectusController::store
* @see app/Http/Controllers/BusinessProspectusController.php:63
* @route '/business/{business}/applications/{application}/prospectus/documents'
*/
store.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessProspectusController::store
* @see app/Http/Controllers/BusinessProspectusController.php:63
* @route '/business/{business}/applications/{application}/prospectus/documents'
*/
store.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::store
* @see app/Http/Controllers/BusinessProspectusController.php:63
* @route '/business/{business}/applications/{application}/prospectus/documents'
*/
const storeForm = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessProspectusController::store
* @see app/Http/Controllers/BusinessProspectusController.php:63
* @route '/business/{business}/applications/{application}/prospectus/documents'
*/
storeForm.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const documents = {
    store: Object.assign(store, store),
}

export default documents