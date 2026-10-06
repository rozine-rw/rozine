import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:39
* @route '/admin/investor-verifications'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/investor-verifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:39
* @route '/admin/investor-verifications'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:39
* @route '/admin/investor-verifications'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:39
* @route '/admin/investor-verifications'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:39
* @route '/admin/investor-verifications'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:39
* @route '/admin/investor-verifications'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:39
* @route '/admin/investor-verifications'
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
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:51
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
export const document = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document.url(args, options),
    method: 'get',
})

document.definition = {
    methods: ["get","head"],
    url: '/admin/investor-verifications/{verification}/documents/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:51
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
document.url = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            verification: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        verification: args.verification,
        document: args.document,
    }

    return document.definition.url
            .replace('{verification}', parsedArgs.verification.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:51
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
document.get = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:51
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
document.head = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: document.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:51
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
const documentForm = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:51
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
documentForm.get = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:51
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
documentForm.head = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

document.form = documentForm

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:63
* @route '/admin/investor-verifications/{verification}/approve'
*/
export const approve = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/admin/investor-verifications/{verification}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:63
* @route '/admin/investor-verifications/{verification}/approve'
*/
approve.url = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { verification: args }
    }

    if (Array.isArray(args)) {
        args = {
            verification: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        verification: args.verification,
    }

    return approve.definition.url
            .replace('{verification}', parsedArgs.verification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:63
* @route '/admin/investor-verifications/{verification}/approve'
*/
approve.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:63
* @route '/admin/investor-verifications/{verification}/approve'
*/
const approveForm = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:63
* @route '/admin/investor-verifications/{verification}/approve'
*/
approveForm.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:68
* @route '/admin/investor-verifications/{verification}/reject'
*/
export const reject = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/admin/investor-verifications/{verification}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:68
* @route '/admin/investor-verifications/{verification}/reject'
*/
reject.url = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { verification: args }
    }

    if (Array.isArray(args)) {
        args = {
            verification: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        verification: args.verification,
    }

    return reject.definition.url
            .replace('{verification}', parsedArgs.verification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:68
* @route '/admin/investor-verifications/{verification}/reject'
*/
reject.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:68
* @route '/admin/investor-verifications/{verification}/reject'
*/
const rejectForm = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:68
* @route '/admin/investor-verifications/{verification}/reject'
*/
rejectForm.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

const StaffInvestorVerificationController = { index, document, approve, reject }

export default StaffInvestorVerificationController