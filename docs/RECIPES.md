# Recipes

Step-by-step for the things this project generates instead of writing by hand. The short version
lives in `CLAUDE.md`; this file is the detail you open while doing it.

## A new entity, end to end

### 1. Migration

Write the migration first — the generators read the **database**, not your intentions. Every column
gets a `->comment()`: what it means in business terms, what an enum's values are, what scale a money
column uses, what a foreign key points at and why. Those comments become validation hints, admin
labels and `docs/db_schema.md`, so a missing one degrades three things at once.

```bash
make migrate
make artisan args="schema:markdown"   # docs/db_schema.md now describes the new table
```

### 2. `vota:crud`

```bash
make artisan args="vota:crud Product"
```

Generates the model, API controller (`App\Http\Controllers\Api\V1`), form requests, resource,
policy and factory from the live table. **Do not hand-write any of them**; if the output is wrong,
the migration or its comments are wrong.

**The gotcha:** the generated `apiResource` line is *appended to the end of `routes/api.php`* — the
generator cannot inject into a group. Anything below that marker has no `auth:sanctum` and no tenant
scope, i.e. the whole table is public. Move the line into the authenticated group by hand and check
it with `php artisan route:list --path=api/v1`.

Then narrow what the generator could not know: validation rules that depend on other rows, the
fields the resource must *not* expose, and the policy's real conditions.

### 3. Filament resource

```bash
make artisan args="make:filament-resource Product --generate"
```

`--generate` builds the form and table from the schema. Four edits are required every single time —
they are the difference between "a panel exists" and "an operator can work":

1. **Authorization.** A generated resource is reachable by anyone who can open the panel. Point it
   at the policy or permission (`canViewAny()` / `canCreate()` / …, or the panel-wide gate) before
   it ships. This is the one that has to be right on the first try.
2. **Navigation.** Set `$navigationGroup`, `$navigationIcon` and `$navigationSort` — ungrouped
   resources pile up at the root of the sidebar and the panel becomes unnavigable at about a dozen
   entities.
3. **Weed the table.** The generator lists *every* column: ids, timestamps, foreign keys, long text.
   Keep the three to six an operator actually scans, make one of them the record title, and hide the
   rest behind `toggleable()` or the detail page.
4. **Filters and search.** Add the filters that match how the data is really looked up (status,
   date range, owner) and set `$recordTitleAttribute` / searchable columns. Without them the panel
   is a wall of rows.

Relations that matter to an operator get a relation manager; everything else stays out.

### 4. Test and finish

```bash
make test args="--filter Product"
```

A Pest feature test that walks the endpoint's real lifecycle: create, read as another user (must not
be visible), update, delete. Then the "what to click by hand" list for the owner: the admin URL, the
API path, and what the expected result looks like.

## Adding a permission

Add the case to the permission enum → register the default in the permission registry → run the
seeder (it is additive on purpose: the runtime matrix lives in the database and is edited in the
admin panel, never re-synced from code) → `make artisan args="permissions:markdown"` → put the gate
in the code (`$user->can('x.y')`, or the `permission:` middleware on the route).

## Adding a translated string

There is no such thing as a user-visible string that exists in one language. Add the key to `lang/en`
and `lang/ru` in the same commit; a parity test fails on a key that exists in one file only. Keys
are namespaced by feature, not by screen, so the same message can be reused by API, mail and bot.
