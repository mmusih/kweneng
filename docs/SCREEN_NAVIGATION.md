# Screen Back navigation

Authenticated web screens remember their origin in tab-local session storage,
scoped to the signed-in user and role. The shared controller is installed by
`resources/js/app.js`; the standalone student login slip loads the smaller
`screen-navigation.js` entry point.

The originating URL includes query parameters and its hash. Back links return
to that URL and identify the source screen by its heading. A `data-screen-label`
attribute can supply a clearer name, as on the afternoon timetable.

Ancestry is separate from browser history. Changing filters or pagination on
the same path does not add a step. Returning to an ancestor, including through
a save redirect, consumes that level. History state preserves ancestry on
refresh and browser Back/Forward. Scroll coordinates are restored when returning
to an ancestor. URL-based tab and filter selections are preserved; each screen
remains responsible for any view state that it does not encode in its URL.

Existing `data-admin-back` links, `data-screen-back` links, and links whose visible
label begins with “Back” share this behavior across roles. Ordinary Dashboard,
menu and breadcrumb links keep their destinations. New Back controls should
use `data-screen-back` and a server-rendered fallback URL. Dashboards have a
hidden Back control that becomes visible only when a source screen is known.

Direct entries, missing referrers, unavailable session storage and new tabs
without inherited navigation context use the server-rendered parent/dashboard
fallback. External URLs are never used as return destinations. Public login
and registration navigation retains its explicit destinations.

Validation: `npm test`, `npm run build`, and the PHP feature tests
`DashboardBackNavigationTest`, `NavigationLinksTest`, and `ClubsTest`.
