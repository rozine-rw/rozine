import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults, validateParameters } from './../../wayfinder'
import auditReports from './audit-reports'
import repayments from './repayments'
import wallet from './wallet'
import applications from './applications'
import campaigns from './campaigns'
/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/business',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleHomeController::__invoke
* @see app/Http/Controllers/RoleHomeController.php:20
* @route '/business'
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
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/business/{business}'
*/
export const show = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/business/{business}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/business/{business}'
*/
show.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/business/{business}'
*/
show.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/business/{business}'
*/
show.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/business/{business}'
*/
const showForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/business/{business}'
*/
showForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::show
* @see app/Http/Controllers/BusinessHomeController.php:28
* @route '/business/{business}'
*/
showForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:42
* @route '/business/{business}/reports'
*/
export const reports = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reports.url(args, options),
    method: 'get',
})

reports.definition = {
    methods: ["get","head"],
    url: '/business/{business}/reports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:42
* @route '/business/{business}/reports'
*/
reports.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return reports.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:42
* @route '/business/{business}/reports'
*/
reports.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reports.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:42
* @route '/business/{business}/reports'
*/
reports.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reports.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:42
* @route '/business/{business}/reports'
*/
const reportsForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reports.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:42
* @route '/business/{business}/reports'
*/
reportsForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reports.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::reports
* @see app/Http/Controllers/BusinessHomeController.php:42
* @route '/business/{business}/reports'
*/
reportsForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reports.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

reports.form = reportsForm

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:50
* @route '/business/{business}/profile/{section?}'
*/
export const profile = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(args, options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/business/{business}/profile/{section?}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:50
* @route '/business/{business}/profile/{section?}'
*/
profile.url = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            section: args[1],
        }
    }

    args = applyUrlDefaults(args)

    validateParameters(args, [
        "section",
    ])

    const parsedArgs = {
        business: args.business,
        section: args.section,
    }

    return profile.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{section?}', parsedArgs.section?.toString() ?? '')
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:50
* @route '/business/{business}/profile/{section?}'
*/
profile.get = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:50
* @route '/business/{business}/profile/{section?}'
*/
profile.head = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:50
* @route '/business/{business}/profile/{section?}'
*/
const profileForm = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:50
* @route '/business/{business}/profile/{section?}'
*/
profileForm.get = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::profile
* @see app/Http/Controllers/BusinessHomeController.php:50
* @route '/business/{business}/profile/{section?}'
*/
profileForm.head = (args: { business: string | number, section?: string | number } | [business: string | number, section: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

profile.form = profileForm

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:35
* @route '/business/{business}/rating'
*/
export const rating = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rating.url(args, options),
    method: 'get',
})

rating.definition = {
    methods: ["get","head"],
    url: '/business/{business}/rating',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:35
* @route '/business/{business}/rating'
*/
rating.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return rating.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:35
* @route '/business/{business}/rating'
*/
rating.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rating.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:35
* @route '/business/{business}/rating'
*/
rating.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: rating.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:35
* @route '/business/{business}/rating'
*/
const ratingForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rating.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:35
* @route '/business/{business}/rating'
*/
ratingForm.get = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rating.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BusinessHomeController::rating
* @see app/Http/Controllers/BusinessHomeController.php:35
* @route '/business/{business}/rating'
*/
ratingForm.head = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rating.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

rating.form = ratingForm

const business = {
    auditReports: Object.assign(auditReports, auditReports),
    home: Object.assign(home, home),
    repayments: Object.assign(repayments, repayments),
    show: Object.assign(show, show),
    reports: Object.assign(reports, reports),
    profile: Object.assign(profile, profile),
    rating: Object.assign(rating, rating),
    wallet: Object.assign(wallet, wallet),
    applications: Object.assign(applications, applications),
    campaigns: Object.assign(campaigns, campaigns),
}

export default business