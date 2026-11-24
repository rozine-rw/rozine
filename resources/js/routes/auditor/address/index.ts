import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:103
* @route '/auditor/address'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/auditor/address',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:103
* @route '/auditor/address'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:103
* @route '/auditor/address'
*/
update.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:103
* @route '/auditor/address'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:103
* @route '/auditor/address'
*/
updateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

update.form = updateForm

const address = {
    update: Object.assign(update, update),
}

export default address