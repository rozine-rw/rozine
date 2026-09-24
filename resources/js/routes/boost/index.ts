import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see Users/engineersticity/Documents/projects/rozine/vendor/laravel/boost/src/BoostServiceProvider.php:95
* @route '/_boost/browser-logs'
*/
export const browserLogs = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: browserLogs.url(options),
    method: 'post',
})

browserLogs.definition = {
    methods: ["post"],
    url: '/_boost/browser-logs',
} satisfies RouteDefinition<["post"]>

/**
* @see Users/engineersticity/Documents/projects/rozine/vendor/laravel/boost/src/BoostServiceProvider.php:95
* @route '/_boost/browser-logs'
*/
browserLogs.url = (options?: RouteQueryOptions) => {
    return browserLogs.definition.url + queryParams(options)
}

/**
* @see Users/engineersticity/Documents/projects/rozine/vendor/laravel/boost/src/BoostServiceProvider.php:95
* @route '/_boost/browser-logs'
*/
browserLogs.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: browserLogs.url(options),
    method: 'post',
})

/**
* @see Users/engineersticity/Documents/projects/rozine/vendor/laravel/boost/src/BoostServiceProvider.php:95
* @route '/_boost/browser-logs'
*/
const browserLogsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: browserLogs.url(options),
    method: 'post',
})

/**
* @see Users/engineersticity/Documents/projects/rozine/vendor/laravel/boost/src/BoostServiceProvider.php:95
* @route '/_boost/browser-logs'
*/
browserLogsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: browserLogs.url(options),
    method: 'post',
})

browserLogs.form = browserLogsForm

const boost = {
    browserLogs: Object.assign(browserLogs, browserLogs),
}

export default boost