import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/api/v1/staff/staff'
*/
const indexc83dab306741c8ddcc901f3f7b92cbb9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'get',
})

indexc83dab306741c8ddcc901f3f7b92cbb9.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/staff',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9.url = (options?: RouteQueryOptions) => {
    return indexc83dab306741c8ddcc901f3f7b92cbb9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/api/v1/staff/staff'
*/
const indexc83dab306741c8ddcc901f3f7b92cbb9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexc83dab306741c8ddcc901f3f7b92cbb9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/api/v1/staff/staff'
*/
indexc83dab306741c8ddcc901f3f7b92cbb9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexc83dab306741c8ddcc901f3f7b92cbb9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexc83dab306741c8ddcc901f3f7b92cbb9.form = indexc83dab306741c8ddcc901f3f7b92cbb9Form
/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/admin/staff'
*/
const index49be9ecafad32c357f5919028afa2421 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'get',
})

index49be9ecafad32c357f5919028afa2421.definition = {
    methods: ["get","head"],
    url: '/admin/staff',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421.url = (options?: RouteQueryOptions) => {
    return index49be9ecafad32c357f5919028afa2421.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/admin/staff'
*/
const index49be9ecafad32c357f5919028afa2421Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index49be9ecafad32c357f5919028afa2421.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:34
* @route '/admin/staff'
*/
index49be9ecafad32c357f5919028afa2421Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index49be9ecafad32c357f5919028afa2421.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index49be9ecafad32c357f5919028afa2421.form = index49be9ecafad32c357f5919028afa2421Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffDirectoryController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/staff': indexc83dab306741c8ddcc901f3f7b92cbb9,
    '/admin/staff': index49be9ecafad32c357f5919028afa2421,
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/suspend'
*/
const access4d348912b9cfe169b9a920534e123037 = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: access4d348912b9cfe169b9a920534e123037.url(args, options),
    method: 'post',
})

access4d348912b9cfe169b9a920534e123037.definition = {
    methods: ["post"],
    url: '/admin/staff/{operator}/suspend',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/suspend'
*/
access4d348912b9cfe169b9a920534e123037.url = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { operator: args }
    }

    if (Array.isArray(args)) {
        args = {
            operator: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        operator: args.operator,
    }

    return access4d348912b9cfe169b9a920534e123037.definition.url
            .replace('{operator}', parsedArgs.operator.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/suspend'
*/
access4d348912b9cfe169b9a920534e123037.post = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: access4d348912b9cfe169b9a920534e123037.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/suspend'
*/
const access4d348912b9cfe169b9a920534e123037Form = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: access4d348912b9cfe169b9a920534e123037.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/suspend'
*/
access4d348912b9cfe169b9a920534e123037Form.post = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: access4d348912b9cfe169b9a920534e123037.url(args, options),
    method: 'post',
})

access4d348912b9cfe169b9a920534e123037.form = access4d348912b9cfe169b9a920534e123037Form
/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/reactivate'
*/
const access1adabf4af3d9871fdd35603729f826aa = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: access1adabf4af3d9871fdd35603729f826aa.url(args, options),
    method: 'post',
})

access1adabf4af3d9871fdd35603729f826aa.definition = {
    methods: ["post"],
    url: '/admin/staff/{operator}/reactivate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/reactivate'
*/
access1adabf4af3d9871fdd35603729f826aa.url = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { operator: args }
    }

    if (Array.isArray(args)) {
        args = {
            operator: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        operator: args.operator,
    }

    return access1adabf4af3d9871fdd35603729f826aa.definition.url
            .replace('{operator}', parsedArgs.operator.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/reactivate'
*/
access1adabf4af3d9871fdd35603729f826aa.post = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: access1adabf4af3d9871fdd35603729f826aa.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/reactivate'
*/
const access1adabf4af3d9871fdd35603729f826aaForm = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: access1adabf4af3d9871fdd35603729f826aa.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::access
* @see app/Http/Controllers/StaffDirectoryController.php:51
* @route '/admin/staff/{operator}/reactivate'
*/
access1adabf4af3d9871fdd35603729f826aaForm.post = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: access1adabf4af3d9871fdd35603729f826aa.url(args, options),
    method: 'post',
})

access1adabf4af3d9871fdd35603729f826aa.form = access1adabf4af3d9871fdd35603729f826aaForm

/**
* Multiple routes resolve to \App\Http\Controllers\StaffDirectoryController::access, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `access['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const access = {
    '/admin/staff/{operator}/suspend': access4d348912b9cfe169b9a920534e123037,
    '/admin/staff/{operator}/reactivate': access1adabf4af3d9871fdd35603729f826aa,
}

const StaffDirectoryController = { index, access }

export default StaffDirectoryController