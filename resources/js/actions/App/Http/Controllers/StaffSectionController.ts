import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffSectionController::show
* @see app/Http/Controllers/StaffSectionController.php:31
* @route '/admin/{section}'
*/
export const show = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/admin/{section}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffSectionController::show
* @see app/Http/Controllers/StaffSectionController.php:31
* @route '/admin/{section}'
*/
show.url = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { section: args }
    }

    if (Array.isArray(args)) {
        args = {
            section: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        section: args.section,
    }

    return show.definition.url
            .replace('{section}', parsedArgs.section.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffSectionController::show
* @see app/Http/Controllers/StaffSectionController.php:31
* @route '/admin/{section}'
*/
show.get = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSectionController::show
* @see app/Http/Controllers/StaffSectionController.php:31
* @route '/admin/{section}'
*/
show.head = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffSectionController::show
* @see app/Http/Controllers/StaffSectionController.php:31
* @route '/admin/{section}'
*/
const showForm = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSectionController::show
* @see app/Http/Controllers/StaffSectionController.php:31
* @route '/admin/{section}'
*/
showForm.get = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffSectionController::show
* @see app/Http/Controllers/StaffSectionController.php:31
* @route '/admin/{section}'
*/
showForm.head = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const StaffSectionController = { show }

export default StaffSectionController