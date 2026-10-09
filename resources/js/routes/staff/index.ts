import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import audit from './audit'
import disbursements from './disbursements'
import applications from './applications'
import sections from './sections'
import investors from './investors'
import businesses from './businesses'
import auditors from './auditors'
import staff from './staff'
import events from './events'
import investorVerifications from './investor-verifications'
import stagingMailTesters from './staging-mail-testers'
import changes from './changes'
/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/admin/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/admin/dashboard'
*/
dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dashboard.form = dashboardForm

const staffNamespace = {
    audit: Object.assign(audit, audit),
    disbursements: Object.assign(disbursements, disbursements),
    applications: Object.assign(applications, applications),
    sections: Object.assign(sections, sections),
    investors: Object.assign(investors, investors),
    businesses: Object.assign(businesses, businesses),
    auditors: Object.assign(auditors, auditors),
    dashboard: Object.assign(dashboard, dashboard),
    staff: Object.assign(staff, staff),
    events: Object.assign(events, events),
    investorVerifications: Object.assign(investorVerifications, investorVerifications),
    stagingMailTesters: Object.assign(stagingMailTesters, stagingMailTesters),
    changes: Object.assign(changes, changes),
}

export default staffNamespace