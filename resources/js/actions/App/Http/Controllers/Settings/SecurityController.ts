import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\SecurityController::edit
* @see app/Http/Controllers/Settings/SecurityController.php:23
* @route '/settings/security'
*/
export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/settings/security',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\SecurityController::edit
* @see app/Http/Controllers/Settings/SecurityController.php:23
* @route '/settings/security'
*/
edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\SecurityController::edit
* @see app/Http/Controllers/Settings/SecurityController.php:23
* @route '/settings/security'
*/
edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\SecurityController::edit
* @see app/Http/Controllers/Settings/SecurityController.php:23
* @route '/settings/security'
*/
edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\SecurityController::edit
* @see app/Http/Controllers/Settings/SecurityController.php:23
* @route '/settings/security'
*/
const editForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\SecurityController::edit
* @see app/Http/Controllers/Settings/SecurityController.php:23
* @route '/settings/security'
*/
editForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\SecurityController::edit
* @see app/Http/Controllers/Settings/SecurityController.php:23
* @route '/settings/security'
*/
editForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

/**
* @see \App\Http\Controllers\Settings\SecurityController::update
* @see app/Http/Controllers/Settings/SecurityController.php:64
* @route '/settings/password'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/settings/password',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Settings\SecurityController::update
* @see app/Http/Controllers/Settings/SecurityController.php:64
* @route '/settings/password'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\SecurityController::update
* @see app/Http/Controllers/Settings/SecurityController.php:64
* @route '/settings/password'
*/
update.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Settings\SecurityController::update
* @see app/Http/Controllers/Settings/SecurityController.php:64
* @route '/settings/password'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\SecurityController::update
* @see app/Http/Controllers/Settings/SecurityController.php:64
* @route '/settings/password'
*/
updateForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\Settings\SecurityController::updateLoginAlerts
* @see app/Http/Controllers/Settings/SecurityController.php:79
* @route '/settings/login-alerts'
*/
export const updateLoginAlerts = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateLoginAlerts.url(options),
    method: 'put',
})

updateLoginAlerts.definition = {
    methods: ["put"],
    url: '/settings/login-alerts',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Settings\SecurityController::updateLoginAlerts
* @see app/Http/Controllers/Settings/SecurityController.php:79
* @route '/settings/login-alerts'
*/
updateLoginAlerts.url = (options?: RouteQueryOptions) => {
    return updateLoginAlerts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\SecurityController::updateLoginAlerts
* @see app/Http/Controllers/Settings/SecurityController.php:79
* @route '/settings/login-alerts'
*/
updateLoginAlerts.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateLoginAlerts.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Settings\SecurityController::updateLoginAlerts
* @see app/Http/Controllers/Settings/SecurityController.php:79
* @route '/settings/login-alerts'
*/
const updateLoginAlertsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateLoginAlerts.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\SecurityController::updateLoginAlerts
* @see app/Http/Controllers/Settings/SecurityController.php:79
* @route '/settings/login-alerts'
*/
updateLoginAlertsForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateLoginAlerts.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateLoginAlerts.form = updateLoginAlertsForm

const SecurityController = { edit, update, updateLoginAlerts }

export default SecurityController