import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\RoleApplicationController::show
* @see app/Http/Controllers/RoleApplicationController.php:42
* @route '/role'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/role',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleApplicationController::show
* @see app/Http/Controllers/RoleApplicationController.php:42
* @route '/role'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleApplicationController::show
* @see app/Http/Controllers/RoleApplicationController.php:42
* @route '/role'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::show
* @see app/Http/Controllers/RoleApplicationController.php:42
* @route '/role'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::show
* @see app/Http/Controllers/RoleApplicationController.php:42
* @route '/role'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::show
* @see app/Http/Controllers/RoleApplicationController.php:42
* @route '/role'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::show
* @see app/Http/Controllers/RoleApplicationController.php:42
* @route '/role'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\RoleApplicationController::save
* @see app/Http/Controllers/RoleApplicationController.php:91
* @route '/role/steps'
*/
export const save = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/role/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RoleApplicationController::save
* @see app/Http/Controllers/RoleApplicationController.php:91
* @route '/role/steps'
*/
save.url = (options?: RouteQueryOptions) => {
    return save.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleApplicationController::save
* @see app/Http/Controllers/RoleApplicationController.php:91
* @route '/role/steps'
*/
save.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::save
* @see app/Http/Controllers/RoleApplicationController.php:91
* @route '/role/steps'
*/
const saveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::save
* @see app/Http/Controllers/RoleApplicationController.php:91
* @route '/role/steps'
*/
saveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(options),
    method: 'post',
})

save.form = saveForm

/**
* @see \App\Http\Controllers\RoleApplicationController::upload
* @see app/Http/Controllers/RoleApplicationController.php:101
* @route '/role/documents'
*/
export const upload = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(options),
    method: 'post',
})

upload.definition = {
    methods: ["post"],
    url: '/role/documents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RoleApplicationController::upload
* @see app/Http/Controllers/RoleApplicationController.php:101
* @route '/role/documents'
*/
upload.url = (options?: RouteQueryOptions) => {
    return upload.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleApplicationController::upload
* @see app/Http/Controllers/RoleApplicationController.php:101
* @route '/role/documents'
*/
upload.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::upload
* @see app/Http/Controllers/RoleApplicationController.php:101
* @route '/role/documents'
*/
const uploadForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::upload
* @see app/Http/Controllers/RoleApplicationController.php:101
* @route '/role/documents'
*/
uploadForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(options),
    method: 'post',
})

upload.form = uploadForm

/**
* @see \App\Http\Controllers\RoleApplicationController::document
* @see app/Http/Controllers/RoleApplicationController.php:77
* @route '/role/documents/{slot}'
*/
export const document = (args: { slot: string | number } | [slot: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document.url(args, options),
    method: 'get',
})

document.definition = {
    methods: ["get","head"],
    url: '/role/documents/{slot}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleApplicationController::document
* @see app/Http/Controllers/RoleApplicationController.php:77
* @route '/role/documents/{slot}'
*/
document.url = (args: { slot: string | number } | [slot: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { slot: args }
    }

    if (Array.isArray(args)) {
        args = {
            slot: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        slot: args.slot,
    }

    return document.definition.url
            .replace('{slot}', parsedArgs.slot.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleApplicationController::document
* @see app/Http/Controllers/RoleApplicationController.php:77
* @route '/role/documents/{slot}'
*/
document.get = (args: { slot: string | number } | [slot: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::document
* @see app/Http/Controllers/RoleApplicationController.php:77
* @route '/role/documents/{slot}'
*/
document.head = (args: { slot: string | number } | [slot: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: document.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::document
* @see app/Http/Controllers/RoleApplicationController.php:77
* @route '/role/documents/{slot}'
*/
const documentForm = (args: { slot: string | number } | [slot: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::document
* @see app/Http/Controllers/RoleApplicationController.php:77
* @route '/role/documents/{slot}'
*/
documentForm.get = (args: { slot: string | number } | [slot: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::document
* @see app/Http/Controllers/RoleApplicationController.php:77
* @route '/role/documents/{slot}'
*/
documentForm.head = (args: { slot: string | number } | [slot: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

document.form = documentForm

/**
* @see \App\Http\Controllers\RoleApplicationController::submit
* @see app/Http/Controllers/RoleApplicationController.php:112
* @route '/role/submit'
*/
export const submit = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/role/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RoleApplicationController::submit
* @see app/Http/Controllers/RoleApplicationController.php:112
* @route '/role/submit'
*/
submit.url = (options?: RouteQueryOptions) => {
    return submit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleApplicationController::submit
* @see app/Http/Controllers/RoleApplicationController.php:112
* @route '/role/submit'
*/
submit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::submit
* @see app/Http/Controllers/RoleApplicationController.php:112
* @route '/role/submit'
*/
const submitForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RoleApplicationController::submit
* @see app/Http/Controllers/RoleApplicationController.php:112
* @route '/role/submit'
*/
submitForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

submit.form = submitForm

const RoleApplicationController = { show, save, upload, document, submit }

export default RoleApplicationController