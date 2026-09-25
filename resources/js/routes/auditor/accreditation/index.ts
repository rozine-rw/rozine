import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import certificates from './certificates'
/**
* @see \App\Http\Controllers\AuditorProfileController::submit
* @see app/Http/Controllers/AuditorProfileController.php:59
* @route '/auditor/accreditation'
*/
export const submit = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/auditor/accreditation',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::submit
* @see app/Http/Controllers/AuditorProfileController.php:59
* @route '/auditor/accreditation'
*/
submit.url = (options?: RouteQueryOptions) => {
    return submit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProfileController::submit
* @see app/Http/Controllers/AuditorProfileController.php:59
* @route '/auditor/accreditation'
*/
submit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::submit
* @see app/Http/Controllers/AuditorProfileController.php:59
* @route '/auditor/accreditation'
*/
const submitForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::submit
* @see app/Http/Controllers/AuditorProfileController.php:59
* @route '/auditor/accreditation'
*/
submitForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

submit.form = submitForm

/**
* @see \App\Http\Controllers\AuditorProfileController::renew
* @see app/Http/Controllers/AuditorProfileController.php:64
* @route '/auditor/accreditation/renewal'
*/
export const renew = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renew.url(options),
    method: 'post',
})

renew.definition = {
    methods: ["post"],
    url: '/auditor/accreditation/renewal',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::renew
* @see app/Http/Controllers/AuditorProfileController.php:64
* @route '/auditor/accreditation/renewal'
*/
renew.url = (options?: RouteQueryOptions) => {
    return renew.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProfileController::renew
* @see app/Http/Controllers/AuditorProfileController.php:64
* @route '/auditor/accreditation/renewal'
*/
renew.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renew.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::renew
* @see app/Http/Controllers/AuditorProfileController.php:64
* @route '/auditor/accreditation/renewal'
*/
const renewForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: renew.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::renew
* @see app/Http/Controllers/AuditorProfileController.php:64
* @route '/auditor/accreditation/renewal'
*/
renewForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: renew.url(options),
    method: 'post',
})

renew.form = renewForm

/**
* @see \App\Http\Controllers\AuditorProfileController::withdraw
* @see app/Http/Controllers/AuditorProfileController.php:69
* @route '/auditor/accreditation/withdrawal'
*/
export const withdraw = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw.url(options),
    method: 'post',
})

withdraw.definition = {
    methods: ["post"],
    url: '/auditor/accreditation/withdrawal',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::withdraw
* @see app/Http/Controllers/AuditorProfileController.php:69
* @route '/auditor/accreditation/withdrawal'
*/
withdraw.url = (options?: RouteQueryOptions) => {
    return withdraw.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProfileController::withdraw
* @see app/Http/Controllers/AuditorProfileController.php:69
* @route '/auditor/accreditation/withdrawal'
*/
withdraw.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::withdraw
* @see app/Http/Controllers/AuditorProfileController.php:69
* @route '/auditor/accreditation/withdrawal'
*/
const withdrawForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdraw.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::withdraw
* @see app/Http/Controllers/AuditorProfileController.php:69
* @route '/auditor/accreditation/withdrawal'
*/
withdrawForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: withdraw.url(options),
    method: 'post',
})

withdraw.form = withdrawForm

const accreditation = {
    submit: Object.assign(submit, submit),
    renew: Object.assign(renew, renew),
    withdraw: Object.assign(withdraw, withdraw),
    certificates: Object.assign(certificates, certificates),
}

export default accreditation