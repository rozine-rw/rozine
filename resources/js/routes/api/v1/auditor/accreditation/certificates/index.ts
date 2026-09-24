import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::show
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:95
* @route '/api/v1/auditor/accreditation/certificates/{certificate}'
*/
export const show = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/auditor/accreditation/certificates/{certificate}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::show
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:95
* @route '/api/v1/auditor/accreditation/certificates/{certificate}'
*/
show.url = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{certificate}', parsedArgs.certificate.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::show
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:95
* @route '/api/v1/auditor/accreditation/certificates/{certificate}'
*/
show.get = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::show
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:95
* @route '/api/v1/auditor/accreditation/certificates/{certificate}'
*/
show.head = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::show
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:95
* @route '/api/v1/auditor/accreditation/certificates/{certificate}'
*/
const showForm = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::show
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:95
* @route '/api/v1/auditor/accreditation/certificates/{certificate}'
*/
showForm.get = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::show
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:95
* @route '/api/v1/auditor/accreditation/certificates/{certificate}'
*/
showForm.head = (args: { certificate: string | number } | [certificate: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const certificates = {
    show: Object.assign(show, show),
}

export default certificates