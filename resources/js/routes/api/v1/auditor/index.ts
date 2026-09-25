import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
import engagement from './engagement'
import jobs from './jobs'
import conflicts from './conflicts'
import accreditation from './accreditation'
import availability from './availability'
import operations from './operations'
/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::profile
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:39
* @route '/api/v1/auditor/profile'
*/
export const profile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/api/v1/auditor/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::profile
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:39
* @route '/api/v1/auditor/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::profile
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:39
* @route '/api/v1/auditor/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::profile
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:39
* @route '/api/v1/auditor/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::profile
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:39
* @route '/api/v1/auditor/profile'
*/
const profileForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::profile
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:39
* @route '/api/v1/auditor/profile'
*/
profileForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AuditorProfileController::profile
* @see app/Http/Controllers/Api/V1/AuditorProfileController.php:39
* @route '/api/v1/auditor/profile'
*/
profileForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

profile.form = profileForm

const auditor = {
    engagement: Object.assign(engagement, engagement),
    jobs: Object.assign(jobs, jobs),
    conflicts: Object.assign(conflicts, conflicts),
    profile: Object.assign(profile, profile),
    accreditation: Object.assign(accreditation, accreditation),
    availability: Object.assign(availability, availability),
    operations: Object.assign(operations, operations),
}

export default auditor