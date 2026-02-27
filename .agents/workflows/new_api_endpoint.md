---
description: How to create a new API endpoint using VotaCrudGenerator
---
# Workflow: Create a New API Endpoint

When the user asks to add a new API endpoint or resource, follow these exact steps:

1. **Design the Database Schema (Migration)**
   - Run `make artisan args="make:migration create_{table_name}_table"`
   - Open the generated migration file and define the schema.
   - **CRITICAL:** You MUST add `->comment('...')` to every column to explain its business purpose. Our tool `VotaCrudGenerator` uses these comments to build understanding into the generated files.

2. **Run the Migration**
   - Run `make artisan args="migrate"`
   - Verify the table was created successfully in the database.

3. **Generate the Complete CRUD Boilerplate**
   - Run `make artisan args="vota:crud {ModelName}"` (where ModelName is Singular PascalCase, e.g., `Post` for `posts` table).
   - This command will automatically introspect the database schema and create EVERYTHING you need:
     - The `Model` (with fillable fields, casts, relationships, and PHPDocs from your comments)
     - The `Controller` (API Resource Controller)
     - The `FormRequest` (Store and Update requests with validation rules mapped directly from DB types)
     - The `JsonResource` API Resource
     - The `Policy`
     - The `Factory`
   - It will also automatically append the API route to `routes/api.php`.

4. **Verify and Adjust (Only if needed)**
   - The generated code is production-ready. 
   - **DO NOT** rewrite the Controller or create DTOs/Actions manually for standard CRUD operations. 
   - Only implement custom Actions/DTOs if the user explicitly requests complex custom business logic (e.g., calling external third-party APIs, triggering complex domain events) that doesn't fit standard CRUD.

5. **Write a Feature Test**
   - Run `make artisan args="make:test {ModelName}EndpointTest --pest"`
   - Test successful API creation, validation errors, and any custom logic you might have added.
