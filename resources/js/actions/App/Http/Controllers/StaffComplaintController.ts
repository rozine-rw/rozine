import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
const index86212cd5b0a84daa87913ff5f03873b2 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index86212cd5b0a84daa87913ff5f03873b2.url(options),
    method: 'get',
})

index86212cd5b0a84daa87913ff5f03873b2.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/complaints',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
index86212cd5b0a84daa87913ff5f03873b2.url = (options?: RouteQueryOptions) => {
    return index86212cd5b0a84daa87913ff5f03873b2.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
index86212cd5b0a84daa87913ff5f03873b2.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index86212cd5b0a84daa87913ff5f03873b2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
index86212cd5b0a84daa87913ff5f03873b2.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index86212cd5b0a84daa87913ff5f03873b2.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
const index86212cd5b0a84daa87913ff5f03873b2Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index86212cd5b0a84daa87913ff5f03873b2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
index86212cd5b0a84daa87913ff5f03873b2Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index86212cd5b0a84daa87913ff5f03873b2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/api/v1/staff/complaints'
*/
index86212cd5b0a84daa87913ff5f03873b2Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index86212cd5b0a84daa87913ff5f03873b2.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index86212cd5b0a84daa87913ff5f03873b2.form = index86212cd5b0a84daa87913ff5f03873b2Form
/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
const index06e6149ec28706be7f9e727aae3ff30c = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index06e6149ec28706be7f9e727aae3ff30c.url(options),
    method: 'get',
})

index06e6149ec28706be7f9e727aae3ff30c.definition = {
    methods: ["get","head"],
    url: '/admin/complaints',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
index06e6149ec28706be7f9e727aae3ff30c.url = (options?: RouteQueryOptions) => {
    return index06e6149ec28706be7f9e727aae3ff30c.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
index06e6149ec28706be7f9e727aae3ff30c.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index06e6149ec28706be7f9e727aae3ff30c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
index06e6149ec28706be7f9e727aae3ff30c.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index06e6149ec28706be7f9e727aae3ff30c.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
const index06e6149ec28706be7f9e727aae3ff30cForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index06e6149ec28706be7f9e727aae3ff30c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
index06e6149ec28706be7f9e727aae3ff30cForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index06e6149ec28706be7f9e727aae3ff30c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffComplaintController::index
* @see app/Http/Controllers/StaffComplaintController.php:32
* @route '/admin/complaints'
*/
index06e6149ec28706be7f9e727aae3ff30cForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index06e6149ec28706be7f9e727aae3ff30c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index06e6149ec28706be7f9e727aae3ff30c.form = index06e6149ec28706be7f9e727aae3ff30cForm

/**
* Multiple routes resolve to \App\Http\Controllers\StaffComplaintController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/complaints': index86212cd5b0a84daa87913ff5f03873b2,
    '/admin/complaints': index06e6149ec28706be7f9e727aae3ff30c,
}

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

const StaffComplaintController = { index, answer }

export default StaffComplaintController