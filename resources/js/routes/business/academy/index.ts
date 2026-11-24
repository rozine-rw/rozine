import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\BusinessAcademyController::module
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
export const module = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: module.url(args, options),
    method: 'get',
})

module.definition = {
    methods: ["get","head"],
    url: '/business/{business}/academy/{module}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessAcademyController::module
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
module.url = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions) => {
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

    return module.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{module}', parsedArgs.module.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessAcademyController::module
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
module.get = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: module.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::module
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
module.head = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: module.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::module
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
const moduleForm = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: module.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::module
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
moduleForm.get = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: module.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::module
* @see app/Http/Controllers/BusinessAcademyController.php:43
* @route '/business/{business}/academy/{module}'
*/
moduleForm.head = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: module.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

module.form = moduleForm

/**
* @see \App\Http\Controllers\BusinessAcademyController::submit
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
export const submit = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/business/{business}/academy/{module}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BusinessAcademyController::submit
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
submit.url = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions) => {
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

    return submit.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{module}', parsedArgs.module.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessAcademyController::submit
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
submit.post = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::submit
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
const submitForm = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BusinessAcademyController::submit
* @see app/Http/Controllers/BusinessAcademyController.php:50
* @route '/business/{business}/academy/{module}'
*/
submitForm.post = (args: { business: string | number, module: string | number } | [business: string | number, module: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
})

submit.form = submitForm

const academy = {
    module: Object.assign(module, module),
    submit: Object.assign(submit, submit),
}

export default academy