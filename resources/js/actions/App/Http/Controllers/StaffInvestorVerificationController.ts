import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/api/v1/staff/investor-verifications'
*/
const index6da9252e107e417771d7d3ccf4090e1e = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index6da9252e107e417771d7d3ccf4090e1e.url(options),
    method: 'get',
})

index6da9252e107e417771d7d3ccf4090e1e.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/investor-verifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/api/v1/staff/investor-verifications'
*/
index6da9252e107e417771d7d3ccf4090e1e.url = (options?: RouteQueryOptions) => {
    return index6da9252e107e417771d7d3ccf4090e1e.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/api/v1/staff/investor-verifications'
*/
index6da9252e107e417771d7d3ccf4090e1e.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index6da9252e107e417771d7d3ccf4090e1e.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/api/v1/staff/investor-verifications'
*/
index6da9252e107e417771d7d3ccf4090e1e.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index6da9252e107e417771d7d3ccf4090e1e.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/api/v1/staff/investor-verifications'
*/
const index6da9252e107e417771d7d3ccf4090e1eForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index6da9252e107e417771d7d3ccf4090e1e.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/api/v1/staff/investor-verifications'
*/
index6da9252e107e417771d7d3ccf4090e1eForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index6da9252e107e417771d7d3ccf4090e1e.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/api/v1/staff/investor-verifications'
*/
index6da9252e107e417771d7d3ccf4090e1eForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index6da9252e107e417771d7d3ccf4090e1e.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index6da9252e107e417771d7d3ccf4090e1e.form = index6da9252e107e417771d7d3ccf4090e1eForm
/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/admin/investor-verifications'
*/
const index33c910a80390b490476805c40b40ed33 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index33c910a80390b490476805c40b40ed33.url(options),
    method: 'get',
})

index33c910a80390b490476805c40b40ed33.definition = {
    methods: ["get","head"],
    url: '/admin/investor-verifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/admin/investor-verifications'
*/
index33c910a80390b490476805c40b40ed33.url = (options?: RouteQueryOptions) => {
    return index33c910a80390b490476805c40b40ed33.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/admin/investor-verifications'
*/
index33c910a80390b490476805c40b40ed33.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index33c910a80390b490476805c40b40ed33.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/admin/investor-verifications'
*/
index33c910a80390b490476805c40b40ed33.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index33c910a80390b490476805c40b40ed33.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/admin/investor-verifications'
*/
const index33c910a80390b490476805c40b40ed33Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index33c910a80390b490476805c40b40ed33.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/admin/investor-verifications'
*/
index33c910a80390b490476805c40b40ed33Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index33c910a80390b490476805c40b40ed33.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::index
* @see app/Http/Controllers/StaffInvestorVerificationController.php:42
* @route '/admin/investor-verifications'
*/
index33c910a80390b490476805c40b40ed33Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index33c910a80390b490476805c40b40ed33.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index33c910a80390b490476805c40b40ed33.form = index33c910a80390b490476805c40b40ed33Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffInvestorVerificationController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/investor-verifications': index6da9252e107e417771d7d3ccf4090e1e,
    '/admin/investor-verifications': index33c910a80390b490476805c40b40ed33,
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/api/v1/staff/investor-verifications/{verification}/documents/{document}'
*/
const document486827cd6497c0c112d8f9f7a1518027 = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document486827cd6497c0c112d8f9f7a1518027.url(args, options),
    method: 'get',
})

document486827cd6497c0c112d8f9f7a1518027.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/investor-verifications/{verification}/documents/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/api/v1/staff/investor-verifications/{verification}/documents/{document}'
*/
document486827cd6497c0c112d8f9f7a1518027.url = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions) => {
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

    return document486827cd6497c0c112d8f9f7a1518027.definition.url
            .replace('{verification}', parsedArgs.verification.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/api/v1/staff/investor-verifications/{verification}/documents/{document}'
*/
document486827cd6497c0c112d8f9f7a1518027.get = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document486827cd6497c0c112d8f9f7a1518027.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/api/v1/staff/investor-verifications/{verification}/documents/{document}'
*/
document486827cd6497c0c112d8f9f7a1518027.head = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: document486827cd6497c0c112d8f9f7a1518027.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/api/v1/staff/investor-verifications/{verification}/documents/{document}'
*/
const document486827cd6497c0c112d8f9f7a1518027Form = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document486827cd6497c0c112d8f9f7a1518027.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/api/v1/staff/investor-verifications/{verification}/documents/{document}'
*/
document486827cd6497c0c112d8f9f7a1518027Form.get = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document486827cd6497c0c112d8f9f7a1518027.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/api/v1/staff/investor-verifications/{verification}/documents/{document}'
*/
document486827cd6497c0c112d8f9f7a1518027Form.head = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document486827cd6497c0c112d8f9f7a1518027.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

document486827cd6497c0c112d8f9f7a1518027.form = document486827cd6497c0c112d8f9f7a1518027Form
/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
const document5b1620af22ca0eb8fa9e66da44f4553b = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document5b1620af22ca0eb8fa9e66da44f4553b.url(args, options),
    method: 'get',
})

document5b1620af22ca0eb8fa9e66da44f4553b.definition = {
    methods: ["get","head"],
    url: '/admin/investor-verifications/{verification}/documents/{document}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
document5b1620af22ca0eb8fa9e66da44f4553b.url = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions) => {
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

    return document5b1620af22ca0eb8fa9e66da44f4553b.definition.url
            .replace('{verification}', parsedArgs.verification.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
document5b1620af22ca0eb8fa9e66da44f4553b.get = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: document5b1620af22ca0eb8fa9e66da44f4553b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
document5b1620af22ca0eb8fa9e66da44f4553b.head = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: document5b1620af22ca0eb8fa9e66da44f4553b.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
const document5b1620af22ca0eb8fa9e66da44f4553bForm = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document5b1620af22ca0eb8fa9e66da44f4553b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
document5b1620af22ca0eb8fa9e66da44f4553bForm.get = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document5b1620af22ca0eb8fa9e66da44f4553b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::document
* @see app/Http/Controllers/StaffInvestorVerificationController.php:57
* @route '/admin/investor-verifications/{verification}/documents/{document}'
*/
document5b1620af22ca0eb8fa9e66da44f4553bForm.head = (args: { verification: string | number, document: string | number } | [verification: string | number, document: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: document5b1620af22ca0eb8fa9e66da44f4553b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

document5b1620af22ca0eb8fa9e66da44f4553b.form = document5b1620af22ca0eb8fa9e66da44f4553bForm

/**
* Multiple routes resolve to \App\Http\Controllers\StaffInvestorVerificationController::document, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `document['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const document = {
    '/api/v1/staff/investor-verifications/{verification}/documents/{document}': document486827cd6497c0c112d8f9f7a1518027,
    '/admin/investor-verifications/{verification}/documents/{document}': document5b1620af22ca0eb8fa9e66da44f4553b,
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/api/v1/staff/investor-verifications/{verification}/approve'
*/
const approvedb0215c93d1d8df745f34c28065ca96b = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approvedb0215c93d1d8df745f34c28065ca96b.url(args, options),
    method: 'post',
})

approvedb0215c93d1d8df745f34c28065ca96b.definition = {
    methods: ["post"],
    url: '/api/v1/staff/investor-verifications/{verification}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/api/v1/staff/investor-verifications/{verification}/approve'
*/
approvedb0215c93d1d8df745f34c28065ca96b.url = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return approvedb0215c93d1d8df745f34c28065ca96b.definition.url
            .replace('{verification}', parsedArgs.verification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/api/v1/staff/investor-verifications/{verification}/approve'
*/
approvedb0215c93d1d8df745f34c28065ca96b.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approvedb0215c93d1d8df745f34c28065ca96b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/api/v1/staff/investor-verifications/{verification}/approve'
*/
const approvedb0215c93d1d8df745f34c28065ca96bForm = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approvedb0215c93d1d8df745f34c28065ca96b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/api/v1/staff/investor-verifications/{verification}/approve'
*/
approvedb0215c93d1d8df745f34c28065ca96bForm.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approvedb0215c93d1d8df745f34c28065ca96b.url(args, options),
    method: 'post',
})

approvedb0215c93d1d8df745f34c28065ca96b.form = approvedb0215c93d1d8df745f34c28065ca96bForm
/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/admin/investor-verifications/{verification}/approve'
*/
const approve18d7c8122dc1c3780268f2ca85d82c62 = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve18d7c8122dc1c3780268f2ca85d82c62.url(args, options),
    method: 'post',
})

approve18d7c8122dc1c3780268f2ca85d82c62.definition = {
    methods: ["post"],
    url: '/admin/investor-verifications/{verification}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/admin/investor-verifications/{verification}/approve'
*/
approve18d7c8122dc1c3780268f2ca85d82c62.url = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return approve18d7c8122dc1c3780268f2ca85d82c62.definition.url
            .replace('{verification}', parsedArgs.verification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/admin/investor-verifications/{verification}/approve'
*/
approve18d7c8122dc1c3780268f2ca85d82c62.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve18d7c8122dc1c3780268f2ca85d82c62.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/admin/investor-verifications/{verification}/approve'
*/
const approve18d7c8122dc1c3780268f2ca85d82c62Form = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve18d7c8122dc1c3780268f2ca85d82c62.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::approve
* @see app/Http/Controllers/StaffInvestorVerificationController.php:75
* @route '/admin/investor-verifications/{verification}/approve'
*/
approve18d7c8122dc1c3780268f2ca85d82c62Form.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve18d7c8122dc1c3780268f2ca85d82c62.url(args, options),
    method: 'post',
})

approve18d7c8122dc1c3780268f2ca85d82c62.form = approve18d7c8122dc1c3780268f2ca85d82c62Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffInvestorVerificationController::approve, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `approve['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const approve = {
    '/api/v1/staff/investor-verifications/{verification}/approve': approvedb0215c93d1d8df745f34c28065ca96b,
    '/admin/investor-verifications/{verification}/approve': approve18d7c8122dc1c3780268f2ca85d82c62,
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/api/v1/staff/investor-verifications/{verification}/reject'
*/
const rejectc3a803ac2cfdc5aa41232b54f86086bd = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rejectc3a803ac2cfdc5aa41232b54f86086bd.url(args, options),
    method: 'post',
})

rejectc3a803ac2cfdc5aa41232b54f86086bd.definition = {
    methods: ["post"],
    url: '/api/v1/staff/investor-verifications/{verification}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/api/v1/staff/investor-verifications/{verification}/reject'
*/
rejectc3a803ac2cfdc5aa41232b54f86086bd.url = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return rejectc3a803ac2cfdc5aa41232b54f86086bd.definition.url
            .replace('{verification}', parsedArgs.verification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/api/v1/staff/investor-verifications/{verification}/reject'
*/
rejectc3a803ac2cfdc5aa41232b54f86086bd.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rejectc3a803ac2cfdc5aa41232b54f86086bd.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/api/v1/staff/investor-verifications/{verification}/reject'
*/
const rejectc3a803ac2cfdc5aa41232b54f86086bdForm = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: rejectc3a803ac2cfdc5aa41232b54f86086bd.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/api/v1/staff/investor-verifications/{verification}/reject'
*/
rejectc3a803ac2cfdc5aa41232b54f86086bdForm.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: rejectc3a803ac2cfdc5aa41232b54f86086bd.url(args, options),
    method: 'post',
})

rejectc3a803ac2cfdc5aa41232b54f86086bd.form = rejectc3a803ac2cfdc5aa41232b54f86086bdForm
/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/admin/investor-verifications/{verification}/reject'
*/
const rejectcb994546b0d488a9435ca2b576358f93 = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rejectcb994546b0d488a9435ca2b576358f93.url(args, options),
    method: 'post',
})

rejectcb994546b0d488a9435ca2b576358f93.definition = {
    methods: ["post"],
    url: '/admin/investor-verifications/{verification}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/admin/investor-verifications/{verification}/reject'
*/
rejectcb994546b0d488a9435ca2b576358f93.url = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return rejectcb994546b0d488a9435ca2b576358f93.definition.url
            .replace('{verification}', parsedArgs.verification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/admin/investor-verifications/{verification}/reject'
*/
rejectcb994546b0d488a9435ca2b576358f93.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rejectcb994546b0d488a9435ca2b576358f93.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/admin/investor-verifications/{verification}/reject'
*/
const rejectcb994546b0d488a9435ca2b576358f93Form = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: rejectcb994546b0d488a9435ca2b576358f93.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffInvestorVerificationController::reject
* @see app/Http/Controllers/StaffInvestorVerificationController.php:80
* @route '/admin/investor-verifications/{verification}/reject'
*/
rejectcb994546b0d488a9435ca2b576358f93Form.post = (args: { verification: string | number } | [verification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: rejectcb994546b0d488a9435ca2b576358f93.url(args, options),
    method: 'post',
})

rejectcb994546b0d488a9435ca2b576358f93.form = rejectcb994546b0d488a9435ca2b576358f93Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffInvestorVerificationController::reject, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `reject['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const reject = {
    '/api/v1/staff/investor-verifications/{verification}/reject': rejectc3a803ac2cfdc5aa41232b54f86086bd,
    '/admin/investor-verifications/{verification}/reject': rejectcb994546b0d488a9435ca2b576358f93,
}

const StaffInvestorVerificationController = { index, document, approve, reject }

export default StaffInvestorVerificationController