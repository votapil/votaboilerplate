---
description: TDD Implementation Workflow
---

# Workflow: Test-Driven Development (TDD) Implementation

When asked to implement new business logic, domain actions, or complex features, ALWAYS follow a strict Test-Driven Development approach. 

## Step 1: Write the Failing Test First
1. Understand the exact requirement from the user.
2. Draft a **Pest Feature Test** (or Unit Test, depending on the scope) in the `tests/` directory representing the end goal.
   - Run `make artisan make:test {FeatureName}Test --pest` (if it's a new feature).
3. The test must outline the "Happy Path" and immediate "Edge Cases". It shouldn't have the implementation details yet.
4. Run the test to ensure it **FAILS**:
   `make test args="--filter {FeatureName}"`
   You should see a failure (e.g., Class not found, 404 Not Found, Assertion failed).

## Step 2: Write the Minimum Code to Pass
1. Create the necessary files (e.g., Action, Controller method, Route) and write just enough code to make the failing test pass.
2. Avoid over-engineering at this stage. Hardcode values if necessary just to understand the data flow.
3. Run the test again. It must **PASS**.

## Step 3: Refactor and Fortify
1. Now that the test passes, refactor the code to its final, clean state:
   - Extract logic into proper Actions or DTOs (following `.antigravityrules`).
   - Remove redundancy.
2. After refactoring, run the test again. It must still **PASS**.
3. Add further edge-case tests (e.g., unauthorized users, missing validation data) and follow Steps 1-2 for them.

## Memory / State Tracking
After implementing the feature and running tests successfully, ALWAYS update `PROJECT_CONTEXT.md` with:
- What feature was just added.
- The corresponding test coverage.
- Next logical steps.
