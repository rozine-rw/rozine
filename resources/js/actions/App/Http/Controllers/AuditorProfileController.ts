import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorProfileController::show
* @see app/Http/Controllers/AuditorProfileController.php:39
* @route '/auditor/profile'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/auditor/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::show
* @see app/Http/Controllers/AuditorProfileController.php:39
* @route '/auditor/profile'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProfileController::show
* @see app/Http/Controllers/AuditorProfileController.php:39
* @route '/auditor/profile'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::show
* @see app/Http/Controllers/AuditorProfileController.php:39
* @route '/auditor/profile'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::show
* @see app/Http/Controllers/AuditorProfileController.php:39
* @route '/auditor/profile'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::show
* @see app/Http/Controllers/AuditorProfileController.php:39
* @route '/auditor/profile'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::show
* @see app/Http/Controllers/AuditorProfileController.php:39
* @route '/auditor/profile'
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

/**
* @see \App\Http\Controllers\AuditorProfileController::certificate
* @see app/Http/Controllers/AuditorProfileController.php:97
* @route '/auditor/accreditation/certificates/{certificate}'
*/
export const certificate = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificate.url(args, options),
    method: 'get',
})

certificate.definition = {
    methods: ["get","head"],
    url: '/auditor/accreditation/certificates/{certificate}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::certificate
* @see app/Http/Controllers/AuditorProfileController.php:97
* @route '/auditor/accreditation/certificates/{certificate}'
*/
certificate.url = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { certificate: args }
    }

    if (Array.isArray(args)) {
        args = {
            certificate: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        certificate: args.certificate,
    }

    return certificate.definition.url
            .replace('{certificate}', parsedArgs.certificate.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProfileController::certificate
* @see app/Http/Controllers/AuditorProfileController.php:97
* @route '/auditor/accreditation/certificates/{certificate}'
*/
certificate.get = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificate.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::certificate
* @see app/Http/Controllers/AuditorProfileController.php:97
* @route '/auditor/accreditation/certificates/{certificate}'
*/
certificate.head = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: certificate.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::certificate
* @see app/Http/Controllers/AuditorProfileController.php:97
* @route '/auditor/accreditation/certificates/{certificate}'
*/
const certificateForm = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificate.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::certificate
* @see app/Http/Controllers/AuditorProfileController.php:97
* @route '/auditor/accreditation/certificates/{certificate}'
*/
certificateForm.get = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificate.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::certificate
* @see app/Http/Controllers/AuditorProfileController.php:97
* @route '/auditor/accreditation/certificates/{certificate}'
*/
certificateForm.head = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificate.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

certificate.form = certificateForm

/**
* @see \App\Http\Controllers\AuditorProfileController::availability
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
export const availability = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: availability.url(options),
    method: 'post',
})

availability.definition = {
    methods: ["post"],
    url: '/auditor/availability',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::availability
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
availability.url = (options?: RouteQueryOptions) => {
    return availability.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProfileController::availability
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
availability.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: availability.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::availability
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
const availabilityForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: availability.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::availability
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
availabilityForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: availability.url(options),
    method: 'post',
})

availability.form = availabilityForm

/**
* @see \App\Http\Controllers\AuditorProfileController::operation
* @see app/Http/Controllers/AuditorProfileController.php:86
* @route '/auditor/operations/{request_id}'
*/
export const operation = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

operation.definition = {
    methods: ["get","head"],
    url: '/auditor/operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::operation
* @see app/Http/Controllers/AuditorProfileController.php:86
* @route '/auditor/operations/{request_id}'
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
* @see \App\Http\Controllers\AuditorProfileController::operation
* @see app/Http/Controllers/AuditorProfileController.php:86
* @route '/auditor/operations/{request_id}'
*/
operation.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::operation
* @see app/Http/Controllers/AuditorProfileController.php:86
* @route '/auditor/operations/{request_id}'
*/
operation.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::operation
* @see app/Http/Controllers/AuditorProfileController.php:86
* @route '/auditor/operations/{request_id}'
*/
const operationForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::operation
* @see app/Http/Controllers/AuditorProfileController.php:86
* @route '/auditor/operations/{request_id}'
*/
operationForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::operation
* @see app/Http/Controllers/AuditorProfileController.php:86
* @route '/auditor/operations/{request_id}'
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

const AuditorProfileController = { show, submit, renew, withdraw, certificate, availability, operation }

export default AuditorProfileController