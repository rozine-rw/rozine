import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/business',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::index
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:34
* @route '/api/v1/business'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::operation
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:95
* @route '/api/v1/business/application-operations/{request_id}'
*/
export const operation = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

operation.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/application-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::operation
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:95
* @route '/api/v1/business/application-operations/{request_id}'
*/
operation.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { request_id: args }
    }

    if (Array.isArray(args)) {
        args = {
            request_id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        request_id: args.request_id,
    }

    return operation.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::operation
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:95
* @route '/api/v1/business/application-operations/{request_id}'
*/
operation.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::operation
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:95
* @route '/api/v1/business/application-operations/{request_id}'
*/
operation.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::operation
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:95
* @route '/api/v1/business/application-operations/{request_id}'
*/
const operationForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::operation
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:95
* @route '/api/v1/business/application-operations/{request_id}'
*/
operationForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\BusinessApplicationController::operation
* @see app/Http/Controllers/Api/V1/BusinessApplicationController.php:95
* @route '/api/v1/business/application-operations/{request_id}'
*/
operationForm.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation.form = operationForm

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

const BusinessApplicationController = { index, operation, create, show, save, evaluate, submit }

export default BusinessApplicationController