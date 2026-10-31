import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:78
* @route '/api/v1/investor/verification/steps'
*/
export const save = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/api/v1/investor/verification/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:78
* @route '/api/v1/investor/verification/steps'
*/
save.url = (options?: RouteQueryOptions) => {
    return save.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:78
* @route '/api/v1/investor/verification/steps'
*/
save.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:78
* @route '/api/v1/investor/verification/steps'
*/
const saveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:78
* @route '/api/v1/investor/verification/steps'
*/
saveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(options),
    method: 'post',
})

save.form = saveForm

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:85
* @route '/api/v1/investor/verification/documents'
*/
export const upload = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(options),
    method: 'post',
})

upload.definition = {
    methods: ["post"],
    url: '/api/v1/investor/verification/documents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:85
* @route '/api/v1/investor/verification/documents'
*/
upload.url = (options?: RouteQueryOptions) => {
    return upload.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:85
* @route '/api/v1/investor/verification/documents'
*/
upload.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:85
* @route '/api/v1/investor/verification/documents'
*/
const uploadForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:85
* @route '/api/v1/investor/verification/documents'
*/
uploadForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(options),
    method: 'post',
})

upload.form = uploadForm

/**
* @see \App\Http\Controllers\InvestorVerificationController::document
* @see app/Http/Controllers/InvestorVerificationController.php:63
* @route '/api/v1/investor/verification/documents/{side}'
*/
export const document = (args: { side: string | number } | [side: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document.url(args, options),
    method: 'get',
})

document.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/verification/documents/{side}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::document
* @see app/Http/Controllers/InvestorVerificationController.php:63
* @route '/api/v1/investor/verification/documents/{side}'
*/
document.url = (args: { side: string | number } | [side: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { side: args }
    }

    if (Array.isArray(args)) {
        args = {
            side: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        side: args.side,
    }

    return document.definition.url
            .replace('{side}', parsedArgs.side.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::document
* @see app/Http/Controllers/InvestorVerificationController.php:63
* @route '/api/v1/investor/verification/documents/{side}'
*/
document.get = (args: { side: string | number } | [side: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::document
* @see app/Http/Controllers/InvestorVerificationController.php:63
* @route '/api/v1/investor/verification/documents/{side}'
*/
document.head = (args: { side: string | number } | [side: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: document.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::document
* @see app/Http/Controllers/InvestorVerificationController.php:63
* @route '/api/v1/investor/verification/documents/{side}'
*/
const documentForm = (args: { side: string | number } | [side: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::document
* @see app/Http/Controllers/InvestorVerificationController.php:63
* @route '/api/v1/investor/verification/documents/{side}'
*/
documentForm.get = (args: { side: string | number } | [side: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::document
* @see app/Http/Controllers/InvestorVerificationController.php:63
* @route '/api/v1/investor/verification/documents/{side}'
*/
documentForm.head = (args: { side: string | number } | [side: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:100
* @route '/api/v1/investor/verification/submit'
*/
export const submit = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/api/v1/investor/verification/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:100
* @route '/api/v1/investor/verification/submit'
*/
submit.url = (options?: RouteQueryOptions) => {
    return submit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:100
* @route '/api/v1/investor/verification/submit'
*/
submit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:100
* @route '/api/v1/investor/verification/submit'
*/
const submitForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:100
* @route '/api/v1/investor/verification/submit'
*/
submitForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

submit.form = submitForm

const verification = {
    save: Object.assign(save, save),
    upload: Object.assign(upload, upload),
    document: Object.assign(document, document),
    submit: Object.assign(submit, submit),
}

export default verification