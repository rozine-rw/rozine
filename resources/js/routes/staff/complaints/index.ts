import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/complaints',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
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
* @see \App\Http\Controllers\StaffComplaintController::answer
* @see app/Http/Controllers/StaffComplaintController.php:44
* @route '/admin/complaints/{complaint}/answers'
*/
export const answer = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: answer.url(args, options),
    method: 'post',
})

answer.definition = {
    methods: ["post"],
    url: '/admin/complaints/{complaint}/answers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffComplaintController::answer
* @see app/Http/Controllers/StaffComplaintController.php:44
* @route '/admin/complaints/{complaint}/answers'
*/
answer.url = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { complaint: args }
    }

    if (Array.isArray(args)) {
        args = {
            complaint: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        complaint: args.complaint,
    }

    return answer.definition.url
            .replace('{complaint}', parsedArgs.complaint.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffComplaintController::answer
* @see app/Http/Controllers/StaffComplaintController.php:44
* @route '/admin/complaints/{complaint}/answers'
*/
answer.post = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: answer.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::answer
* @see app/Http/Controllers/StaffComplaintController.php:44
* @route '/admin/complaints/{complaint}/answers'
*/
const answerForm = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: answer.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::answer
* @see app/Http/Controllers/StaffComplaintController.php:44
* @route '/admin/complaints/{complaint}/answers'
*/
answerForm.post = (args: { complaint: string | number } | [complaint: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: answer.url(args, options),
    method: 'post',
})

answer.form = answerForm

const complaints = {
    index: Object.assign(index, index),
    answer: Object.assign(answer, answer),
}

export default complaints