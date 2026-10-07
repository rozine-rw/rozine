import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EmailVerificationCodeController::store
* @see app/Http/Controllers/EmailVerificationCodeController.php:28
* @route '/email/verify-code'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/email/verify-code',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmailVerificationCodeController::store
* @see app/Http/Controllers/EmailVerificationCodeController.php:28
* @route '/email/verify-code'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmailVerificationCodeController::store
* @see app/Http/Controllers/EmailVerificationCodeController.php:28
* @route '/email/verify-code'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmailVerificationCodeController::store
* @see app/Http/Controllers/EmailVerificationCodeController.php:28
* @route '/email/verify-code'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmailVerificationCodeController::store
* @see app/Http/Controllers/EmailVerificationCodeController.php:28
* @route '/email/verify-code'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const EmailVerificationCodeController = { store }

export default EmailVerificationCodeController