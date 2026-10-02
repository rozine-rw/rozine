import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:24
* @route '/admin/applications'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/applications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:24
* @route '/admin/applications'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:24
* @route '/admin/applications'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:24
* @route '/admin/applications'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:24
* @route '/admin/applications'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:24
* @route '/admin/applications'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::index
* @see app/Http/Controllers/StaffApplicationReleaseController.php:24
* @route '/admin/applications'
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
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:34
* @route '/admin/applications/{application}'
*/
export const show = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/admin/applications/{application}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:34
* @route '/admin/applications/{application}'
*/
show.url = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { application: args }
    }

    if (Array.isArray(args)) {
        args = {
            application: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        application: args.application,
    }

    return show.definition.url
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:34
* @route '/admin/applications/{application}'
*/
show.get = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:34
* @route '/admin/applications/{application}'
*/
show.head = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:34
* @route '/admin/applications/{application}'
*/
const showForm = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:34
* @route '/admin/applications/{application}'
*/
showForm.get = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::show
* @see app/Http/Controllers/StaffApplicationReleaseController.php:34
* @route '/admin/applications/{application}'
*/
showForm.head = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:41
* @route '/admin/applications/{application}/release'
*/
export const release = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

release.definition = {
    methods: ["post"],
    url: '/admin/applications/{application}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:41
* @route '/admin/applications/{application}/release'
*/
release.url = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { application: args }
    }

    if (Array.isArray(args)) {
        args = {
            application: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        application: args.application,
    }

    return release.definition.url
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:41
* @route '/admin/applications/{application}/release'
*/
release.post = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:41
* @route '/admin/applications/{application}/release'
*/
const releaseForm = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffApplicationReleaseController::release
* @see app/Http/Controllers/StaffApplicationReleaseController.php:41
* @route '/admin/applications/{application}/release'
*/
releaseForm.post = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: release.url(args, options),
    method: 'post',
})

release.form = releaseForm

const applications = {
    operations: Object.assign(operations, operations),
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    release: Object.assign(release, release),
}

export default applications