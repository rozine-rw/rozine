import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
const show619dc3a99425f668ea9cab64e6648cb4 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show619dc3a99425f668ea9cab64e6648cb4.url(options),
    method: 'get',
})

show619dc3a99425f668ea9cab64e6648cb4.definition = {
    methods: ["get","head"],
    url: '/terms',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
show619dc3a99425f668ea9cab64e6648cb4.url = (options?: RouteQueryOptions) => {
    return show619dc3a99425f668ea9cab64e6648cb4.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
show619dc3a99425f668ea9cab64e6648cb4.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show619dc3a99425f668ea9cab64e6648cb4.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
show619dc3a99425f668ea9cab64e6648cb4.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show619dc3a99425f668ea9cab64e6648cb4.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
const show619dc3a99425f668ea9cab64e6648cb4Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show619dc3a99425f668ea9cab64e6648cb4.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
show619dc3a99425f668ea9cab64e6648cb4Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show619dc3a99425f668ea9cab64e6648cb4.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
show619dc3a99425f668ea9cab64e6648cb4Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show619dc3a99425f668ea9cab64e6648cb4.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show619dc3a99425f668ea9cab64e6648cb4.form = show619dc3a99425f668ea9cab64e6648cb4Form
/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
const showa2c058616aeb0c9393ca03a98bc05c02 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showa2c058616aeb0c9393ca03a98bc05c02.url(options),
    method: 'get',
})

showa2c058616aeb0c9393ca03a98bc05c02.definition = {
    methods: ["get","head"],
    url: '/privacy',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
showa2c058616aeb0c9393ca03a98bc05c02.url = (options?: RouteQueryOptions) => {
    return showa2c058616aeb0c9393ca03a98bc05c02.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
showa2c058616aeb0c9393ca03a98bc05c02.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showa2c058616aeb0c9393ca03a98bc05c02.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
showa2c058616aeb0c9393ca03a98bc05c02.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showa2c058616aeb0c9393ca03a98bc05c02.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
const showa2c058616aeb0c9393ca03a98bc05c02Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showa2c058616aeb0c9393ca03a98bc05c02.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
showa2c058616aeb0c9393ca03a98bc05c02Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showa2c058616aeb0c9393ca03a98bc05c02.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
showa2c058616aeb0c9393ca03a98bc05c02Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showa2c058616aeb0c9393ca03a98bc05c02.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showa2c058616aeb0c9393ca03a98bc05c02.form = showa2c058616aeb0c9393ca03a98bc05c02Form
/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
const show3290c002c36ba085034bd4b72e1546a6 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3290c002c36ba085034bd4b72e1546a6.url(options),
    method: 'get',
})

show3290c002c36ba085034bd4b72e1546a6.definition = {
    methods: ["get","head"],
    url: '/disclaimers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
show3290c002c36ba085034bd4b72e1546a6.url = (options?: RouteQueryOptions) => {
    return show3290c002c36ba085034bd4b72e1546a6.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
show3290c002c36ba085034bd4b72e1546a6.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3290c002c36ba085034bd4b72e1546a6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
show3290c002c36ba085034bd4b72e1546a6.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show3290c002c36ba085034bd4b72e1546a6.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
const show3290c002c36ba085034bd4b72e1546a6Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show3290c002c36ba085034bd4b72e1546a6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
show3290c002c36ba085034bd4b72e1546a6Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show3290c002c36ba085034bd4b72e1546a6.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::show
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
show3290c002c36ba085034bd4b72e1546a6Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show3290c002c36ba085034bd4b72e1546a6.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show3290c002c36ba085034bd4b72e1546a6.form = show3290c002c36ba085034bd4b72e1546a6Form

/**
* Multiple routes resolve to \App\Http\Controllers\LegalDocumentController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/terms': show619dc3a99425f668ea9cab64e6648cb4,
    '/privacy': showa2c058616aeb0c9393ca03a98bc05c02,
    '/disclaimers': show3290c002c36ba085034bd4b72e1546a6,
}

/**
* @see \App\Http\Controllers\LegalDocumentController::review
* @see app/Http/Controllers/LegalDocumentController.php:33
* @route '/legal/review'
*/
export const review = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: review.url(options),
    method: 'get',
})

review.definition = {
    methods: ["get","head"],
    url: '/legal/review',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LegalDocumentController::review
* @see app/Http/Controllers/LegalDocumentController.php:33
* @route '/legal/review'
*/
review.url = (options?: RouteQueryOptions) => {
    return review.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LegalDocumentController::review
* @see app/Http/Controllers/LegalDocumentController.php:33
* @route '/legal/review'
*/
review.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: review.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::review
* @see app/Http/Controllers/LegalDocumentController.php:33
* @route '/legal/review'
*/
review.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: review.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::review
* @see app/Http/Controllers/LegalDocumentController.php:33
* @route '/legal/review'
*/
const reviewForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: review.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::review
* @see app/Http/Controllers/LegalDocumentController.php:33
* @route '/legal/review'
*/
reviewForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: review.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::review
* @see app/Http/Controllers/LegalDocumentController.php:33
* @route '/legal/review'
*/
reviewForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: review.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

review.form = reviewForm

/**
* @see \App\Http\Controllers\LegalDocumentController::accept
* @see app/Http/Controllers/LegalDocumentController.php:42
* @route '/legal/review'
*/
export const accept = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(options),
    method: 'post',
})

accept.definition = {
    methods: ["post"],
    url: '/legal/review',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LegalDocumentController::accept
* @see app/Http/Controllers/LegalDocumentController.php:42
* @route '/legal/review'
*/
accept.url = (options?: RouteQueryOptions) => {
    return accept.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LegalDocumentController::accept
* @see app/Http/Controllers/LegalDocumentController.php:42
* @route '/legal/review'
*/
accept.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::accept
* @see app/Http/Controllers/LegalDocumentController.php:42
* @route '/legal/review'
*/
const acceptForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::accept
* @see app/Http/Controllers/LegalDocumentController.php:42
* @route '/legal/review'
*/
acceptForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(options),
    method: 'post',
})

accept.form = acceptForm

const LegalDocumentController = { show, review, accept }

export default LegalDocumentController