# Expense Tracker API

A RESTful API for tracking personal expenses with authentication, filtering, and categorization capabilities.

> Project based on [roadmap.sh Expense Tracker API](https://roadmap.sh/projects/expense-tracker-api)

## Features

- User authentication with JWT
- Create, read, update, and delete expenses
- Categorize expenses
- Filter expenses by date range (past week, past month, last 3 months, custom)
- Filter expenses by category
- Sort expenses by various fields
- Get expense summaries and statistics

## Requirements

- PHP 8.2+
- Composer
- Docker and Docker Compose (optional)

## Setup

### Using Docker

1. Clone the repository:

```bash
git clone <repository-url>
cd php-expence-tracker-api
```

2. Start the Docker containers:

```bash
docker-compose up -d
```

3. Enter the app container to run commands:

```bash
docker exec -it expense-tracker-app bash
```

4. Inside the container, run migrations and seeders:

```bash
php artisan migrate --seed
```

5. Generate JWT secret (if not already done):

```bash
php artisan jwt:secret
```

6. Access the API at http://localhost:8000/api

### Without Docker

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure your database
4. Run migrations and seeders: `php artisan migrate --seed`
5. Generate JWT secret: `php artisan jwt:secret`
6. Start the server: `php artisan serve`
7. Access the API at http://localhost:8000/api

## API Documentation

This project includes comprehensive API documentation using OpenAPI 3.0.3 specification and Swagger UI.

### Accessing the Documentation

- **Swagger UI Interface**: Visit [http://localhost:8000/docs](http://localhost:8000/docs) in your browser
- **Raw OpenAPI Specification**: Available at [http://localhost:8000/api/documentation/openapi.yaml](http://localhost:8000/api/documentation/openapi.yaml)

### Features

- Interactive documentation with Swagger UI
- Test API endpoints directly from the browser
- Detailed request/response schemas and examples
- Authentication support (JWT)
- Comprehensive documentation for all endpoints

### Using Authenticated Endpoints in Swagger UI

1. Use the login or register endpoint to obtain a JWT token
2. Click the "Authorize" button (padlock icon) at the top of the page
3. Enter your token in the format: `Bearer your-token-here`
4. Click "Authorize" to apply the token to all authenticated requests

## API Endpoints

### Authentication

- `POST /api/register` - Register a new user
  - Required fields: name, email, password, password_confirmation

- `POST /api/login` - Log in and get JWT token
  - Required fields: email, password

- `POST /api/logout` - Log out (invalidate token)
  - Requires authentication

- `POST /api/refresh` - Refresh JWT token
  - Requires authentication

- `GET /api/me` - Get authenticated user details
  - Requires authentication

### Categories

- `GET /api/categories` - List all categories
  - Requires authentication

- `POST /api/categories` - Create a new category
  - Requires authentication
  - Required fields: name

- `GET /api/categories/{id}` - Get a specific category
  - Requires authentication

- `PUT /api/categories/{id}` - Update a category
  - Requires authentication
  - Required fields: name

- `DELETE /api/categories/{id}` - Delete a category
  - Requires authentication

### Expenses

- `GET /api/expenses` - List all expenses with optional filtering
  - Requires authentication
  - Optional query parameters:
    - `filter`: past_week, past_month, last_3_months, custom
    - `start_date`: Start date for custom filter (YYYY-MM-DD)
    - `end_date`: End date for custom filter (YYYY-MM-DD)
    - `category_id`: Filter by category ID
    - `sort_by`: Field to sort by (id, description, amount, expense_date, created_at, updated_at)
    - `sort_direction`: asc or desc
    - `per_page`: Number of items per page

- `POST /api/expenses` - Create a new expense
  - Requires authentication
  - Required fields: category_id, description, amount, expense_date
  - Optional fields: notes

- `GET /api/expenses/{id}` - Get a specific expense
  - Requires authentication

- `PUT /api/expenses/{id}` - Update an expense
  - Requires authentication
  - Optional fields: category_id, description, amount, expense_date, notes

- `DELETE /api/expenses/{id}` - Delete an expense
  - Requires authentication

- `GET /api/expenses/summary` - Get expense summary statistics
  - Requires authentication
  - Optional query parameters (same as list expenses)

## Default Categories

The application comes with the following default expense categories:

- Groceries
- Leisure
- Electronics
- Utilities
- Clothing
- Health
- Others

## Authentication

The API uses JWT (JSON Web Token) for authentication. Include the token in your requests:

```
Authorization: Bearer <token>
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
