import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute'
*/
const dispute6eb67dbc933da9980f2982a58efd7fa4 = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dispute6eb67dbc933da9980f2982a58efd7fa4.url(args, options),
    method: 'post',
})

dispute6eb67dbc933da9980f2982a58efd7fa4.definition = {
    methods: ["post"],
    url: '/api/v1/business/{business}/audit-reports/{report}/dispute',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute'
*/
dispute6eb67dbc933da9980f2982a58efd7fa4.url = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        report: args.report,
    }

    return dispute6eb67dbc933da9980f2982a58efd7fa4.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute'
*/
dispute6eb67dbc933da9980f2982a58efd7fa4.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dispute6eb67dbc933da9980f2982a58efd7fa4.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute'
*/
const dispute6eb67dbc933da9980f2982a58efd7fa4Form = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: dispute6eb67dbc933da9980f2982a58efd7fa4.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute'
*/
dispute6eb67dbc933da9980f2982a58efd7fa4Form.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: dispute6eb67dbc933da9980f2982a58efd7fa4.url(args, options),
    method: 'post',
})

dispute6eb67dbc933da9980f2982a58efd7fa4.form = dispute6eb67dbc933da9980f2982a58efd7fa4Form
/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
const disputee2aa2b2eab3fb77ebd34d98ea7ceb8df = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: disputee2aa2b2eab3fb77ebd34d98ea7ceb8df.url(args, options),
    method: 'post',
})

disputee2aa2b2eab3fb77ebd34d98ea7ceb8df.definition = {
    methods: ["post"],
    url: '/business/{business}/audit-reports/{report}/dispute',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
disputee2aa2b2eab3fb77ebd34d98ea7ceb8df.url = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        report: args.report,
    }

    return disputee2aa2b2eab3fb77ebd34d98ea7ceb8df.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
disputee2aa2b2eab3fb77ebd34d98ea7ceb8df.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: disputee2aa2b2eab3fb77ebd34d98ea7ceb8df.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
const disputee2aa2b2eab3fb77ebd34d98ea7ceb8dfForm = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: disputee2aa2b2eab3fb77ebd34d98ea7ceb8df.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::dispute
* @see app/Http/Controllers/AuditDisputeController.php:21
* @route '/business/{business}/audit-reports/{report}/dispute'
*/
disputee2aa2b2eab3fb77ebd34d98ea7ceb8dfForm.post = (args: { business: string | number, report: string | number } | [business: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: disputee2aa2b2eab3fb77ebd34d98ea7ceb8df.url(args, options),
    method: 'post',
})

disputee2aa2b2eab3fb77ebd34d98ea7ceb8df.form = disputee2aa2b2eab3fb77ebd34d98ea7ceb8dfForm

/**
* Multiple routes resolve to \App\Http\Controllers\AuditDisputeController::dispute, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `dispute['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const dispute = {
    '/api/v1/business/{business}/audit-reports/{report}/dispute': dispute6eb67dbc933da9980f2982a58efd7fa4,
    '/business/{business}/audit-reports/{report}/dispute': disputee2aa2b2eab3fb77ebd34d98ea7ceb8df,
}

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
const proof8e908f36f27e9f089f452ead7e80f471 = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof8e908f36f27e9f089f452ead7e80f471.url(args, options),
    method: 'get',
})

proof8e908f36f27e9f089f452ead7e80f471.definition = {
    methods: ["get","head"],
    url: '/api/v1/business/{business}/audit-reports/{report}/dispute/proofs/{proof}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proof8e908f36f27e9f089f452ead7e80f471.url = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            report: args[1],
            proof: args[2],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        report: args.report,
        proof: args.proof,
    }

    return proof8e908f36f27e9f089f452ead7e80f471.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace('{proof}', parsedArgs.proof.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proof8e908f36f27e9f089f452ead7e80f471.get = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof8e908f36f27e9f089f452ead7e80f471.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proof8e908f36f27e9f089f452ead7e80f471.head = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: proof8e908f36f27e9f089f452ead7e80f471.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
const proof8e908f36f27e9f089f452ead7e80f471Form = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof8e908f36f27e9f089f452ead7e80f471.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proof8e908f36f27e9f089f452ead7e80f471Form.get = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof8e908f36f27e9f089f452ead7e80f471.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proof8e908f36f27e9f089f452ead7e80f471Form.head = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof8e908f36f27e9f089f452ead7e80f471.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

proof8e908f36f27e9f089f452ead7e80f471.form = proof8e908f36f27e9f089f452ead7e80f471Form
/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
const proof34a4ebfa9d8433ee810648e7d177a7b3 = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof34a4ebfa9d8433ee810648e7d177a7b3.url(args, options),
    method: 'get',
})

proof34a4ebfa9d8433ee810648e7d177a7b3.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof34a4ebfa9d8433ee810648e7d177a7b3.url = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
            proof: args[2],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
        proof: args.proof,
    }

    return proof34a4ebfa9d8433ee810648e7d177a7b3.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace('{proof}', parsedArgs.proof.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof34a4ebfa9d8433ee810648e7d177a7b3.get = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof34a4ebfa9d8433ee810648e7d177a7b3.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof34a4ebfa9d8433ee810648e7d177a7b3.head = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: proof34a4ebfa9d8433ee810648e7d177a7b3.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
const proof34a4ebfa9d8433ee810648e7d177a7b3Form = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof34a4ebfa9d8433ee810648e7d177a7b3.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof34a4ebfa9d8433ee810648e7d177a7b3Form.get = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof34a4ebfa9d8433ee810648e7d177a7b3.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof34a4ebfa9d8433ee810648e7d177a7b3Form.head = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof34a4ebfa9d8433ee810648e7d177a7b3.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

proof34a4ebfa9d8433ee810648e7d177a7b3.form = proof34a4ebfa9d8433ee810648e7d177a7b3Form
/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
const proof3244e2ed4a958b8ca41b627bd070ef93 = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof3244e2ed4a958b8ca41b627bd070ef93.url(args, options),
    method: 'get',
})

proof3244e2ed4a958b8ca41b627bd070ef93.definition = {
    methods: ["get","head"],
    url: '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof3244e2ed4a958b8ca41b627bd070ef93.url = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            report: args[0],
            proof: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        report: args.report,
        proof: args.proof,
    }

    return proof3244e2ed4a958b8ca41b627bd070ef93.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace('{proof}', parsedArgs.proof.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof3244e2ed4a958b8ca41b627bd070ef93.get = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof3244e2ed4a958b8ca41b627bd070ef93.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof3244e2ed4a958b8ca41b627bd070ef93.head = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: proof3244e2ed4a958b8ca41b627bd070ef93.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
const proof3244e2ed4a958b8ca41b627bd070ef93Form = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof3244e2ed4a958b8ca41b627bd070ef93.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof3244e2ed4a958b8ca41b627bd070ef93Form.get = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof3244e2ed4a958b8ca41b627bd070ef93.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof3244e2ed4a958b8ca41b627bd070ef93Form.head = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof3244e2ed4a958b8ca41b627bd070ef93.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

proof3244e2ed4a958b8ca41b627bd070ef93.form = proof3244e2ed4a958b8ca41b627bd070ef93Form
/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
const proofa584c6815421c73fd627768806593ef7 = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proofa584c6815421c73fd627768806593ef7.url(args, options),
    method: 'get',
})

proofa584c6815421c73fd627768806593ef7.definition = {
    methods: ["get","head"],
    url: '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proofa584c6815421c73fd627768806593ef7.url = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            business: args[0],
            report: args[1],
            proof: args[2],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        business: args.business,
        report: args.report,
        proof: args.proof,
    }

    return proofa584c6815421c73fd627768806593ef7.definition.url
            .replace('{business}', parsedArgs.business.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace('{proof}', parsedArgs.proof.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proofa584c6815421c73fd627768806593ef7.get = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proofa584c6815421c73fd627768806593ef7.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proofa584c6815421c73fd627768806593ef7.head = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: proofa584c6815421c73fd627768806593ef7.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
const proofa584c6815421c73fd627768806593ef7Form = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proofa584c6815421c73fd627768806593ef7.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proofa584c6815421c73fd627768806593ef7Form.get = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proofa584c6815421c73fd627768806593ef7.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}'
*/
proofa584c6815421c73fd627768806593ef7Form.head = (args: { business: string | number, report: string | number, proof: string | number } | [business: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proofa584c6815421c73fd627768806593ef7.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

proofa584c6815421c73fd627768806593ef7.form = proofa584c6815421c73fd627768806593ef7Form
/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/auditor/reports/{report}/dispute/proofs/{proof}'
*/
const proof324a5572d6f9666c1168d03d8fc26bb3 = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof324a5572d6f9666c1168d03d8fc26bb3.url(args, options),
    method: 'get',
})

proof324a5572d6f9666c1168d03d8fc26bb3.definition = {
    methods: ["get","head"],
    url: '/auditor/reports/{report}/dispute/proofs/{proof}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof324a5572d6f9666c1168d03d8fc26bb3.url = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            report: args[0],
            proof: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        report: args.report,
        proof: args.proof,
    }

    return proof324a5572d6f9666c1168d03d8fc26bb3.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace('{proof}', parsedArgs.proof.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof324a5572d6f9666c1168d03d8fc26bb3.get = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof324a5572d6f9666c1168d03d8fc26bb3.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof324a5572d6f9666c1168d03d8fc26bb3.head = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: proof324a5572d6f9666c1168d03d8fc26bb3.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/auditor/reports/{report}/dispute/proofs/{proof}'
*/
const proof324a5572d6f9666c1168d03d8fc26bb3Form = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof324a5572d6f9666c1168d03d8fc26bb3.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof324a5572d6f9666c1168d03d8fc26bb3Form.get = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof324a5572d6f9666c1168d03d8fc26bb3.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/auditor/reports/{report}/dispute/proofs/{proof}'
*/
proof324a5572d6f9666c1168d03d8fc26bb3Form.head = (args: { report: string | number, proof: string | number } | [report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof324a5572d6f9666c1168d03d8fc26bb3.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

proof324a5572d6f9666c1168d03d8fc26bb3.form = proof324a5572d6f9666c1168d03d8fc26bb3Form
/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
const proof70bb3edb16aaf50f38c58bdc9ade36c1 = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof70bb3edb16aaf50f38c58bdc9ade36c1.url(args, options),
    method: 'get',
})

proof70bb3edb16aaf50f38c58bdc9ade36c1.definition = {
    methods: ["get","head"],
    url: '/admin/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof70bb3edb16aaf50f38c58bdc9ade36c1.url = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
            proof: args[2],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
        proof: args.proof,
    }

    return proof70bb3edb16aaf50f38c58bdc9ade36c1.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace('{proof}', parsedArgs.proof.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof70bb3edb16aaf50f38c58bdc9ade36c1.get = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: proof70bb3edb16aaf50f38c58bdc9ade36c1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof70bb3edb16aaf50f38c58bdc9ade36c1.head = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: proof70bb3edb16aaf50f38c58bdc9ade36c1.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
const proof70bb3edb16aaf50f38c58bdc9ade36c1Form = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof70bb3edb16aaf50f38c58bdc9ade36c1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof70bb3edb16aaf50f38c58bdc9ade36c1Form.get = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof70bb3edb16aaf50f38c58bdc9ade36c1.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::proof
* @see app/Http/Controllers/AuditDisputeController.php:61
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}'
*/
proof70bb3edb16aaf50f38c58bdc9ade36c1Form.head = (args: { assignment: string | number, report: string | number, proof: string | number } | [assignment: string | number, report: string | number, proof: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: proof70bb3edb16aaf50f38c58bdc9ade36c1.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

proof70bb3edb16aaf50f38c58bdc9ade36c1.form = proof70bb3edb16aaf50f38c58bdc9ade36c1Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditDisputeController::proof, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `proof['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const proof = {
    '/api/v1/business/{business}/audit-reports/{report}/dispute/proofs/{proof}': proof8e908f36f27e9f089f452ead7e80f471,
    '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}': proof34a4ebfa9d8433ee810648e7d177a7b3,
    '/api/v1/auditor/reports/{report}/dispute/proofs/{proof}': proof3244e2ed4a958b8ca41b627bd070ef93,
    '/business/{business}/audit-reports/{report}/dispute/proofs/{proof}': proofa584c6815421c73fd627768806593ef7,
    '/auditor/reports/{report}/dispute/proofs/{proof}': proof324a5572d6f9666c1168d03d8fc26bb3,
    '/admin/audit-assignments/{assignment}/reports/{report}/dispute/proofs/{proof}': proof70bb3edb16aaf50f38c58bdc9ade36c1,
}

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/api/v1/staff/audit-assignments/dispute-operations/{request_id}'
*/
const operationefdd69d2b09fa726af1d4f1ffc144f7c = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationefdd69d2b09fa726af1d4f1ffc144f7c.url(args, options),
    method: 'get',
})

operationefdd69d2b09fa726af1d4f1ffc144f7c.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/audit-assignments/dispute-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/api/v1/staff/audit-assignments/dispute-operations/{request_id}'
*/
operationefdd69d2b09fa726af1d4f1ffc144f7c.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { request_id: args }
    }

    if (Array.isArray(args)) {
        args = {
            request_id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        request_id: args.request_id,
    }

    return operationefdd69d2b09fa726af1d4f1ffc144f7c.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/api/v1/staff/audit-assignments/dispute-operations/{request_id}'
*/
operationefdd69d2b09fa726af1d4f1ffc144f7c.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operationefdd69d2b09fa726af1d4f1ffc144f7c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/api/v1/staff/audit-assignments/dispute-operations/{request_id}'
*/
operationefdd69d2b09fa726af1d4f1ffc144f7c.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operationefdd69d2b09fa726af1d4f1ffc144f7c.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/api/v1/staff/audit-assignments/dispute-operations/{request_id}'
*/
const operationefdd69d2b09fa726af1d4f1ffc144f7cForm = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationefdd69d2b09fa726af1d4f1ffc144f7c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/api/v1/staff/audit-assignments/dispute-operations/{request_id}'
*/
operationefdd69d2b09fa726af1d4f1ffc144f7cForm.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationefdd69d2b09fa726af1d4f1ffc144f7c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/api/v1/staff/audit-assignments/dispute-operations/{request_id}'
*/
operationefdd69d2b09fa726af1d4f1ffc144f7cForm.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operationefdd69d2b09fa726af1d4f1ffc144f7c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operationefdd69d2b09fa726af1d4f1ffc144f7c.form = operationefdd69d2b09fa726af1d4f1ffc144f7cForm
/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/admin/audit-assignments/dispute-operations/{request_id}'
*/
const operation831a75a34db6c93ef014bc855d1390e8 = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation831a75a34db6c93ef014bc855d1390e8.url(args, options),
    method: 'get',
})

operation831a75a34db6c93ef014bc855d1390e8.definition = {
    methods: ["get","head"],
    url: '/admin/audit-assignments/dispute-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/admin/audit-assignments/dispute-operations/{request_id}'
*/
operation831a75a34db6c93ef014bc855d1390e8.url = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { request_id: args }
    }

    if (Array.isArray(args)) {
        args = {
            request_id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        request_id: args.request_id,
    }

    return operation831a75a34db6c93ef014bc855d1390e8.definition.url
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/admin/audit-assignments/dispute-operations/{request_id}'
*/
operation831a75a34db6c93ef014bc855d1390e8.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation831a75a34db6c93ef014bc855d1390e8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/admin/audit-assignments/dispute-operations/{request_id}'
*/
operation831a75a34db6c93ef014bc855d1390e8.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation831a75a34db6c93ef014bc855d1390e8.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/admin/audit-assignments/dispute-operations/{request_id}'
*/
const operation831a75a34db6c93ef014bc855d1390e8Form = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation831a75a34db6c93ef014bc855d1390e8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/admin/audit-assignments/dispute-operations/{request_id}'
*/
operation831a75a34db6c93ef014bc855d1390e8Form.get = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation831a75a34db6c93ef014bc855d1390e8.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::operation
* @see app/Http/Controllers/AuditDisputeController.php:52
* @route '/admin/audit-assignments/dispute-operations/{request_id}'
*/
operation831a75a34db6c93ef014bc855d1390e8Form.head = (args: { request_id: string | number } | [request_id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation831a75a34db6c93ef014bc855d1390e8.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation831a75a34db6c93ef014bc855d1390e8.form = operation831a75a34db6c93ef014bc855d1390e8Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditDisputeController::operation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `operation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const operation = {
    '/api/v1/staff/audit-assignments/dispute-operations/{request_id}': operationefdd69d2b09fa726af1d4f1ffc144f7c,
    '/admin/audit-assignments/dispute-operations/{request_id}': operation831a75a34db6c93ef014bc855d1390e8,
}

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
const showe3735e7bd0fe4bea2f35ffe39dd9f2e5 = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showe3735e7bd0fe4bea2f35ffe39dd9f2e5.url(args, options),
    method: 'get',
})

showe3735e7bd0fe4bea2f35ffe39dd9f2e5.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showe3735e7bd0fe4bea2f35ffe39dd9f2e5.url = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
    }

    return showe3735e7bd0fe4bea2f35ffe39dd9f2e5.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showe3735e7bd0fe4bea2f35ffe39dd9f2e5.get = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showe3735e7bd0fe4bea2f35ffe39dd9f2e5.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showe3735e7bd0fe4bea2f35ffe39dd9f2e5.head = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showe3735e7bd0fe4bea2f35ffe39dd9f2e5.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
const showe3735e7bd0fe4bea2f35ffe39dd9f2e5Form = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showe3735e7bd0fe4bea2f35ffe39dd9f2e5.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showe3735e7bd0fe4bea2f35ffe39dd9f2e5Form.get = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showe3735e7bd0fe4bea2f35ffe39dd9f2e5.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showe3735e7bd0fe4bea2f35ffe39dd9f2e5Form.head = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showe3735e7bd0fe4bea2f35ffe39dd9f2e5.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showe3735e7bd0fe4bea2f35ffe39dd9f2e5.form = showe3735e7bd0fe4bea2f35ffe39dd9f2e5Form
/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute'
*/
const showf50871715ebd6675bf16c9e3d5c7ba6e = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showf50871715ebd6675bf16c9e3d5c7ba6e.url(args, options),
    method: 'get',
})

showf50871715ebd6675bf16c9e3d5c7ba6e.definition = {
    methods: ["get","head"],
    url: '/admin/audit-assignments/{assignment}/reports/{report}/dispute',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showf50871715ebd6675bf16c9e3d5c7ba6e.url = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
    }

    return showf50871715ebd6675bf16c9e3d5c7ba6e.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showf50871715ebd6675bf16c9e3d5c7ba6e.get = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showf50871715ebd6675bf16c9e3d5c7ba6e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showf50871715ebd6675bf16c9e3d5c7ba6e.head = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showf50871715ebd6675bf16c9e3d5c7ba6e.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute'
*/
const showf50871715ebd6675bf16c9e3d5c7ba6eForm = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showf50871715ebd6675bf16c9e3d5c7ba6e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showf50871715ebd6675bf16c9e3d5c7ba6eForm.get = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showf50871715ebd6675bf16c9e3d5c7ba6e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::show
* @see app/Http/Controllers/AuditDisputeController.php:44
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute'
*/
showf50871715ebd6675bf16c9e3d5c7ba6eForm.head = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showf50871715ebd6675bf16c9e3d5c7ba6e.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showf50871715ebd6675bf16c9e3d5c7ba6e.form = showf50871715ebd6675bf16c9e3d5c7ba6eForm

/**
* Multiple routes resolve to \App\Http\Controllers\AuditDisputeController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute': showe3735e7bd0fe4bea2f35ffe39dd9f2e5,
    '/admin/audit-assignments/{assignment}/reports/{report}/dispute': showf50871715ebd6675bf16c9e3d5c7ba6e,
}

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
const resolve3d0b3994f6f295d610b17b657844f28b = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve3d0b3994f6f295d610b17b657844f28b.url(args, options),
    method: 'post',
})

resolve3d0b3994f6f295d610b17b657844f28b.definition = {
    methods: ["post"],
    url: '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
resolve3d0b3994f6f295d610b17b657844f28b.url = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
    }

    return resolve3d0b3994f6f295d610b17b657844f28b.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
resolve3d0b3994f6f295d610b17b657844f28b.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve3d0b3994f6f295d610b17b657844f28b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
const resolve3d0b3994f6f295d610b17b657844f28bForm = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve3d0b3994f6f295d610b17b657844f28b.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
resolve3d0b3994f6f295d610b17b657844f28bForm.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve3d0b3994f6f295d610b17b657844f28b.url(args, options),
    method: 'post',
})

resolve3d0b3994f6f295d610b17b657844f28b.form = resolve3d0b3994f6f295d610b17b657844f28bForm
/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
const resolve48bcd44258790a6368228bd2e4b0282d = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve48bcd44258790a6368228bd2e4b0282d.url(args, options),
    method: 'post',
})

resolve48bcd44258790a6368228bd2e4b0282d.definition = {
    methods: ["post"],
    url: '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
resolve48bcd44258790a6368228bd2e4b0282d.url = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
    }

    return resolve48bcd44258790a6368228bd2e4b0282d.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
resolve48bcd44258790a6368228bd2e4b0282d.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve48bcd44258790a6368228bd2e4b0282d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
const resolve48bcd44258790a6368228bd2e4b0282dForm = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve48bcd44258790a6368228bd2e4b0282d.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
resolve48bcd44258790a6368228bd2e4b0282dForm.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve48bcd44258790a6368228bd2e4b0282d.url(args, options),
    method: 'post',
})

resolve48bcd44258790a6368228bd2e4b0282d.form = resolve48bcd44258790a6368228bd2e4b0282dForm
/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
const resolve897af81fbe6beb3157cb61570e91b2e9 = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve897af81fbe6beb3157cb61570e91b2e9.url(args, options),
    method: 'post',
})

resolve897af81fbe6beb3157cb61570e91b2e9.definition = {
    methods: ["post"],
    url: '/admin/audit-assignments/{assignment}/reports/{report}/dispute/escalate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
resolve897af81fbe6beb3157cb61570e91b2e9.url = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
    }

    return resolve897af81fbe6beb3157cb61570e91b2e9.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
resolve897af81fbe6beb3157cb61570e91b2e9.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve897af81fbe6beb3157cb61570e91b2e9.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
const resolve897af81fbe6beb3157cb61570e91b2e9Form = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve897af81fbe6beb3157cb61570e91b2e9.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/escalate'
*/
resolve897af81fbe6beb3157cb61570e91b2e9Form.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve897af81fbe6beb3157cb61570e91b2e9.url(args, options),
    method: 'post',
})

resolve897af81fbe6beb3157cb61570e91b2e9.form = resolve897af81fbe6beb3157cb61570e91b2e9Form
/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
const resolve64cd554ea6844407237a75dd53d2a6d0 = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve64cd554ea6844407237a75dd53d2a6d0.url(args, options),
    method: 'post',
})

resolve64cd554ea6844407237a75dd53d2a6d0.definition = {
    methods: ["post"],
    url: '/admin/audit-assignments/{assignment}/reports/{report}/dispute/resolve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
resolve64cd554ea6844407237a75dd53d2a6d0.url = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            assignment: args[0],
            report: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        assignment: args.assignment,
        report: args.report,
    }

    return resolve64cd554ea6844407237a75dd53d2a6d0.definition.url
            .replace('{assignment}', parsedArgs.assignment.toString())
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
resolve64cd554ea6844407237a75dd53d2a6d0.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve64cd554ea6844407237a75dd53d2a6d0.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
const resolve64cd554ea6844407237a75dd53d2a6d0Form = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve64cd554ea6844407237a75dd53d2a6d0.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::resolve
* @see app/Http/Controllers/AuditDisputeController.php:38
* @route '/admin/audit-assignments/{assignment}/reports/{report}/dispute/resolve'
*/
resolve64cd554ea6844407237a75dd53d2a6d0Form.post = (args: { assignment: string | number, report: string | number } | [assignment: string | number, report: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve64cd554ea6844407237a75dd53d2a6d0.url(args, options),
    method: 'post',
})

resolve64cd554ea6844407237a75dd53d2a6d0.form = resolve64cd554ea6844407237a75dd53d2a6d0Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditDisputeController::resolve, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `resolve['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const resolve = {
    '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/escalate': resolve3d0b3994f6f295d610b17b657844f28b,
    '/api/v1/staff/audit-assignments/{assignment}/reports/{report}/dispute/resolve': resolve48bcd44258790a6368228bd2e4b0282d,
    '/admin/audit-assignments/{assignment}/reports/{report}/dispute/escalate': resolve897af81fbe6beb3157cb61570e91b2e9,
    '/admin/audit-assignments/{assignment}/reports/{report}/dispute/resolve': resolve64cd554ea6844407237a75dd53d2a6d0,
}

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/api/v1/auditor/reports/{report}/dispute/uphold'
*/
const upholdee45e51aebba53bc6e22f864eff77ad9 = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upholdee45e51aebba53bc6e22f864eff77ad9.url(args, options),
    method: 'post',
})

upholdee45e51aebba53bc6e22f864eff77ad9.definition = {
    methods: ["post"],
    url: '/api/v1/auditor/reports/{report}/dispute/uphold',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/api/v1/auditor/reports/{report}/dispute/uphold'
*/
upholdee45e51aebba53bc6e22f864eff77ad9.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { report: args }
    }

    if (Array.isArray(args)) {
        args = {
            report: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        report: args.report,
    }

    return upholdee45e51aebba53bc6e22f864eff77ad9.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/api/v1/auditor/reports/{report}/dispute/uphold'
*/
upholdee45e51aebba53bc6e22f864eff77ad9.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upholdee45e51aebba53bc6e22f864eff77ad9.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/api/v1/auditor/reports/{report}/dispute/uphold'
*/
const upholdee45e51aebba53bc6e22f864eff77ad9Form = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upholdee45e51aebba53bc6e22f864eff77ad9.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/api/v1/auditor/reports/{report}/dispute/uphold'
*/
upholdee45e51aebba53bc6e22f864eff77ad9Form.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upholdee45e51aebba53bc6e22f864eff77ad9.url(args, options),
    method: 'post',
})

upholdee45e51aebba53bc6e22f864eff77ad9.form = upholdee45e51aebba53bc6e22f864eff77ad9Form
/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
const uphold0b7da443d37fe0d054ce7ab8568863a4 = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uphold0b7da443d37fe0d054ce7ab8568863a4.url(args, options),
    method: 'post',
})

uphold0b7da443d37fe0d054ce7ab8568863a4.definition = {
    methods: ["post"],
    url: '/auditor/reports/{report}/dispute/uphold',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
uphold0b7da443d37fe0d054ce7ab8568863a4.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { report: args }
    }

    if (Array.isArray(args)) {
        args = {
            report: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        report: args.report,
    }

    return uphold0b7da443d37fe0d054ce7ab8568863a4.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
uphold0b7da443d37fe0d054ce7ab8568863a4.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uphold0b7da443d37fe0d054ce7ab8568863a4.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
const uphold0b7da443d37fe0d054ce7ab8568863a4Form = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uphold0b7da443d37fe0d054ce7ab8568863a4.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AuditDisputeController::uphold
* @see app/Http/Controllers/AuditDisputeController.php:32
* @route '/auditor/reports/{report}/dispute/uphold'
*/
uphold0b7da443d37fe0d054ce7ab8568863a4Form.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uphold0b7da443d37fe0d054ce7ab8568863a4.url(args, options),
    method: 'post',
})

uphold0b7da443d37fe0d054ce7ab8568863a4.form = uphold0b7da443d37fe0d054ce7ab8568863a4Form

/**
* Multiple routes resolve to \App\Http\Controllers\AuditDisputeController::uphold, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `uphold['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const uphold = {
    '/api/v1/auditor/reports/{report}/dispute/uphold': upholdee45e51aebba53bc6e22f864eff77ad9,
    '/auditor/reports/{report}/dispute/uphold': uphold0b7da443d37fe0d054ce7ab8568863a4,
}

const AuditDisputeController = { dispute, proof, operation, show, resolve, uphold }

export default AuditDisputeController