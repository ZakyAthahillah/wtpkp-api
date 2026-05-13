## FORBIDDEN ACTIONS

- do not modify unrelated code
- do not refactor entire module
- do not change environment config
- do not hardcode credentials

## skills and learning

- always make file api documentation at folder docs/api when you create new endpoint api
- always update `docs/api/all-endpoints.txt` after creating or changing API endpoints so the endpoint list stays complete
- always include simple usage examples in `docs/api/all-endpoints.txt` for each API endpoint, focusing on headers, query params, and request body examples without requiring response examples
- always make file learning at folder docs/learning if you have error for documentation bug or debugging
- always check at folder docs/learning if you struggle after 3 prompts of fixing something. It might be a similiar issue from the past

## plan mode default

- enter plan mode (3+ steps or architectureal decisions)
- if something goes sideways, STOP and re-plan immediately. Don't keep pushing
- use plan mode for verification steps, not just building
- always asking question for get more clarity
- write detailed specs upfront to reduce ambiguity
- always breakdown tasks with its dependencies
- always WAIT for user approval before:
    - creating files
    - modifying existing files
    - deleting code

## Architecture and Coding Rules

- Use camelCase for function naming
- Follow MVP (Model View Controller) architecture and use best practice for architecture MVP
- Do not write helper functions inside controllers; create them in the `/helpers` folder
- Always wrap critical logic with try-catch blocks
- Always return standardized error responses
- Always use pagination for list endpoints
- Avoid N+1 queries (use eager loading)

## Database Safety Rule:

- Never run `php artisan migrate:refresh`.
- Never suggest `php artisan migrate:refresh` as a fix.
- Prefer safe alternatives such as `php artisan migrate`, targeted migrations, or manual SQL review.
- Always use consistent migration naming:
    - `create_<table_name>_table`
    - `add_<column_name>_to_<table_name>_table`
    - `update_<table_name>_table`
    - `drop_<column_name>_from_<table_name>_table`
- Never rename a migration file after it has already been executed with `php artisan migrate`.

## Validation

- Use required validation for all POST and PUT methods
- Validation MUST be applied when creating or updating data
- Do not process requests without proper validation

## Testing and Definition of Done

- For every API endpoint or function created, unit tests MUST be implemented
- Tests MUST cover multiple scenarios, including:
    - success case
    - validation error case
    - edge cases
    - failure/error case

## Definition of Done

A task is considered complete ONLY if:

- Unit tests are created and all tests pass
- The endpoint or function works as expected
- Response follows the standardized format
- No unrelated code is modified

## workflow

task is complete only if:

- unit test passed
- no lint error
- response follows standard format
- no unrelated changes

## response api

- use this format api

- 200

{
"success": true,
"message": "Data retrieved successfully",
"data": [
{
"id": "1",
"name": "Site Jakarta Barat",
"address": "Jl. Raya Kebon Jeruk No. 10, Jakarta Barat",
"latitude": -6.2001234,
"longitude": 106.7812345,
"radius_meter": 100,
"is_active": true,
"created_at": "2026-03-28 14:55:12",
"updated_at": "2026-03-28 14:55:12"
}
],
"meta": null,
"errors": null
}

- 400

{
"success": false,
"message": "Bad request",
"data": null,
"meta": null,
"errors": {
"request": [
"At least one field must be provided for update."
]
}
}

- 500

{
"success": false,
"message": "Failed to create site",
"data": null,
"meta": null,
"errors": null
}
