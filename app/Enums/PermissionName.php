<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Every permission the application knows about.
 *
 * Naming convention: `<area>.<section>`. Keep permissions COARSE — one per
 * section of the product, not one per model per verb. A generated CRUD matrix
 * produces hundreds of checkboxes nobody can reason about; a handful of named
 * capabilities can be reviewed in a pull request.
 *
 * Descriptions and default roles live in App\Support\PermissionRegistry.
 * #[TypeScript] exports the values as a string-literal union, so the frontend's
 * can() helper is checked against the same list the backend enforces.
 *
 * RULE: a permission declared here and checked nowhere is a lie the generated
 * documentation tells — the matrix promises a boundary that does not exist.
 * Every case must have at least one enforcement point, and
 * tests/Feature/Structure/PermissionCoverageTest fails the build when one does
 * not. Add the gate in the same commit as the case, or do not add the case.
 */
#[TypeScript]
enum PermissionName: string
{
    /** Open the admin panel at all — App\Models\User::canAccessPanel(). */
    case AdminAccess = 'admin.access';

    /**
     * The user section of the panel: accounts, and the roles and permissions
     * held by them. Separate from admin.access because this is the
     * privilege-escalation surface — whoever reaches it can promote themselves.
     * Enforced by App\Policies\UserPolicy.
     */
    case AdminUsers = 'admin.users';

    /** Operational dashboards, starting with the queue monitor (Horizon). */
    case AdminSystem = 'admin.system';
}
