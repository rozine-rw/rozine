import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::index
* @see app/Http/Controllers/StaffStagingMailTesterController.php:33
* @route '/admin/staging-mail-testers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/staging-mail-testers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::index
* @see app/Http/Controllers/StaffStagingMailTesterController.php:33
* @route '/admin/staging-mail-testers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::index
* @see app/Http/Controllers/StaffStagingMailTesterController.php:33
* @route '/admin/staging-mail-testers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::index
* @see app/Http/Controllers/StaffStagingMailTesterController.php:33
* @route '/admin/staging-mail-testers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::index
* @see app/Http/Controllers/StaffStagingMailTesterController.php:33
* @route '/admin/staging-mail-testers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::index
* @see app/Http/Controllers/StaffStagingMailTesterController.php:33
* @route '/admin/staging-mail-testers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::index
* @see app/Http/Controllers/StaffStagingMailTesterController.php:33
* @route '/admin/staging-mail-testers'
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
* @see \App\Http\Controllers\StaffStagingMailTesterController::store
* @see app/Http/Controllers/StaffStagingMailTesterController.php:42
* @route '/admin/staging-mail-testers'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/staging-mail-testers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::store
* @see app/Http/Controllers/StaffStagingMailTesterController.php:42
* @route '/admin/staging-mail-testers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::store
* @see app/Http/Controllers/StaffStagingMailTesterController.php:42
* @route '/admin/staging-mail-testers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::store
* @see app/Http/Controllers/StaffStagingMailTesterController.php:42
* @route '/admin/staging-mail-testers'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::store
* @see app/Http/Controllers/StaffStagingMailTesterController.php:42
* @route '/admin/staging-mail-testers'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::remove
* @see app/Http/Controllers/StaffStagingMailTesterController.php:48
* @route '/admin/staging-mail-testers/{tester}/remove'
*/
export const remove = (args: { tester: string | number } | [tester: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: remove.url(args, options),
    method: 'post',
})

remove.definition = {
    methods: ["post"],
    url: '/admin/staging-mail-testers/{tester}/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::remove
* @see app/Http/Controllers/StaffStagingMailTesterController.php:48
* @route '/admin/staging-mail-testers/{tester}/remove'
*/
remove.url = (args: { tester: string | number } | [tester: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tester: args }
    }

    if (Array.isArray(args)) {
        args = {
            tester: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        tester: args.tester,
    }

    return remove.definition.url
            .replace('{tester}', parsedArgs.tester.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::remove
* @see app/Http/Controllers/StaffStagingMailTesterController.php:48
* @route '/admin/staging-mail-testers/{tester}/remove'
*/
remove.post = (args: { tester: string | number } | [tester: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: remove.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::remove
* @see app/Http/Controllers/StaffStagingMailTesterController.php:48
* @route '/admin/staging-mail-testers/{tester}/remove'
*/
const removeForm = (args: { tester: string | number } | [tester: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: remove.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffStagingMailTesterController::remove
* @see app/Http/Controllers/StaffStagingMailTesterController.php:48
* @route '/admin/staging-mail-testers/{tester}/remove'
*/
removeForm.post = (args: { tester: string | number } | [tester: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: remove.url(args, options),
    method: 'post',
})

remove.form = removeForm

const StaffStagingMailTesterController = { index, store, remove }

export default StaffStagingMailTesterController