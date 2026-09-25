import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::create
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:53
* @route '/api/v1/business/{business}/applications'
*/
export const create = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: create.url(args, options),
    method: 'post',
})

create.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/applications',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::create
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:53
* @route '/api/v1/business/{business}/applications'
*/
create.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return create.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::create
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:53
* @route '/api/v1/business/{business}/applications'
*/
create.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: create.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::create
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:53
* @route '/api/v1/business/{business}/applications'
*/
const createForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: create.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::create
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:53
* @route '/api/v1/business/{business}/applications'
*/
createForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: create.url(args, options),
    method: 'post',
})

create.form = createForm

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::show
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:44
* @route '/api/v1/business/{business}/applications/{application}'
*/
export const show = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/applications/{application}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::show
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:44
* @route '/api/v1/business/{business}/applications/{application}'
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
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::show
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:44
* @route '/api/v1/business/{business}/applications/{application}'
*/
show.get = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::show
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:44
* @route '/api/v1/business/{business}/applications/{application}'
*/
show.head = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::show
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:44
* @route '/api/v1/business/{business}/applications/{application}'
*/
const showForm = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::show
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:44
* @route '/api/v1/business/{business}/applications/{application}'
*/
showForm.get = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::show
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:44
* @route '/api/v1/business/{business}/applications/{application}'
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
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::save
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:61
* @route '/api/v1/business/{business}/applications/{application}/save'
*/
export const save = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/applications/{application}/save',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::save
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:61
* @route '/api/v1/business/{business}/applications/{application}/save'
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
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::save
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:61
* @route '/api/v1/business/{business}/applications/{application}/save'
*/
save.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::save
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:61
* @route '/api/v1/business/{business}/applications/{application}/save'
*/
const saveForm = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::save
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:61
* @route '/api/v1/business/{business}/applications/{application}/save'
*/
saveForm.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(args, options),
    method: 'post',
})

save.form = saveForm

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::evaluate
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:73
* @route '/api/v1/business/{business}/applications/{application}/evaluate'
*/
export const evaluate = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: evaluate.url(args, options),
    method: 'post',
})

evaluate.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/applications/{application}/evaluate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::evaluate
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:73
* @route '/api/v1/business/{business}/applications/{application}/evaluate'
*/
evaluate.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
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

    return evaluate.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::evaluate
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:73
* @route '/api/v1/business/{business}/applications/{application}/evaluate'
*/
evaluate.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: evaluate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::evaluate
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:73
* @route '/api/v1/business/{business}/applications/{application}/evaluate'
*/
const evaluateForm = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: evaluate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::evaluate
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:73
* @route '/api/v1/business/{business}/applications/{application}/evaluate'
*/
evaluateForm.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: evaluate.url(args, options),
    method: 'post',
})

evaluate.form = evaluateForm

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::submit
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:84
* @route '/api/v1/business/{business}/applications/{application}/submit'
*/
export const submit = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/applications/{application}/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::submit
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:84
* @route '/api/v1/business/{business}/applications/{application}/submit'
*/
submit.url = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions) => {
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

    return submit.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::submit
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:84
* @route '/api/v1/business/{business}/applications/{application}/submit'
*/
submit.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::submit
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:84
* @route '/api/v1/business/{business}/applications/{application}/submit'
*/
const submitForm = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::submit
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:84
* @route '/api/v1/business/{business}/applications/{application}/submit'
*/
submitForm.post = (args: { business: string | number, application: string | number } | [business: string | number, application: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
})

submit.form = submitForm

const applications = {
    operations: Object.assign(operations, operations),
    create: Object.assign(create, create),
    show: Object.assign(show, show),
    save: Object.assign(save, save),
    evaluate: Object.assign(evaluate, evaluate),
    submit: Object.assign(submit, submit),
}

export default applications