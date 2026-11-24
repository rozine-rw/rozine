import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffRoleApplicationController::document
* @see app/Http/Controllers/StaffRoleApplicationController.php:39
* @route '/admin/role-applications/{application}/documents/{document}'
*/
export const document = (args: { application: string | number, document: string | number } | [application: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document.url(args, options),
    method: 'get',
})

document.definition = {
    methods: ["get","head"],
    url: '/admin/role-applications/{application}/documents/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::document
* @see app/Http/Controllers/StaffRoleApplicationController.php:39
* @route '/admin/role-applications/{application}/documents/{document}'
*/
document.url = (args: { application: string | number, document: string | number } | [application: string | number, document: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            application: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        application: args.application,
        document: args.document,
    }

    return document.definition.url
            .replace('{application}', parsedArgs.application.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::document
* @see app/Http/Controllers/StaffRoleApplicationController.php:39
* @route '/admin/role-applications/{application}/documents/{document}'
*/
document.get = (args: { application: string | number, document: string | number } | [application: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::document
* @see app/Http/Controllers/StaffRoleApplicationController.php:39
* @route '/admin/role-applications/{application}/documents/{document}'
*/
document.head = (args: { application: string | number, document: string | number } | [application: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: document.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::document
* @see app/Http/Controllers/StaffRoleApplicationController.php:39
* @route '/admin/role-applications/{application}/documents/{document}'
*/
const documentForm = (args: { application: string | number, document: string | number } | [application: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::document
* @see app/Http/Controllers/StaffRoleApplicationController.php:39
* @route '/admin/role-applications/{application}/documents/{document}'
*/
documentForm.get = (args: { application: string | number, document: string | number } | [application: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::document
* @see app/Http/Controllers/StaffRoleApplicationController.php:39
* @route '/admin/role-applications/{application}/documents/{document}'
*/
documentForm.head = (args: { application: string | number, document: string | number } | [application: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\StaffRoleApplicationController::decide
* @see app/Http/Controllers/StaffRoleApplicationController.php:52
* @route '/admin/role-applications/{application}/decide'
*/
export const decide = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decide.url(args, options),
    method: 'post',
})

decide.definition = {
    methods: ["post"],
    url: '/admin/role-applications/{application}/decide',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::decide
* @see app/Http/Controllers/StaffRoleApplicationController.php:52
* @route '/admin/role-applications/{application}/decide'
*/
decide.url = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return decide.definition.url
            .replace('{application}', parsedArgs.application.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::decide
* @see app/Http/Controllers/StaffRoleApplicationController.php:52
* @route '/admin/role-applications/{application}/decide'
*/
decide.post = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decide.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::decide
* @see app/Http/Controllers/StaffRoleApplicationController.php:52
* @route '/admin/role-applications/{application}/decide'
*/
const decideForm = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decide.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffRoleApplicationController::decide
* @see app/Http/Controllers/StaffRoleApplicationController.php:52
* @route '/admin/role-applications/{application}/decide'
*/
decideForm.post = (args: { application: string | number } | [application: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decide.url(args, options),
    method: 'post',
})

decide.form = decideForm

const roleApplications = {
    document: Object.assign(document, document),
    decide: Object.assign(decide, decide),
}

export default roleApplications