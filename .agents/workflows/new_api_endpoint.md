---
description: How to create a new API endpoint using Best Practices (Database-First)
---
# Workflow: Create a New API Endpoint

When asked to add a new API endpoint or model resource, you must follow this strict Database-First approach using VotaCrudGenerator.

## 1. Design the Database Architecture
- Before writing any code, analyze the business requirements and design the database schema.
- Determine the necessary tables, column types, relationships (foreign keys), nullable fields, and constraints.
- Discuss the schema with the user if there are ambiguities.

## 2. Create the Migration
- Run `make artisan make:migration create_{table_name}_table`
- Open the generated migration file and define the schema based on your design.
- **CRITICAL:** You MUST add explicit `->comment('...')` to every column to explain its business purpose. Our tool `VotaCrudGenerator` uses these comments to automatically document the Models and API Resources.

## 3. Run the Migration
- Run `make artisan migrate`
- Verify the table was created successfully in the PostgreSQL container.

## 4. Generate the Complete CRUD Boilerplate
- Run `make artisan vota:crud {ModelName}` (where ModelName is Singular PascalCase, e.g., `Post` for the `posts` table).
- This command will automatically introspect the concrete database schema and generate EVERYTHING you need:
  - The `Model` (with `$fillable` fields, `$casts`, relationships, and PHPDocs containing your comments)
  - The `Controller` (API Resource Controller mapped to REST actions)
  - The `FormRequest` (Store and Update requests with validation rules mapped directly from the DB types)
  - The `JsonResource` (API Resource for JSON formatting)
  - The `Policy` (For authorization checks)
  - The `Factory`
- It will also automatically append the API endpoint to `routes/api.php`.

## 5. Verify and Test
- The generated code is production-ready for standard CRUD operations. **DO NOT** rewrite the Controller or create DTOs/Actions manually unless explicitly requested to implement complex external business logic.
- Run `make artisan make:test {ModelName}EndpointTest --pest`
- Write Pest Feature tests to cover successful API creation, validation errors, and unauthorized access.
