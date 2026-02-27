---
description: How to create a new API endpoint using Best Practices
---
# Workflow: Create a New API Endpoint

When the user asks to add a new API endpoint, follow these exact steps:

1. **Design the Database Schema**
   - Run `make artisan args="make:migration create_{table_name}_table"`
   - Open the generated migration file and define the schema carefully. 
   - **CRITICAL:** Add `->comment('...')` to every column to explain its business purpose. This helps the generator understand context.

2. **Run the Migration**
   - Run `make artisan args="migrate"`
   - Verify the table was created successfully.

3. **Generate the Base CRUD Boilerplate**
   - Run `make artisan args="vota:crud {ModelName}"` (where ModelName is Singular PascalCase, e.g., `Post`).
   - This command will automatically introspect your database schema and create the `Model`, `Controller` (API Resource), `FormRequest`, `JsonResource`, `Policy`, and `Factory`.
   - It will also append the API route to `routes/api.php`.

4. **Implement Domain-Driven Design (DDD) via Actions and DTOs**
   - `votacrudgenerator` generates *standard* CRUD operations in the Controller.
   - **However, for any complex business logic (e.g., triggering events, syncing relations, side-effects), you MUST extract this out of the Controller.**
   - **Create a DTO (Data Transfer Object)** in `app/DTOs/` to securely hold the validated data coming from the FormRequest. Add a `fromRequest()` static method mapping the request fields to the DTO's `readonly` properties.
   - **Create an Action** in `app/Actions/` (e.g., `Create{ModelName}Action`). This class should have a single public method (e.g. `execute(ModelDTO $dto)`). Put all DB transaction logic here.
   - **Update the auto-generated Controller** to invoke this Action by passing the DTO, rather than directly using `Model::create($request->validated())`. 

5. **Write a Feature Test**
   - Run `make artisan args="make:test {ModelName}EndpointTest --pest"`
   - Test successful API creation, validation errors, and business logic execution.
