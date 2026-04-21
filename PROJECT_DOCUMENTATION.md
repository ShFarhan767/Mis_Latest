# MIS Project - Complete Documentation

## Project Overview

**Project Name:** MIS_Project (Management Information System)  
**Framework:** Laravel 12 + Vue.js (Inertia.js)  
**Database:** MySQL/MariaDB  
**Authentication:** JWT + Laravel Sanctum + Fortify  
**Testing:** Pest PHP

This is a comprehensive management system designed to manage customer relationships, task assignments, employee management, and business development activities. It's built with a modern tech stack and follows Laravel best practices.

---

## Project Purpose & Core Use Case

The MIS Project is primarily designed for **Business Development Representative (BDR) Management** and **Customer Relationship Management (CRM)**. It helps organizations:

1. Track and manage customer/client interactions
2. Assign and monitor tasks across teams
3. Manage employee/staff information
4. Track customer follow-ups and engagement
5. Monitor customer status and history
6. Track offers and connections with clients
7. Generate dashboards for business insights

---

## What is BDR (Business Development Representative)?

**BDR = Business Development Representative**

A BDR is a professional responsible for:

- **Lead Generation**: Finding and qualifying new business opportunities
- **Customer Outreach**: Contacting prospects and presenting solutions
- **Relationship Building**: Establishing and maintaining client relationships
- **Follow-ups**: Scheduling and tracking customer follow-ups
- **Data Management**: Maintaining accurate customer information and notes
- **Deal Progression**: Moving customers through the sales pipeline

**In this MIS Project:**

- BDRs/Staff manage customers assigned to them
- Track every interaction and follow-up date
- Monitor customer interest levels and status
- Generate presentations (Demo Presenter functionality)
- Record notes and communication history

---

## System Architecture

### Technology Stack

```
Backend:
├── Laravel 12.0 (PHP Framework)
├── Laravel Fortify (Authentication)
├── Laravel Sanctum (API Tokens)
├── JWT-Auth (Token-based authentication)
├── Inertia.js (Server-side rendering)
└── MySQL Database

Frontend:
├── Vue.js 3 (via Inertia)
├── Vite (Build tool)
├── ESLint (Code quality)
└── TypeScript

Additional Package:
├── Pusher (Real-time notifications - optional)
├── Laravel Pail (Logging)
└── Pest PHP (Testing)
```

### Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/              # API controllers
│   │   ├── Auth/             # Authentication
│   │   ├── Backend/          # Main business logic controllers
│   │   └── Settings/         # Settings controllers
│   ├── Middleware/           # Authentication & authorization
│   └── Requests/             # Form request validation
├── Models/                   # Eloquent models
├── Mail/                     # Mail templates
├── Observers/                # Model observers
├── Repositories/             # Data access layer
├── Services/                 # Business logic services
└── Providers/                # Service providers

routes/
├── api.php                   # API routes
├── web.php                   # Web routes
├── auth.php                  # Auth routes
├── settings.php              # Settings routes
└── console.php               # Artisan commands

resources/
├── js/                       # Vue components
├── css/                      # Stylesheets
└── views/                    # Blade templates

database/
├── migrations/               # Database schema
├── seeders/                  # Database seeders
└── factories/                # Model factories for testing

tests/
├── Feature/                  # Feature tests
└── Unit/                     # Unit tests

config/
├── app.php                   # Application config
├── auth.php                  # Auth configuration
├── database.php              # Database config
├── jwt.php                   # JWT configuration
├── mail.php                  # Mail configuration
└── ...                       # Other configs
```

---

## Core Models & Database Schema

### 1. **User Model**

- Main authentication user (admin/manager level)
- Roles: admin, staff, employee
- Two-factor authentication support
- Related to: Tasks, Customers, Employees

### 2. **Employee Model**

- Staff/BDR information
- Extends Laravel Authenticatable
- Contains: name, email, mobile, designation, password, role, status
- Can be assigned tasks and customers

### 3. **Customer Model** ⭐ (Main CRM Entity)

Properties:

- Personal: name, designation, email
- Business: shop_type, country_name, service_type
- Sales: interest_level, lead_source, offer_connect
- Status: customer status, staff status, last_contact_date, next_follow_up_date
- Notes: last_discuss_note, feature_need, our_commitment
- Relationships: assigned_staff_id (assigned BDR)

Related Models:

- `CustomerNumber`: Phone numbers
- `CustomerHistory`: Track status changes
- `CustomerAssignHistory`: Assignment changes
- `CustomerStaffStatusHistory`: Status transitions
- `CustomerObserver`: Automatic logging of changes

### 4. **Client Model**

- Business/shop clients (different from Customer)
- Contains: name, company_name, operator_name, contact details
- Relationships: businessType, tasks, notes, statuses

### 5. **Task Model**

- Operational tasks assigned to dates and shops
- Status: pending, assigned, in_progress, completed, declined, approved, cancelled
- Contains: title, details, image, notes for different statuses
- Relationships: taskAssignments, shop, creator

### 6. **TaskAssignment Model**

- Links tasks to employees with deadlines
- Tracks: task_id, employee_id, deadline, completion status

### 7. **Master Data Models**

- `Area`: Geographic areas/regions
- `Country`: Country list
- `BusinessType`: Business classification
- `ShopType`: Shop/business type
- `ServiceType`: Services offered
- `Designation`: Job titles
- `LeadSource`: Where leads come from
- `InterestLevel`: Customer interest (Hot, Warm, Cold, etc.)
- `WorkSession`: Time tracking/sessions

### 8. **Relationship & History Models**

- `ClientNote`: Notes on clients
- `ClientStatusHistory`: Track status changes
- `ClientOperatorHistory`: Operator changes
- `ClientTimeline`: Activity timeline
- `TaskNote`: Task-specific notes
- `CustomerNote` (via Observer)

### 9. **Configuration Models**

- `Logo`, `HeaderLogo`: Branding
- `SmsApiInfo`: SMS API configuration
- `PasswordOtp`: OTP for password reset
- `OfferConnect`: Offers and connections tracking

---

## Core Features & Functionality

### 🔐 Authentication & Authorization

**Routes:**

- `routes/auth.php` - Handle registration, login, password reset
- Uses JWT tokens + Sanctum
- Role-based access control (Admin, Staff)

**Middleware:**

- `AdminOrStaffMiddleware`: Checks user is admin or staff
- Auth middleware for protected routes

**Features:**

- Email/password login
- Two-factor authentication (via Fortify)
- Password reset via OTP
- Remember me functionality
- Session management

### 👥 User & Employee Management

**Controllers:**

- `EmployeeController` - CRUD operations for employees
- `StaffController` - Staff-specific operations

**Features:**

- Create/edit/delete employees
- Assign roles and designations
- Track employee status
- Employee authentication via /employee/login

**API Endpoints:**

```
GET/POST   /api/employees
GET/PUT    /api/employees/{id}
DELETE     /api/employees/{id}
```

### 👤 Customer/Lead Management (CRM Core)

**Controller:** `CustomerController`

**Features:**

- Complete customer database with details
- Customer assignment to BDRs/staff
- Track interest levels (Hot, Warm, Cold)
- Lead source tracking
- Service type preferences
- Follow-up date management
- Contact history
- Status transitions

**Customer Fields:**

- Basic: name, designation, email
- Business: shop_type, country, service_type
- Sales: interest_level, lead_source, offer_connect
- Engagement: last_contact_date, next_follow_up_date, last_discuss_note
- Assignment: assigned_staff_id (BDR assignment)

**API Endpoints:**

```
GET/POST   /api/customers
GET/PUT    /api/customers/{id}
DELETE     /api/customers/{id}
GET        /api/customer-history/{id}
POST       /api/customer-assign/{id}
```

### 🏢 Client/Shop Management

**Controller:** `ClientController`

**Features:**

- Shop/client information management
- Business type classification
- Contact numbers and operators
- Project tracking
- Associated tasks
- Status history
- Referral tracking

**API Endpoints:**

```
GET/POST   /api/clients
GET/PUT    /api/clients/{id}
DELETE     /api/clients/{id}
```

### 📋 Task Management System

**Controllers:**

- `TaskController` - Task CRUD
- `TaskAssignmentController` - Assignment logic
- `TaskNoteController` - Task notes

**Task Lifecycle:**

```
Created → Assigned → In Progress → (Completed/Declined/Approved/Cancelled)
```

**Task Status & Notes:**

- `status`: Current task status
- `complete_note`: Notes on completion
- `decline_note`: Reason for decline
- `approve_note`: Approval comments
- `reissue_comment`: Reissue details
- `cancelled_note`: Cancellation reason

**Features:**

- Image attachments
- Deadline tracking
- Assignment to staff with deadline
- Status progression
- Timeline and history
- Notes at each stage

**API Endpoints:**

```
GET/POST   /api/tasks
GET/PUT    /api/tasks/{id}
DELETE     /api/tasks/{id}
GET/POST   /api/task-assignments
PUT        /api/task-assignments/{id}
```

### 🎤 Demo Presenter Management

**Controller:** `DemoPresenterController`

**Purpose:** Manage demos/presentations given to customers

**Features:**

- Create presentations
- Track presenter (employee)
- Date and customer association
- Presentation details

**API Endpoints:**

```
GET/POST   /api/demo-presenters
GET/PUT    /api/demo-presenters/{id}
DELETE     /api/demo-presenters/{id}
```

### 📊 Dashboard & Analytics

**Controller:** `DashboardController`

**Dashboard Features:**

- Overview of tasks and assignments
- Customer statistics
- Follow-up reminders
- Employee performance metrics
- Task completion rates
- Customer status distribution

### 🏷️ Master Data Management

**Controllers (with corresponding APIs):**

- `AreaController` - Geographic areas
- `CountryController` - Countries
- `BusinessTypeController` - Business classifications
- `ShopTypeController` - Shop types
- `ServiceTypeController` - Service offerings
- `DesignationController` - Job titles
- `LeadSourceController` - Lead sources
- `InterestLevelController` - Interest levels

**Purpose:** Maintain standard dropdowns and references

**API Pattern:**

```
GET/POST   /api/{resource}
GET/PUT    /api/{resource}/{id}
DELETE     /api/{resource}/{id}
```

### 📝 Notes & History Management

**Controllers:**

- `ClientNoteController` - Notes on clients
- `TaskNoteController` - Task-specific notes
- `CustomerHistoryController` - Customer change history

**Features:**

- Record interactions
- Track history automatically (via Observer)
- Maintain audit trail

### 🔧 Other Features

**Logo Management:** `LogoController`, `HeaderLogoController`

- Upload and manage company logos

**SMS API Config:** `SmsApiInfo` model

- Configure SMS service for notifications

**Offer Connect:**

- Track offers made to customers
- Connection monitoring

---

## API Overview

### Base URL

```
/api/
```

### Authentication

- Uses JWT tokens
- Include `Authorization: Bearer {token}` header
- Get token from login endpoint

### Main API Groups

| Resource         | Endpoints                                             |
| ---------------- | ----------------------------------------------------- |
| Employees        | /api/employees                                        |
| Customers        | /api/customers                                        |
| Clients          | /api/clients                                          |
| Tasks            | /api/tasks                                            |
| Task Assignments | /api/task-assignments                                 |
| Task Notes       | /api/task-notes                                       |
| Client Notes     | /api/client-notes                                     |
| Demo Presenters  | /api/demo-presenters                                  |
| Master Data      | /api/areas, /api/countries, /api/business-types, etc. |

### Response Format

```json
{
    "data": {
        /* resource data */
    },
    "message": "Success message",
    "status": true
}
```

---

## Authentication Flow

### Employee Login

```
Route: POST /employee/login
Input: email, password
Output: JWT token + employee data
```

### User Login

```
Route: POST /login (via Fortify)
Input: email, password
Output: Session + authenticated user
```

### API Authentication

```
Header: Authorization: Bearer {jwt_token}
Scope: Sanctum API tokens
Expires: Configurable (default: none)
```

---

## Database Relationships

### Key Relationships

```
User (admin)
├── Many Tasks (created_by)
└── Many Customers (created_by)

Employee (BDR/Staff)
├── Many Tasks (via TaskAssignment)
├── Many Customers (assigned_staff_id)
└── Many WorkSessions

Customer (CRM Lead)
├── One Designation
├── Many CustomerNumbers
├── Many CustomerNotes
├── Many CustomerHistory
├── One Employee (assigned_staff_id) - assigned BDR
├── Many CustomerAssignHistory
└── Many CustomerStaffStatusHistory

Client (Shop)
├── One BusinessType
├── Many Tasks
├── Many ClientNotes
├── Many ClientStatusHistories
└── Many ClientOperatorHistories

Task
├── One User (creator)
├── One Client (shop)
├── Many TaskAssignments
└── Many TaskNotes

TaskAssignment
├── One Task
├── One Employee (assigned_to)
└── Tracks deadline & status
```

---

## File Upload & Storage

**Upload Directories:**

- `/public/uploads/` - General file uploads
- `/public/images/` - Image files
- `/storage/app/` - Private storage

**Supported in:**

- Task images
- Logo uploads
- Document attachments

---

## Configuration Files

| File                  | Purpose                                    |
| --------------------- | ------------------------------------------ |
| `.env`                | Environment variables (API keys, DB, etc.) |
| `config/app.php`      | App name, timezone, locale                 |
| `config/auth.php`     | Auth guards (web, api)                     |
| `config/database.php` | Database connection                        |
| `config/jwt.php`      | JWT token configuration                    |
| `config/mail.php`     | Email service config                       |
| `config/queue.php`    | Queue job configuration                    |
| `config/cache.php`    | Caching strategy                           |

---

## Mail System

**Mail Class:**

- `OtpMail` - OTP for password reset

**SMTP Configuration:**

- Configured in `.env` and `config/mail.php`
- Supports: SMTP, Mailgun, SendGrid, etc.

---

## Testing Framework

**Tool:** Pest PHP

**Test Structure:**

```
tests/
├── Feature/           # Feature/integration tests
├── Unit/              # Unit tests
├── Pest.php           # Pest configuration
└── TestCase.php       # Base test class
```

**Commands:**

```bash
php artisan test                    # Run all tests
php artisan test --filter=TestName # Run specific test
php artisan test Feature            # Run feature tests only
```

---

## Development Workflow

### Setup

```bash
composer install
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

### Development Server

```bash
php artisan serve          # Laravel server
npm run dev                 # Vite dev server
php artisan tinker          # Interactive shell
```

### Database

```bash
php artisan migrate         # Run migrations
php artisan seed            # Run seeders
php artisan migrate:fresh   # Reset database
php artisan tinker          # Database shell
```

### Code Quality

```bash
php artisan pint            # Fix PHP code style
npx eslint .                # Check JS code style
php artisan test            # Run tests
```

---

## Security Features

1. **JWT Authentication** - Token-based API auth
2. **CSRF Protection** - Cross-site request forgery
3. **Two-Factor Authentication** - Via Laravel Fortify
4. **Password Hashing** - Bcrypt hashing
5. **Middleware** - Role-based access control
6. **Validation** - Form request validation
7. **Rate Limiting** - API rate limiting (configurable)
8. **Encryption** - Encrypted sensitive data

---

## Deployment

### Environment Setup

1. Copy `.env.example` to `.env`
2. Set `APP_KEY` via `php artisan key:generate`
3. Configure database credentials
4. Set JWT_SECRET
5. Configure mail service

### Production Build

```bash
composer install --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

### Server Requirements

- PHP 8.2+
- MySQL 8.0+
- Node.js 18+
- Composer
- Extensions: fileinfo, PDO, TokenReflection, MBString

---

## Performance Optimization

1. **Eager Loading** - Use with() to prevent N+1 queries
2. **Caching** - Implement query caching
3. **Indexing** - Index foreign keys and frequently searched fields
4. **Pagination** - Use paginate() for large datasets
5. **Database Queries** - Use select() to fetch only needed columns
6. **Frontend** - Lazy load components via Vue/Inertia

---

## Common Code Patterns

### Query with Relations

```php
Customer::with(['numbers', 'designation'])
    ->where('status', 'active')
    ->paginate(15);
```

### Create with Relations

```php
Task::create([
    'shop_id' => $shopId,
    'title' => 'New Task',
    'status' => 'pending'
]);

TaskAssignment::create([
    'task_id' => $task->id,
    'assigned_to' => $employeeId,
    'deadline' => now()->addDays(3)
]);
```

### Update Status

```php
$customer->update([
    'status' => 'interested',
    'next_follow_up_date' => now()->addDays(7),
    'last_contact_date' => now()
]);
```

### Observer Pattern (Auto-logging)

```php
// CustomerObserver watches all Customer changes
// Automatically creates history entries
```

---

## Troubleshooting

### Common Issues

**Authentication Issues:**

- Check JWT_SECRET in .env
- Verify token expiration
- Clear cache: `php artisan cache:clear`

**Database Issues:**

- Verify .env database credentials
- Check migrations: `php artisan migrate:status`
- Run seeders: `php artisan seed`

**API Issues:**

- Check CORS configuration
- Verify middleware stack
- Review request validation

**File Upload Issues:**

- Check `/public/uploads/` permissions
- Verify storage symlink: `php artisan storage:link`
- Check file size limits in php.ini

---

## Future Enhancements

Potential features:

- SMS notifications for follow-ups
- Email alerts
- Advanced reporting & analytics
- Mobile app version
- Real-time collaboration (Pusher integration)
- Bulk import/export
- Advanced customer segmentation
- AI-powered lead scoring
- Calendar & scheduling

---

## Support & Documentation

- **Laravel Docs:** https://laravel.com/docs
- **Inertia.js:** https://inertiajs.com
- **Vue.js:** https://vuejs.org
- **JWT-Auth:** https://jwt-auth.readthedocs.io

---

**Last Updated:** April 2026  
**Project Status:** Active Development
