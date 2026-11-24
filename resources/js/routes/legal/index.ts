import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\LegalDocumentController::terms
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
export const terms = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: terms.url(options),
    method: 'get',
})

terms.definition = {
    methods: ["get","head"],
    url: '/terms',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LegalDocumentController::terms
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
terms.url = (options?: RouteQueryOptions) => {
    return terms.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LegalDocumentController::terms
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
terms.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: terms.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::terms
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
terms.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: terms.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::terms
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
const termsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: terms.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::terms
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
termsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: terms.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::terms
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/terms'
*/
termsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: terms.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

terms.form = termsForm

/**
* @see \App\Http\Controllers\LegalDocumentController::privacy
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
export const privacy = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: privacy.url(options),
    method: 'get',
})

privacy.definition = {
    methods: ["get","head"],
    url: '/privacy',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LegalDocumentController::privacy
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
privacy.url = (options?: RouteQueryOptions) => {
    return privacy.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LegalDocumentController::privacy
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
privacy.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: privacy.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::privacy
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
privacy.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: privacy.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::privacy
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
const privacyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: privacy.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::privacy
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
privacyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: privacy.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::privacy
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/privacy'
*/
privacyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: privacy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

privacy.form = privacyForm

/**
* @see \App\Http\Controllers\LegalDocumentController::disclaimers
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
export const disclaimers = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: disclaimers.url(options),
    method: 'get',
})

disclaimers.definition = {
    methods: ["get","head"],
    url: '/disclaimers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LegalDocumentController::disclaimers
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
disclaimers.url = (options?: RouteQueryOptions) => {
    return disclaimers.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LegalDocumentController::disclaimers
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
disclaimers.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: disclaimers.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::disclaimers
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
disclaimers.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: disclaimers.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::disclaimers
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
const disclaimersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: disclaimers.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::disclaimers
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
disclaimersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: disclaimers.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LegalDocumentController::disclaimers
* @see app/Http/Controllers/LegalDocumentController.php:23
* @route '/disclaimers'
*/
disclaimersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: disclaimers.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

disclaimers.form = disclaimersForm

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

const legal = {
    terms: Object.assign(terms, terms),
    privacy: Object.assign(privacy, privacy),
    disclaimers: Object.assign(disclaimers, disclaimers),
    review: Object.assign(review, review),
    accept: Object.assign(accept, accept),
}

export default legal