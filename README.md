# Student Portal

A web-based student management system for colleges, training centres and tuition centres, built with Laravel 13, Blade and plain CSS. It covers student records, subject registration, marks with teacher approval, GPA/CGPA, transcripts, fees with printed receipts, and seven user roles. The super admin can rebrand the whole system (logo, colours, font) without touching code.

![Dashboard](docs/screenshots/dashboard.png)

## Roles

Each person sees only the menus, pages and buttons their role allows.

| Role | Can do |
|---|---|
| **Super admin** | Everything, plus branding, user accounts and changing marks directly |
| **Admin staff** | View students, teachers and subjects; edit students' personal and contact details. Cannot see results |
| **Head of department** | Assign teachers to subjects, see all results, request mark changes (the subject teacher must approve) |
| **Registrar** | Add and edit students, register them for subjects, see results (read-only), print transcripts, reset student passwords |
| **Teacher** | Enter marks for their own subjects; approve or reject mark change requests |
| **Accountant** | Bill fees (one student or a whole programme), record payments, print receipts, see who owes money |
| **Student** | See their own subjects, results and transcript |

All permissions live in one file, `config/roles.php`, so they can be adjusted for each client.

## Features

- **Student records:** search, filters, Excel (CSV) export, and a profile page with GPA per semester and CGPA.
- **Automatic student logins:** adding a student creates their login. The first password is their student number, and they must change it at first login.
- **Subject registration:** the registrar registers students for subjects each semester, and the subject's teacher enters the marks.
- **Mark change approval:** a head of department's change takes effect only after the subject teacher approves it, and every request and decision is kept.
- **Transcripts and receipts:** A4 layouts you can print or save as PDF, carrying the institution's logo and name.
- **Fees:**
  - running statements per student
  - balances showing amount owing or credit
  - one-click billing for a whole programme (safe to run twice)
  - receipt numbers in the format RCP-2026-00001
- **Branding page:** logo upload, portal and institution names, three colours with a live preview, and a choice of eight fonts. Colours too light for white text are refused.
- **Security:**
  - role checks on every page and action
  - login rate limiting per email address
  - hashed passwords and CSRF protection
  - validation on every form
  - result URLs scoped to their student
- **Responsive:** works on phones and tablets.
- **Automated tests:** 71 feature tests, including a check that every role can open only its own pages.

| Mark change approval | Fees and payments |
|---|---|
| ![Mark changes](docs/screenshots/mark-changes.png) | ![Fees](docs/screenshots/fees.png) |

| Student record | Official receipt |
|---|---|
| ![Student record](docs/screenshots/student.png) | ![Receipt](docs/screenshots/receipt.png) |

| Branding (super admin) | Printable transcript |
|---|---|
| ![Branding](docs/screenshots/branding.png) | ![Transcript](docs/screenshots/transcript.png) |

## Tech stack

- PHP 8.3+ and Laravel 13
- Blade templates and components, with plain CSS (no build step needed)
- SQLite by default; works with MySQL or MariaDB
- PHPUnit for tests

## Getting started

```bash
git clone https://github.com/<your-username>/student-portal.git
cd student-portal
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed      # optional: sample data and a demo login for every role
```

With [Laravel Herd](https://herd.laravel.com), put the folder in your Herd directory and open `http://student-portal.test`. Without Herd, run `php artisan serve` and open `http://127.0.0.1:8000`.

### Demo accounts (after `db:seed`)

All staff passwords are `password`.

| Role | Email |
|---|---|
| Super admin | `admin@example.com` |
| Admin staff | `adminstaff@example.com` |
| Head of department | `hod@example.com` |
| Registrar | `registrar@example.com` |
| Teacher | `teacher@example.com` (also `teacher2@`, `teacher3@`) |
| Accountant | `accountant@example.com` |
| Student | `dcs2024001@student.example.com`, password `DCS2024001` (asked to choose a new one) |

Change or delete these accounts before putting the system online.

## Customising for a client

| What | Where |
|---|---|
| Logo, portal name, institution name, colours, font | Log in as super admin, then go to **Branding** |
| What each role can do | `config/roles.php` |
| Grading scale (marks, grades, grade points) | `config/grading.php` |
| Currency shown on fees and receipts | `.env`: `PORTAL_CURRENCY="RM"` |
| Font choices offered on the Branding page | `config/portal.php` |
| Student statuses, genders, programme levels, payment methods | the constants in `app/Models/Student.php`, `Programme.php` and `Payment.php` |

## Using MySQL instead of SQLite

Create an empty database, then update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=student_portal
DB_USERNAME=root
DB_PASSWORD=
```

Then run `php artisan migrate --seed`.

## Running the tests

```bash
php artisan test
```

## Where things are

```
app/Http/Controllers/   One controller per area: students, registrations, marks, mark changes,
                        teachers, subjects, programmes, finance, payments, charges, billing,
                        users, branding, profile, dashboard
app/Models/             Student, Result, Subject, Programme, User, Charge, Payment,
                        ResultChangeRequest, Setting
app/Support/            Grading (GPA/CGPA), Branding (logo, colours, font), Color (contrast checks)
config/roles.php        Roles and permissions
config/grading.php      The grading scale
config/portal.php       Default names, colours, fonts and currency
database/migrations/    Table definitions
database/seeders/       Sample data and demo accounts
resources/views/        Blade templates (layouts, components and one folder per area)
public/css/app.css      All styles
public/uploads/         Uploaded logo (not committed to Git)
routes/web.php          All URLs and who can open them
tests/Feature/          Automated tests
```
