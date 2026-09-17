/**
 * Mirror of the backend `App\Enums\PermissionName`.
 *
 * Hand-written on purpose, and small on purpose: only the permissions the FRONTEND branches on
 * belong here. Add a case to the PHP enum and a line here in the same commit — a name that
 * exists on only one side silently hides a screen from everybody, and there is no error to see.
 *
 * `satisfies` ties the two sides together: every value below has to be a member of the generated
 * `App.Enums.PermissionName` (`app/types/generated.d.ts`, written by `make ts-sync`), so renaming
 * a case in the PHP enum turns this file red. It stays a subset — this is not the full list.
 *
 * Caught by the editor and by `nuxt typecheck`, NOT by `make verify`: nothing in the pipeline
 * runs a type checker today (Vite strips types without reading them). Until one is wired in,
 * this is a guard you have to look at, not one that stops a push.
 */
export const PERMISSIONS = {
    /** Gate for the admin panel; the same permission guards Filament at /admin. */
    adminAccess: 'admin.access',
} as const satisfies Record<string, App.Enums.PermissionName>

export type PermissionName = typeof PERMISSIONS[keyof typeof PERMISSIONS]
