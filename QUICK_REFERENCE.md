# MIS Project - Quick Reference Guide

## What is This Project?

A **Customer Relationship Management (CRM) & Task Management System** for managing Business Development Representatives (BDRs) and their customer interactions.

---

## What is BDR Presentation?

**BDR = Business Development Representative**

A BDR Presentation typically refers to:

- **Demo Presentations** given by sales staff to potential customers
- Facilitated through the `DemoPresenterController`
- Tracks: who presented, when, to which customer, outcomes
- Used in the sales funnel to move customers toward deals

**In This System:**

- Navigate to **Demo Presenter** section
- Create presentations linked to customers
- Track presentation outcomes
- Improve sales process

---

## Key Modules at a Glance

| Module              | Purpose                | Key Action                                       |
| ------------------- | ---------------------- | ------------------------------------------------ |
| **Customers**       | CRM Database           | Add leads, track interest, schedule follow-ups   |
| **Tasks**           | Work assignments       | Create tasks, assign to staff, track progress    |
| **Employees**       | Team management        | Add staff, assign roles, manage designations     |
| **Clients**         | Business/Shop database | Register shops, track contacts, manage projects  |
| **Demo Presenters** | Sales presentations    | Record presentations given to customers          |
| **Follow-ups**      | Customer engagement    | Schedule next contact dates, record interactions |
| **Dashboard**       | Analytics & overview   | View KPIs, task status, customer distribution    |

---

## User Roles

| Role          | Access                       | Responsibility                            |
| ------------- | ---------------------------- | ----------------------------------------- |
| **Admin**     | Full system                  | Setup, management, reporting              |
| **Staff/BDR** | Customers, Tasks, Follow-ups | Manage assigned customers, complete tasks |
| **Employee**  | Login + limited access       | Task completion, notes                    |

---

## Typical Workflow

### 1. Customer Management

```
Add Customer → Assign to BDR → Schedule Follow-up
→ Record Interaction → Update Status/Interest Level
→ Track Next Follow-up Date
```

### 2. Task Management

```
Create Task → Assign to Employee with Deadline
→ Employee Completes Task → Add Notes
→ Approve/Decline → Archive
```

### 3. BDR Demo Flow

```
Identify Prospect → Give Demo Presentation
→ Record Demo Details → Track Outcome
→ Schedule Follow-up → Close or Nurture
```

---

## Database Tables

### Core Tables

- `customers` - All leads/customers
- `employees` - Team members (BDRs, staff)
- `users` - Admins
- `tasks` - Work items
- `task_assignments` - Task → Employee links
- `clients` - Business/shops
- `designations` - Job titles

### History & Tracking

- `customer_histories` - Customer status changes (auto)
- `customer_assignment_histories` - When assigned to different staff
- `client_status_histories` - Shop status tracking
- `work_sessions` - Time tracking

### Supporting Data

- `areas` - Geographic regions
- `countries` - Countries list
- `business_types` - Business classifications
- `service_types` - Services offered
- `lead_sources` - Where leads come from
- `interest_levels` - Hot/Warm/Cold/etc.

---

## API Quick Reference

### Authentication

```
POST /login                              → Get session
POST /employee/login                     → Employee login with JWT
Bearer token in Authorization header
```

### Core Resources

```
GET/POST    /api/customers               → List/Create customers
GET/PUT     /api/customers/{id}          → View/Update customer
DELETE      /api/customers/{id}          → Delete customer

GET/POST    /api/employees               → Manage employees
GET/PUT     /api/tasks                   → Manage tasks
GET/POST    /api/demo-presenters         → Manage presentations
GET/POST    /api/clients                 → Manage shops/clients
```

### Master Data

```
/api/areas, /api/countries, /api/business-types, /api/service-types,
/api/designations, /api/lead-sources, /api/interest-levels
```

---

## Key Features

### Customer Management

✅ Customer database with details (name, email, business type, country, etc.)  
✅ Assign customers to BDRs  
✅ Track interest levels (Hot, Warm, Cold)  
✅ Schedule follow-up dates  
✅ Record contact history  
✅ Track last contact date  
✅ Automatic status change history

### Task Management

✅ Create/assign tasks with deadlines  
✅ Task status tracking (pending → completed)  
✅ Image attachments  
✅ Notes at each stage  
✅ Task reassignment  
✅ Completion tracking

### Presentation Tracking

✅ Record presentations given to customers  
✅ Track presenter details  
✅ Customer association  
✅ Presentation date/time  
✅ Presentation type tracking

### Business Insights

✅ Dashboard with KPIs  
✅ Task completion rates  
✅ Customer distribution  
✅ Follow-up reminders  
✅ Employee performance

---

## Common Operations

### Add a New Customer

```
1. Go to Customers section
2. Click Add Customer
3. Fill: name, email, business type, country, service type
4. Select interest level
5. Choose BDR/Staff to assign
6. Set next follow-up date
7. Click Save
```

### Create & Assign a Task

```
1. Go to Tasks section
2. Click Create Task
3. Select shop/client
4. Enter title and details
5. Optionally upload image
6. Click Assign to Employee
7. Select employee and deadline
8. Click Assign
```

### Record a Presentation

```
1. Go to Demo Presenters
2. Click New Presentation
3. Select presenter (employee)
4. Select customer/prospect
5. Enter presentation date
6. Add details
7. Save
```

### Track Follow-up

```
1. Open Customer record
2. Review next_follow_up_date
3. Call/email customer on scheduled date
4. Update customer: last_contact_date
5. Add notes: last_discuss_note
6. Set new follow-up date
7. Save
```

---

## Data Fields by Entity

### Customer

- **Basic:** name, designation, email, mobile (via numbers relation)
- **Business:** shop_type, country_name, service_type, company info
- **Sales:** interest_level, lead_source, offer_connect, client_behaviour
- **Engagement:** last_contact_date, next_follow_up_date, last_discuss_note
- **Assignment:** assigned_staff_id (which BDR owns this customer)
- **Status:** status, staff_status

### Task

- **Identification:** title, details, shop_id, shop_name
- **Status:** current status, various status notes (complete_note, decline_note, etc.)
- **Timeline:** start_date, image_path, created_by
- **Tracking:** Multiple status notes at each stage

### Employee

- **Identity:** name, email, mobile, designation
- **Account:** password, role, status
- **Tracking:** Can be assigned tasks and customers

---

## Environment Setup

### .env File Keys

```
APP_NAME=MIS_Project
APP_KEY=base64:...              # Generated via php artisan key:generate
APP_DEBUG=false                 # true in development, false in production

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mis_db
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=your-secret-key      # Set via jwt:secret command

MAIL_FROM_ADDRESS=noreply@example.com
```

---

## Commands

### Laravel

```bash
php artisan migrate              # Run database migrations
php artisan seed                 # Seed sample data
php artisan serve                # Start dev server (localhost:8000)
php artisan tinker               # Interactive CLI
```

### Frontend

```bash
npm run dev                       # Development build with hot reload
npm run build                     # Production build
```

### Testing

```bash
php artisan test                  # Run all tests
```

### Code Quality

```bash
php artisan pint                  # Fix PHP code style issues
```

---

## Important File Paths

```
├── app/Http/Controllers/Backend/
│   ├── CustomerController.php         ⭐ Main CRM logic
│   ├── TaskController.php
│   ├── DemoPresenterController.php    ⭐ Presentation tracking
│   ├── EmployeeController.php
│   └── ...
├── app/Models/
│   ├── Customer.php                   ⭐ Customer model
│   ├── Task.php
│   ├── Employee.php
│   └── ...
├── routes/
│   ├── api.php                        ⭐ API routes
│   └── web.php
├── resources/js/                      React/Vue components
└── vite.config.ts                     Frontend build config
```

---

## Troubleshooting

### Issue: Can't login

- **Check:** Verify employee/user exists in database
- **Solution:** Use `php artisan tinker` to create user if needed

### Issue: Tasks not showing

- **Check:** Verify task assignments exist
- **Solution:** Create task assignments via API or database

### Issue: API 404 errors

- **Check:** Verify routes in `routes/api.php`
- **Solution:** Clear route cache: `php artisan route:cache`

### Issue: Files not uploading

- **Check:** `/public/uploads/` directory exists
- **Solution:** Run `php artisan storage:link`

---

## Business KPIs to Track

1. **Customer Metrics**
    - Total customers in system
    - By interest level distribution
    - By status distribution
    - By assigned BDR

2. **Engagement Metrics**
    - Days since last contact
    - Upcoming follow-ups (30 days)
    - Customer converted to deal
    - Follow-up adherence rate

3. **Task Metrics**
    - On-time completion %
    - Tasks by status
    - Tasks per employee
    - Days in progress

4. **BDR Performance**
    - Customers per BDR
    - Tasks completed
    - Customer conversion rate
    - Average response time

---

## Tips & Best Practices

✅ **Always assign customers to BDRs** - Keeps responsibility clear  
✅ **Set follow-up dates** - Prevents customers from slipping  
✅ **Add notes** - Record why you changed status  
✅ **Use interest levels** - Hot prospects get priority  
✅ **Complete tasks on time** - Use deadline tracking  
✅ **Record interactions** - Every contact should be logged  
✅ **Use demo presentations** - Track all pitches given  
✅ **Review dashboard daily** - Stay on top of metrics

---

**More Info:** See `PROJECT_DOCUMENTATION.md` for complete technical documentation
