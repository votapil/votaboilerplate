---
description: How to create a new API endpoint using Best Practices
---
# Workflow: Create a New API Endpoint

When the user asks to add a new API endpoint, follow these exact steps:

1. **Design the Database Schema**
   - Run `make artisan args="make:migration create_{table_name}_table"`
   - Open the generated migration file.
   - Define the schema carefully. **CRITICAL:** Add `->comment('...')` to every column to explain its business purpose. This helps the AI and generator understand the context.

2. **Run the Migration**
   - Run `make artisan args="migrate"`
   - Verify the table was created successfully in the PostgreSQL container.

3. **Generate the CRUD Boilerplate**
   - Run `make artisan args="vota:crud {ModelName}"` (where ModelName is Singular PascalCase, e.g., `Post` for `posts` table).
   - This command will automatically introspect your database schema and create:
     - The `Model` (with fillable fields, casts, and relations)
     - The `Controller` (API Resource Controller)
     - The `FormRequest` (Store and Update requests with validation rules mapped from DB types)
     - The `JsonResource` API Resource
     - The `Policy`
     - The `Factory`
     - It will also append the API route to `routes/api.php`

4. **Implement Custom Business Logic (Optional)**
   - The generated Controller is typically standard CRUD. If complex logic is required (e.g., triggering events, calling external APIs), extract that logic into an `app/Actions/` class or an `app/DTOs/` class and call it from the generated Controller.
   - E.g., `Create{ModelName}Action`.

5. **Write a Feature Test**
   - Run `make artisan args="make:test {ModelName}EndpointTest --pest"`
   - Test successful API creation, validation errors, and unauthorized access.
