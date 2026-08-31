/**
 * Mirror of the backend `App\Enums\PermissionName`.
 *
 * Hand-written on purpose, and small on purpose: only the permissions the FRONTEND branches on
 * belong here. Add a case to the PHP enum and a line here in the same commit — a name that
 * exists on only one side silently hides a screen from everybody, and there is no error to see.
 */
export const PERMISSIONS = {
    /** Gate for the admin panel; the same permission guards Filament at /admin. */
    adminAccess: 'admin.access',
} as const

export type PermissionName = typeof PERMISSIONS[keyof typeof PERMISSIONS]
