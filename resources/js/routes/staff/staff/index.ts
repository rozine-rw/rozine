import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:40
* @route '/admin/staff'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/staff',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:40
* @route '/admin/staff'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:40
* @route '/admin/staff'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:40
* @route '/admin/staff'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:40
* @route '/admin/staff'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:40
* @route '/admin/staff'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::index
* @see app/Http/Controllers/StaffDirectoryController.php:40
* @route '/admin/staff'
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
* @see \App\Http\Controllers\StaffDirectoryController::grant
* @see app/Http/Controllers/StaffDirectoryController.php:57
* @route '/admin/staff'
*/
export const grant = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grant.url(options),
    method: 'post',
})

grant.definition = {
    methods: ["post"],
    url: '/admin/staff',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::grant
* @see app/Http/Controllers/StaffDirectoryController.php:57
* @route '/admin/staff'
*/
grant.url = (options?: RouteQueryOptions) => {
    return grant.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::grant
* @see app/Http/Controllers/StaffDirectoryController.php:57
* @route '/admin/staff'
*/
grant.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grant.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::grant
* @see app/Http/Controllers/StaffDirectoryController.php:57
* @route '/admin/staff'
*/
const grantForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grant.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::grant
* @see app/Http/Controllers/StaffDirectoryController.php:57
* @route '/admin/staff'
*/
grantForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grant.url(options),
    method: 'post',
})

grant.form = grantForm

/**
* @see \App\Http\Controllers\StaffDirectoryController::suspend
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/suspend'
*/
export const suspend = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: suspend.url(args, options),
    method: 'post',
})

suspend.definition = {
    methods: ["post"],
    url: '/admin/staff/{operator}/suspend',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::suspend
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/suspend'
*/
suspend.url = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return suspend.definition.url
            .replace('{operator}', parsedArgs.operator.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::suspend
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/suspend'
*/
suspend.post = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: suspend.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::suspend
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/suspend'
*/
const suspendForm = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: suspend.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::suspend
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/suspend'
*/
suspendForm.post = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: suspend.url(args, options),
    method: 'post',
})

suspend.form = suspendForm

/**
* @see \App\Http\Controllers\StaffDirectoryController::reactivate
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/reactivate'
*/
export const reactivate = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reactivate.url(args, options),
    method: 'post',
})

reactivate.definition = {
    methods: ["post"],
    url: '/admin/staff/{operator}/reactivate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDirectoryController::reactivate
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/reactivate'
*/
reactivate.url = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return reactivate.definition.url
            .replace('{operator}', parsedArgs.operator.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDirectoryController::reactivate
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/reactivate'
*/
reactivate.post = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reactivate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::reactivate
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/reactivate'
*/
const reactivateForm = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reactivate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDirectoryController::reactivate
* @see app/Http/Controllers/StaffDirectoryController.php:66
* @route '/admin/staff/{operator}/reactivate'
*/
reactivateForm.post = (args: { operator: string | number } | [operator: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reactivate.url(args, options),
    method: 'post',
})

reactivate.form = reactivateForm

const staff = {
    index: Object.assign(index, index),
    grant: Object.assign(grant, grant),
    suspend: Object.assign(suspend, suspend),
    reactivate: Object.assign(reactivate, reactivate),
}

export default staff