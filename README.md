# Real Estate Management System (Laravel) - Multi-Tenant SaaS

## Description
This is a **multi-tenant Real Estate Management System** built with Laravel, designed for companies to manage their properties, units, tenants, and financial operations. It is suitable for SaaS deployment and can serve multiple companies on the same platform with isolated data.

The system includes:

- Multi-tenant architecture (separate data per company)
- Full property & unit management
- Unit features & amenities tracking
- Tenant management
- Rental requests, approvals, and bookings
- Lease management with start/end dates, rent, deposit, and status tracking
- Payment management with status tracking (pending, paid, refunded, etc.)
- Maintenance requests & issue tracking
- Accounting module (Accounts, Journal Entries, Expenses)
- Internal conversations (chat system) & notifications
- Role-based access (admin, owner, tenant, employee)
- Multi-language & responsive design ready

---

## Key Features

### Properties & Units
- Create, edit, delete properties
- Add multiple units per property
- Track unit details: type, rent price, status (available, occupied, maintenance)
- Upload multiple images per unit
- Define unit features/amenities (e.g., pool, balcony, parking)

### Tenants & Leasing
- Tenants can register and request rentals
- Lease management with start/end dates
- Track rent amount, deposit, and status
- Multi-tenant support for company isolation
- Notifications to tenants & company staff

### Payments & Accounting
- Record payments with method and status
- Automatic linking of payments to leases
- Accounts, journal entries, and journal items for full accounting
- Expense tracking
- Reports on income, expenses, and outstanding payments

### Maintenance & Requests
- Tenants can submit maintenance or repair requests
- Track request status (pending, in progress, completed)
- Assign staff to requests and add notes

### Communication & Notifications
- Internal chat between users within the same company
- Multi-user conversations
- Real-time notifications for messages, lease updates, payments, and maintenance

### Admin & Roles
- Admin, Owner, Employee, Tenant roles
- Role-based access control
- Multi-tenant architecture ensures data isolation per company

---

## Tech Stack
- Laravel 10 (PHP 8.x)
- MySQL / PostgreSQL
- Livewire + Blade templates
- Git & GitHub
- Optional: Railway / Render for free hosting during testing

---

## Directory Structure

