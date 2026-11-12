# Nursery Management API

A RESTful API built with **Laravel 12** and **MySQL** to manage nursery rooms, children, check-ins, and check-outs with multi-tenant data isolation.

## Features

- Multi-tenant architecture.
- Tenant identification through the `X-Tenant-ID` request header.
- Room and child management through tenant-scoped database relationships.
- Child check-in and check-out management.
- Prevention of duplicate active check-ins.
- Room occupant listing.
- Tenant-level data isolation.
- Database transactions and validation.
- Automated feature tests using PHPUnit.

## Technology Stack

- PHP
- Laravel 12
- MySQL
- PHPUnit
- REST API
- Postman

## Requirements

- PHP version compatible with Laravel 12
- Composer
- MySQL
- PHP extensions required by Laravel

## Installation

### 1. Clone the repository

```bash
git clone <YOUR_GITHUB_REPOSITORY_URL>
cd nursery-pro
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Update your `.env` file with your MySQL database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nursery_management
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in MySQL before running migrations.

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Start the application

```bash
php artisan serve
```

The API will be available at:

`http://127.0.0.1:8000`

## Database Structure

The application contains four primary tables:

| Table | Description |
|---|---|
| tenants | Stores nursery/tenant information |
| rooms | Stores rooms belonging to tenants |
| children | Stores children belonging to tenants |
| check_ins | Stores child check-in and check-out records |

### Tenant Isolation

Every room, child, and check-in record is associated with a tenant.

The application identifies the current tenant through the `X-Tenant-ID` HTTP header.

All relevant database queries are scoped to the resolved tenant.

A child or room belonging to another tenant cannot be accessed through the current tenant's API requests.

## API Documentation

All API endpoints require the following header:

```http
X-Tenant-ID: 1
Accept: application/json
Content-Type: application/json
```

### 1. Check In a Child

**Endpoint**

```http
POST /api/rooms/{room}/check-ins
```

**Request body**

```json
{
    "child_id": 1
}
```

**Successful response — 201 Created**

```json
{
    "message": "Child checked into room successfully.",
    "data": {
        "check_in_id": 1,
        "room_id": 1,
        "room_name": "Sunflowers",
        "child_id": 1,
        "child_name": "Rahul",
        "checked_in_at": "2026-11-12T..."
    }
}
```

**Validation and business rules**

- Room must belong to the current tenant.
- Child must belong to the current tenant.
- A child cannot have more than one active check-in.
- A successful check-in creates a new check-in record.

### 2. Check Out a Child

**Endpoint**

```http
POST /api/check-ins/{id}/check-out
```

No request body is required.

**Successful response — 200 OK**

Returns the check-in information with the `checked_out_at` timestamp.

**Validation and business rules**

- Check-in record must belong to the current tenant.
- A child cannot be checked out twice.
- Check-out updates the existing record rather than deleting it.

### 3. Get Current Room Occupants

**Endpoint**

```http
GET /api/rooms/{room}/occupants
```

**Successful response — 200 OK**

```json
{
    "message": "Room occupants retrieved successfully.",
    "total_occupants": 1,
    "data": [
        {
            "check_in_id": 1,
            "child_id": 1,
            "child_name": "Rahul",
            "room_id": 1,
            "checked_in_at": "2026-11-12T..."
        }
    ]
}
```

Only children with an active check-in (`checked_out_at` is null) are returned.

If no children are currently checked in, the API returns an empty data array and a total count of zero.

### Child Room Assignment Policy

**Decision: Reject duplicate active check-ins.**

A child can be assigned to only one room at a time.

If staff attempt to check in a child who already has an active check-in in another room, the API rejects the request with HTTP `422 Unprocessable Entity`.

Example error:

```json
{
    "message": "This child is already checked into a room.",
    "errors": {
        "child_id": [
            "This child is already checked into a room."
        ]
    }
}
```

**Reasoning:**

- Prevents a child from appearing in multiple rooms simultaneously.
- Avoids accidental movement of children between rooms.
- Preserves accurate attendance and check-in/check-out history.
- Requires staff to explicitly check out a child before assigning them to another room.

This approach prioritizes data consistency, child safety, and predictable staff workflows over automatic room reassignment.

A child can be checked into another room only after their previous active check-in has been closed.


## HTTP Status Codes

| Status | Description |
|---|---|
| 200 | Request successful |
| 201 | Resource created successfully |
| 400 | Missing tenant header |
| 404 | Tenant, room, child, or check-in not found in the current tenant context |
| 422 | Validation error or duplicate active check-in |

## Automated Testing

The project includes PHPUnit feature tests covering the main API workflows and tenant isolation.

Configure a separate testing database named `nursery_management_test`.

Create `.env.testing` with your testing database credentials. Do not commit this file.

Run the testing migrations:

```bash
php artisan migrate --env=testing
```

Run the feature tests:

```bash
php artisan test --filter=NurseryApiTest
```

The current feature test suite contains eight tests covering:

1. Successful child check-in.
2. Duplicate check-in prevention.
3. Cross-tenant child isolation.
4. Cross-tenant room isolation.
5. Successful child check-out.
6. Cross-tenant check-out prevention.
7. Active room occupant listing.
8. Missing tenant header validation.

## Design Decisions

- **Tenant scoping:** Database queries use the resolved tenant relationship to prevent cross-tenant access.
- **Transactions:** Check-in and check-out operations use database transactions.
- **Duplicate prevention:** A child cannot have multiple active check-ins.
- **Historical records:** Check-out updates the existing check-in record, preserving history.
- **Data integrity:** Foreign keys and tenant relationships help maintain database consistency.
- **Error handling:** Invalid resources in the current tenant context return 404 responses; business validation errors return 422 responses.

## Assumptions and Limitations

- Tenant identification is based on the `X-Tenant-ID` header.
- Authentication and authorization of users against tenants are outside the current assignment scope. In production, tenant membership must be verified for the authenticated user.
- Room and child creation are handled separately from the three required API endpoints.
- The API is designed for JSON requests and responses.

## License

This project was created as a nursery management API assignment.
