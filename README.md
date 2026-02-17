<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Sanctum-Auth-38B2AC?style=for-the-badge&logo=laravel&logoColor=white" alt="Sanctum">
</p>

# 🚀 TaskFlow API

A production-ready RESTful API for task and project management, built with Laravel 12. Features token-based authentication, role-based access control (RBAC) with granular permissions, comprehensive test coverage, and interactive API documentation.

<p align="center">
  <img src="https://img.shields.io/badge/tests-57%20passing-brightgreen?style=flat-square" alt="Tests">
  <img src="https://img.shields.io/badge/coverage->80%25-brightgreen?style=flat-square" alt="Coverage">
  <img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="License">
  <img src="https://img.shields.io/badge/API-documented-orange?style=flat-square" alt="API Docs">
</p>

---

## ✨ Features

- **Authentication** — Register, login, logout, and profile retrieval with Laravel Sanctum tokens
- **Projects CRUD** — Create, read, update, and delete projects with ownership protection
- **Tasks CRUD** — Nested task management within projects, with global cross-project listing
- **Role-Based Access Control** — Three roles (admin, manager, member) with 10 granular permissions via Spatie Permission
- **Filtering & Pagination** — Filter tasks by status, priority, and assignee; paginated responses
- **Rate Limiting** — Configurable throttling for API and auth endpoints (brute-force protection)
- **API Documentation** — Interactive Swagger/OpenAPI docs at `/api/documentation`
- **Comprehensive Testing** — 60+ feature tests covering auth, CRUD, authorization, and validation
- **CI/CD Pipeline** — GitHub Actions running tests automatically on every push

---

## 🏗️ Architecture

This project follows a **Service Layer** architecture pattern, keeping controllers thin and business logic decoupled.

```
Request → Routes → Middleware → Controller → Form Request (validation)
                                    ↓
                              Service Layer (business logic)
                                    ↓
                              Eloquent Model (database)
                                    ↓
                              API Resource (response transformation)
                                    ↓
                              JSON Response
```

### Design Patterns Used

| Pattern | Where | Purpose |
|---------|-------|---------|
| Service Layer | `app/Services/` | Encapsulate business logic outside controllers |
| Form Request | `app/Http/Requests/` | Validate and authorize incoming data |
| API Resource | `app/Http/Resources/` | Transform models into consistent JSON responses |
| Policy | `app/Policies/` | Two-level authorization (permission + ownership) |
| Factory | `database/factories/` | Generate test data with expressive state methods |

### Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/V1/     # Thin controllers (max 10 lines per method)
│   ├── Requests/Api/V1/        # Validation & authorization
│   └── Resources/Api/V1/       # Response transformations
├── Models/                     # Eloquent models with relationships
├── Policies/                   # Permission + ownership checks
└── Services/                   # Business logic layer

tests/
├── Feature/Api/V1/
│   ├── Auth/                   # 14 authentication tests
│   ├── Project/                # 19 project CRUD tests
│   └── Task/                   # 27 task CRUD tests
└── Unit/
```

---

## 🔌 API Endpoints

All endpoints are prefixed with `/api/v1`.

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `POST` | `/auth/register` | Register a new user | No |
| `POST` | `/auth/login` | Login and get token | No |
| `POST` | `/auth/logout` | Revoke current token | Yes |
| `GET` | `/auth/me` | Get user profile | Yes |

### Projects

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/projects` | List projects (paginated, filterable) | Yes |
| `POST` | `/projects` | Create a project | Yes |
| `GET` | `/projects/{id}` | Get project details | Yes |
| `PUT` | `/projects/{id}` | Update a project | Yes |
| `DELETE` | `/projects/{id}` | Delete a project | Yes |

### Tasks

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/projects/{id}/tasks` | List project tasks | Yes |
| `POST` | `/projects/{id}/tasks` | Create a task | Yes |
| `GET` | `/projects/{id}/tasks/{taskId}` | Get task details | Yes |
| `PUT` | `/projects/{id}/tasks/{taskId}` | Update a task | Yes |
| `DELETE` | `/projects/{id}/tasks/{taskId}` | Delete a task | Yes |
| `GET` | `/tasks` | List all tasks (global, filterable) | Yes |

### Filters

```
GET /api/v1/tasks?status=todo&priority=high&assigned_to=1
GET /api/v1/projects?status=active
```

> 📖 Full interactive documentation available at `/api/documentation` when running the app.

---

## 🔐 Roles & Permissions

Built with [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) for granular access control.

| Permission | Member | Manager | Admin |
|-----------|--------|---------|-------|
| View projects | ✅ | ✅ | ✅ |
| Create projects | ❌ | ✅ | ✅ |
| Update projects | ❌ | ✅ | ✅ |
| Delete projects | ❌ | ❌ | ✅ |
| View tasks | ✅ | ✅ | ✅ |
| Create tasks | ✅ | ✅ | ✅ |
| Update tasks | ✅ | ✅ | ✅ |
| Delete tasks | ❌ | ✅ | ✅ |
| Assign tasks | ❌ | ✅ | ✅ |
| Manage users | ❌ | ❌ | ✅ |

> Authorization is two-level: permission check **+** resource ownership check.

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.2+
- Composer 2.x
- MySQL 8.0+

### Installation

```bash
# Clone the repository
git clone https://github.com/alexgtzs/taskflow-api.git
cd taskflow-api

# Install dependencies
composer install

# Set up environment
cp .env.example .env
php artisan key:generate

# Configure your database in .env
# DB_CONNECTION=mysql
# DB_DATABASE=taskflow_api
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Run migrations and seed roles/permissions
php artisan migrate --seed

# Install API routes and Sanctum
php artisan install:api

# Start the development server
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api/v1`.

### Quick Test

```bash
# Register a user
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123","password_confirmation":"password123"}'

# Use the returned token for authenticated requests
curl http://127.0.0.1:8000/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --filter=AuthenticationTest
php artisan test --filter=ProjectCrudTest
php artisan test --filter=TaskCrudTest
```

### Test Coverage

| Suite | Tests | Covers |
|-------|-------|--------|
| Authentication | 14 | Register, login, logout, profile, validation |
| Projects CRUD | 18 | CRUD, ownership, permissions, filters, pagination |
| Tasks CRUD | 25 | CRUD, nested routes, filters, assignment, permissions |
| **Total** | **57** | **Auth, CRUD, authorization, validation, edge cases** |

---

## 🛠️ Tech Stack

| Technology | Purpose |
|-----------|---------|
| [Laravel 12](https://laravel.com/) | PHP framework |
| [Laravel Sanctum](https://laravel.com/docs/sanctum) | API token authentication |
| [Spatie Permission](https://spatie.be/docs/laravel-permission) | Role-based access control |
| [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) | OpenAPI documentation |
| [Laravel Pint](https://laravel.com/docs/pint) | Code style enforcement |
| [PHPUnit](https://phpunit.de/) | Testing framework |
| [GitHub Actions](https://github.com/features/actions) | CI/CD pipeline |

---

## 📄 License

This project is open-sourced software licensed under the [MIT License](LICENSE).
