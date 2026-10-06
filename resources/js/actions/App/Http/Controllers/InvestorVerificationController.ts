import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
const show7498856edc6acb3a5da75eac4af5573d = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show7498856edc6acb3a5da75eac4af5573d.url(options),
    method: 'get',
})

show7498856edc6acb3a5da75eac4af5573d.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/verification',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
show7498856edc6acb3a5da75eac4af5573d.url = (options?: RouteQueryOptions) => {
    return show7498856edc6acb3a5da75eac4af5573d.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
show7498856edc6acb3a5da75eac4af5573d.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show7498856edc6acb3a5da75eac4af5573d.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
show7498856edc6acb3a5da75eac4af5573d.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show7498856edc6acb3a5da75eac4af5573d.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
const show7498856edc6acb3a5da75eac4af5573dForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show7498856edc6acb3a5da75eac4af5573d.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
show7498856edc6acb3a5da75eac4af5573dForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show7498856edc6acb3a5da75eac4af5573d.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/api/v1/investor/verification'
*/
show7498856edc6acb3a5da75eac4af5573dForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show7498856edc6acb3a5da75eac4af5573d.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show7498856edc6acb3a5da75eac4af5573d.form = show7498856edc6acb3a5da75eac4af5573dForm
/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
const showde7871553fd6c7ea1659d217e0115804 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showde7871553fd6c7ea1659d217e0115804.url(options),
    method: 'get',
})

showde7871553fd6c7ea1659d217e0115804.definition = {
    methods: ["get","head"],
    url: '/investor/verification',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
showde7871553fd6c7ea1659d217e0115804.url = (options?: RouteQueryOptions) => {
    return showde7871553fd6c7ea1659d217e0115804.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
showde7871553fd6c7ea1659d217e0115804.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showde7871553fd6c7ea1659d217e0115804.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
showde7871553fd6c7ea1659d217e0115804.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showde7871553fd6c7ea1659d217e0115804.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
const showde7871553fd6c7ea1659d217e0115804Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showde7871553fd6c7ea1659d217e0115804.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
showde7871553fd6c7ea1659d217e0115804Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showde7871553fd6c7ea1659d217e0115804.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::show
* @see app/Http/Controllers/InvestorVerificationController.php:40
* @route '/investor/verification'
*/
showde7871553fd6c7ea1659d217e0115804Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showde7871553fd6c7ea1659d217e0115804.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showde7871553fd6c7ea1659d217e0115804.form = showde7871553fd6c7ea1659d217e0115804Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorVerificationController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/investor/verification': show7498856edc6acb3a5da75eac4af5573d,
    '/investor/verification': showde7871553fd6c7ea1659d217e0115804,
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/api/v1/investor/verification/steps'
*/
const savea058d030671a0dbb9bfdf102ad1a2193 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savea058d030671a0dbb9bfdf102ad1a2193.url(options),
    method: 'post',
})

savea058d030671a0dbb9bfdf102ad1a2193.definition = {
    methods: ["post"],
    url: '/api/v1/investor/verification/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/api/v1/investor/verification/steps'
*/
savea058d030671a0dbb9bfdf102ad1a2193.url = (options?: RouteQueryOptions) => {
    return savea058d030671a0dbb9bfdf102ad1a2193.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/api/v1/investor/verification/steps'
*/
savea058d030671a0dbb9bfdf102ad1a2193.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savea058d030671a0dbb9bfdf102ad1a2193.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/api/v1/investor/verification/steps'
*/
const savea058d030671a0dbb9bfdf102ad1a2193Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: savea058d030671a0dbb9bfdf102ad1a2193.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/api/v1/investor/verification/steps'
*/
savea058d030671a0dbb9bfdf102ad1a2193Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: savea058d030671a0dbb9bfdf102ad1a2193.url(options),
    method: 'post',
})

savea058d030671a0dbb9bfdf102ad1a2193.form = savea058d030671a0dbb9bfdf102ad1a2193Form
/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
const save32204cb3f96b06b072e39b275d6a7654 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save32204cb3f96b06b072e39b275d6a7654.url(options),
    method: 'post',
})

save32204cb3f96b06b072e39b275d6a7654.definition = {
    methods: ["post"],
    url: '/investor/verification/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
save32204cb3f96b06b072e39b275d6a7654.url = (options?: RouteQueryOptions) => {
    return save32204cb3f96b06b072e39b275d6a7654.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
save32204cb3f96b06b072e39b275d6a7654.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save32204cb3f96b06b072e39b275d6a7654.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
const save32204cb3f96b06b072e39b275d6a7654Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save32204cb3f96b06b072e39b275d6a7654.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::save
* @see app/Http/Controllers/InvestorVerificationController.php:54
* @route '/investor/verification/steps'
*/
save32204cb3f96b06b072e39b275d6a7654Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save32204cb3f96b06b072e39b275d6a7654.url(options),
    method: 'post',
})

save32204cb3f96b06b072e39b275d6a7654.form = save32204cb3f96b06b072e39b275d6a7654Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorVerificationController::save, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `save['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const save = {
    '/api/v1/investor/verification/steps': savea058d030671a0dbb9bfdf102ad1a2193,
    '/investor/verification/steps': save32204cb3f96b06b072e39b275d6a7654,
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/api/v1/investor/verification/documents'
*/
const uploadceb47da3add9aef60fdc14aac4126c6e = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadceb47da3add9aef60fdc14aac4126c6e.url(options),
    method: 'post',
})

uploadceb47da3add9aef60fdc14aac4126c6e.definition = {
    methods: ["post"],
    url: '/api/v1/investor/verification/documents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/api/v1/investor/verification/documents'
*/
uploadceb47da3add9aef60fdc14aac4126c6e.url = (options?: RouteQueryOptions) => {
    return uploadceb47da3add9aef60fdc14aac4126c6e.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/api/v1/investor/verification/documents'
*/
uploadceb47da3add9aef60fdc14aac4126c6e.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadceb47da3add9aef60fdc14aac4126c6e.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/api/v1/investor/verification/documents'
*/
const uploadceb47da3add9aef60fdc14aac4126c6eForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadceb47da3add9aef60fdc14aac4126c6e.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/api/v1/investor/verification/documents'
*/
uploadceb47da3add9aef60fdc14aac4126c6eForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadceb47da3add9aef60fdc14aac4126c6e.url(options),
    method: 'post',
})

uploadceb47da3add9aef60fdc14aac4126c6e.form = uploadceb47da3add9aef60fdc14aac4126c6eForm
/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
const upload00272106b54ce13d1bd3a04f74e7b7e9 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload00272106b54ce13d1bd3a04f74e7b7e9.url(options),
    method: 'post',
})

upload00272106b54ce13d1bd3a04f74e7b7e9.definition = {
    methods: ["post"],
    url: '/investor/verification/documents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
upload00272106b54ce13d1bd3a04f74e7b7e9.url = (options?: RouteQueryOptions) => {
    return upload00272106b54ce13d1bd3a04f74e7b7e9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
upload00272106b54ce13d1bd3a04f74e7b7e9.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload00272106b54ce13d1bd3a04f74e7b7e9.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
const upload00272106b54ce13d1bd3a04f74e7b7e9Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload00272106b54ce13d1bd3a04f74e7b7e9.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::upload
* @see app/Http/Controllers/InvestorVerificationController.php:61
* @route '/investor/verification/documents'
*/
upload00272106b54ce13d1bd3a04f74e7b7e9Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload00272106b54ce13d1bd3a04f74e7b7e9.url(options),
    method: 'post',
})

upload00272106b54ce13d1bd3a04f74e7b7e9.form = upload00272106b54ce13d1bd3a04f74e7b7e9Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorVerificationController::upload, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `upload['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const upload = {
    '/api/v1/investor/verification/documents': uploadceb47da3add9aef60fdc14aac4126c6e,
    '/investor/verification/documents': upload00272106b54ce13d1bd3a04f74e7b7e9,
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/api/v1/investor/verification/submit'
*/
const submitab9c35128af7f532a1a67ae1ae838a78 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitab9c35128af7f532a1a67ae1ae838a78.url(options),
    method: 'post',
})

submitab9c35128af7f532a1a67ae1ae838a78.definition = {
    methods: ["post"],
    url: '/api/v1/investor/verification/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/api/v1/investor/verification/submit'
*/
submitab9c35128af7f532a1a67ae1ae838a78.url = (options?: RouteQueryOptions) => {
    return submitab9c35128af7f532a1a67ae1ae838a78.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/api/v1/investor/verification/submit'
*/
submitab9c35128af7f532a1a67ae1ae838a78.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitab9c35128af7f532a1a67ae1ae838a78.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/api/v1/investor/verification/submit'
*/
const submitab9c35128af7f532a1a67ae1ae838a78Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitab9c35128af7f532a1a67ae1ae838a78.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/api/v1/investor/verification/submit'
*/
submitab9c35128af7f532a1a67ae1ae838a78Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitab9c35128af7f532a1a67ae1ae838a78.url(options),
    method: 'post',
})

submitab9c35128af7f532a1a67ae1ae838a78.form = submitab9c35128af7f532a1a67ae1ae838a78Form
/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
const submitda95dfa569d727bede107655bee3682a = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitda95dfa569d727bede107655bee3682a.url(options),
    method: 'post',
})

submitda95dfa569d727bede107655bee3682a.definition = {
    methods: ["post"],
    url: '/investor/verification/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
submitda95dfa569d727bede107655bee3682a.url = (options?: RouteQueryOptions) => {
    return submitda95dfa569d727bede107655bee3682a.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
submitda95dfa569d727bede107655bee3682a.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitda95dfa569d727bede107655bee3682a.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
const submitda95dfa569d727bede107655bee3682aForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitda95dfa569d727bede107655bee3682a.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorVerificationController::submit
* @see app/Http/Controllers/InvestorVerificationController.php:71
* @route '/investor/verification/submit'
*/
submitda95dfa569d727bede107655bee3682aForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitda95dfa569d727bede107655bee3682a.url(options),
    method: 'post',
})

submitda95dfa569d727bede107655bee3682a.form = submitda95dfa569d727bede107655bee3682aForm

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorVerificationController::submit, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `submit['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const submit = {
    '/api/v1/investor/verification/submit': submitab9c35128af7f532a1a67ae1ae838a78,
    '/investor/verification/submit': submitda95dfa569d727bede107655bee3682a,
}

const InvestorVerificationController = { show, save, upload, submit }

export default InvestorVerificationController