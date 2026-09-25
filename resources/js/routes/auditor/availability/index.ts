import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/auditor/availability',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
update.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditorProfileController::update
* @see app/Http/Controllers/AuditorProfileController.php:77
* @route '/auditor/availability'
*/
updateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

update.form = updateForm

const availability = {
    update: Object.assign(update, update),
}

export default availability