import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import notices5868c6 from './notices'
import reports from './reports'
import engagement from './engagement'
import jobs from './jobs'
import conflicts from './conflicts'
import portfolio from './portfolio'
import accreditation from './accreditation'
import availability from './availability'
import operations from './operations'
/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:30
* @route '/auditor'
*/
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/auditor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:30
* @route '/auditor'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:30
* @route '/auditor'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:30
* @route '/auditor'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:30
* @route '/auditor'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:30
* @route '/auditor'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorHomeController::__invoke
* @see app/Http/Controllers/AuditorHomeController.php:30
* @route '/auditor'
*/
homeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

home.form = homeForm

/**
* @see \App\Http\Controllers\AuditorProfileController::profile
* @see app/Http/Controllers/AuditorProfileController.php:41
* @route '/auditor/profile'
*/
export const profile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/auditor/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::profile
* @see app/Http/Controllers/AuditorProfileController.php:41
* @route '/auditor/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProfileController::profile
* @see app/Http/Controllers/AuditorProfileController.php:41
* @route '/auditor/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::profile
* @see app/Http/Controllers/AuditorProfileController.php:41
* @route '/auditor/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::profile
* @see app/Http/Controllers/AuditorProfileController.php:41
* @route '/auditor/profile'
*/
const profileForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::profile
* @see app/Http/Controllers/AuditorProfileController.php:41
* @route '/auditor/profile'
*/
profileForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::profile
* @see app/Http/Controllers/AuditorProfileController.php:41
* @route '/auditor/profile'
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

/**
* @see \App\Http\Controllers\AuditorNoticesController::notices
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
export const notices = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notices.url(options),
    method: 'get',
})

notices.definition = {
    methods: ["get","head"],
    url: '/auditor/notices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditorNoticesController::notices
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
notices.url = (options?: RouteQueryOptions) => {
    return notices.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorNoticesController::notices
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
notices.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::notices
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
notices.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notices.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::notices
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
const noticesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::notices
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
noticesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditorNoticesController::notices
* @see app/Http/Controllers/AuditorNoticesController.php:17
* @route '/auditor/notices'
*/
noticesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notices.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

notices.form = noticesForm

const auditor = {
    home: Object.assign(home, home),
    profile: Object.assign(profile, profile),
    notices: Object.assign(notices, notices5868c6),
    reports: Object.assign(reports, reports),
    engagement: Object.assign(engagement, engagement),
    jobs: Object.assign(jobs, jobs),
    conflicts: Object.assign(conflicts, conflicts),
    portfolio: Object.assign(portfolio, portfolio),
    accreditation: Object.assign(accreditation, accreditation),
    availability: Object.assign(availability, availability),
    operations: Object.assign(operations, operations),
}

export default auditor