import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
export const save = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/investor/verification/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
save.url = (options?: RouteQueryOptions) => {
    return save.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
save.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
const saveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
saveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url(options),
    method: 'post',
})

save.form = saveForm

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
export const upload = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(options),
    method: 'post',
})

upload.definition = {
    methods: ["post"],
    url: '/investor/verification/documents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
upload.url = (options?: RouteQueryOptions) => {
    return upload.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
upload.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
const uploadForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
uploadForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(options),
    method: 'post',
})

upload.form = uploadForm

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
export const submit = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/investor/verification/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
submit.url = (options?: RouteQueryOptions) => {
    return submit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
submit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
const submitForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
submitForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

submit.form = submitForm

const verification = {
    save: Object.assign(save, save),
    upload: Object.assign(upload, upload),
    submit: Object.assign(submit, submit),
}

export default verification