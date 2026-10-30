import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/api/v1/staff/businesses'
*/
const indexdb3a888129fba9a701bb6313c25269b6 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'get',
})

indexdb3a888129fba9a701bb6313c25269b6.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/businesses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6.url = (options?: RouteQueryOptions) => {
    return indexdb3a888129fba9a701bb6313c25269b6.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/api/v1/staff/businesses'
*/
const indexdb3a888129fba9a701bb6313c25269b6Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexdb3a888129fba9a701bb6313c25269b6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/api/v1/staff/businesses'
*/
indexdb3a888129fba9a701bb6313c25269b6Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexdb3a888129fba9a701bb6313c25269b6.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexdb3a888129fba9a701bb6313c25269b6.form = indexdb3a888129fba9a701bb6313c25269b6Form
/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
const index8c8e212d400b1e5f7bb096b3a45096d9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'get',
})

index8c8e212d400b1e5f7bb096b3a45096d9.definition = {
    methods: ["get","head"],
    url: '/admin/businesses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9.url = (options?: RouteQueryOptions) => {
    return index8c8e212d400b1e5f7bb096b3a45096d9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
const index8c8e212d400b1e5f7bb096b3a45096d9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8c8e212d400b1e5f7bb096b3a45096d9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::index
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:77
* @route '/admin/businesses'
*/
index8c8e212d400b1e5f7bb096b3a45096d9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index8c8e212d400b1e5f7bb096b3a45096d9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index8c8e212d400b1e5f7bb096b3a45096d9.form = index8c8e212d400b1e5f7bb096b3a45096d9Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffBusinessDirectoryController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/businesses': indexdb3a888129fba9a701bb6313c25269b6,
    '/admin/businesses': index8c8e212d400b1e5f7bb096b3a45096d9,
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::premises
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:108
* @route '/admin/businesses/{business}/premises'
*/
export const premises = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: premises.url(args, options),
    method: 'post',
})

premises.definition = {
    methods: ["post"],
    url: '/admin/businesses/{business}/premises',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::premises
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:108
* @route '/admin/businesses/{business}/premises'
*/
premises.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return premises.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::premises
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:108
* @route '/admin/businesses/{business}/premises'
*/
premises.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: premises.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::premises
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:108
* @route '/admin/businesses/{business}/premises'
*/
const premisesForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: premises.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::premises
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:108
* @route '/admin/businesses/{business}/premises'
*/
premisesForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: premises.url(args, options),
    method: 'post',
})

premises.form = premisesForm

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flashAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
export const flashAudit = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: flashAudit.url(args, options),
    method: 'post',
})

flashAudit.definition = {
    methods: ["post"],
    url: '/admin/businesses/{business}/flash-audit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flashAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
flashAudit.url = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return flashAudit.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flashAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
flashAudit.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: flashAudit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flashAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
const flashAuditForm = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: flashAudit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::flashAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:126
* @route '/admin/businesses/{business}/flash-audit'
*/
flashAuditForm.post = (args: { business: string | number } | [business: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: flashAudit.url(args, options),
    method: 'post',
})

flashAudit.form = flashAuditForm

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatchAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
export const redispatchAudit = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redispatchAudit.url(args, options),
    method: 'post',
})

redispatchAudit.definition = {
    methods: ["post"],
    url: '/admin/businesses/{business}/audits/{assignment}/redispatch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatchAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
redispatchAudit.url = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            assignment: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        assignment: args.assignment,
    }

    return redispatchAudit.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatchAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
redispatchAudit.post = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redispatchAudit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatchAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
const redispatchAuditForm = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redispatchAudit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::redispatchAudit
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:139
* @route '/admin/businesses/{business}/audits/{assignment}/redispatch'
*/
redispatchAuditForm.post = (args: { business: string | number, assignment: string | number } | [business: string | number, assignment: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redispatchAudit.url(args, options),
    method: 'post',
})

redispatchAudit.form = redispatchAuditForm

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:160
* @route '/admin/businesses/onboard'
*/
export const onboard = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: onboard.url(options),
    method: 'post',
})

onboard.definition = {
    methods: ["post"],
    url: '/admin/businesses/onboard',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:160
* @route '/admin/businesses/onboard'
*/
onboard.url = (options?: RouteQueryOptions) => {
    return onboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:160
* @route '/admin/businesses/onboard'
*/
onboard.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: onboard.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:160
* @route '/admin/businesses/onboard'
*/
const onboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: onboard.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffBusinessDirectoryController::onboard
* @see app/Http/Controllers/StaffBusinessDirectoryController.php:160
* @route '/admin/businesses/onboard'
*/
onboardForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: onboard.url(options),
    method: 'post',
})

onboard.form = onboardForm

const StaffBusinessDirectoryController = { index, premises, flashAudit, redispatchAudit, onboard }

export default StaffBusinessDirectoryController