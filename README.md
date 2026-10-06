# Student Portal

A web-based student management system for colleges, training centres and tuition centres. It is built with Laravel 13, Blade and plain CSS. Staff can keep student records, record subject results, and print academic transcripts, with GPA and CGPA worked out automatically.

![Dashboard](docs/screenshots/dashboard.png)

## Features

- **Dashboard:** student totals, students per programme, status breakdown, grade distribution, top students by CGPA, and recently added students.
- **Students:**
  - add, edit and delete students
  - search by name, student number or email
  - filter by programme and status
  - export the list to Excel (CSV)
- **Student record:** a profile page with results grouped by semester, semester GPA and cumulative CGPA.
- **Results:** enter marks from 0 to 100. The grade and grade point come from a configurable grading scale.
- **Printable transcript:** a clean A4 layout you can print or save as PDF from the browser.
- **Programmes and subjects:** manage courses and subjects with credit hours. The system blocks deleting records that are still in use.
- **Staff accounts with roles:** admins manage accounts; staff manage academic records. Public sign-up is disabled.
- **My profile:** each user can update their details and change their password.
- **Security:**
  - login rate limiting
  - hashed passwords
  - CSRF protection
  - validation on every form
  - result URLs scoped to their student
- **Responsive layout** that works on phones and tablets.
- **Automated tests:** 38 feature tests covering every module.

| Student record | Printable transcript |
|---|---|
| ![Student record](docs/screenshots/student.png) | ![Transcript](docs/screenshots/transcript.png) |

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
php artisan db:seed      # optional: sample programmes, subjects and 36 students
```

With [Laravel Herd](https://herd.laravel.com), put the folder in your Herd directory and open `http://student-portal.test`. Without Herd, run `php artisan serve` and open `http://127.0.0.1:8000`.

### Demo accounts (after `db:seed`)

| Role  | Email               | Password   |
|-------|---------------------|------------|
| Admin | `admin@example.com` | `password` |
| Staff | `staff@example.com` | `password` |

Change these passwords before putting the system online.

## Customising for a client

| What | Where |
|---|---|
| Portal name and the institution name on transcripts | `.env`: `PORTAL_NAME="Student Portal"` and `INSTITUTION_NAME="Kolej Teknologi Melaka"` |
| Grading scale (marks, grades, grade points) | `config/grading.php` |
| Colours and fonts | the variables at the top of `public/css/app.css` |
| Student statuses, genders and programme levels | the constants in `app/Models/Student.php` and `app/Models/Programme.php` |

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
app/Http/Controllers/   Student, Result, Programme, Subject, User, Profile, Dashboard, Auth
app/Models/             Student, Result, Programme, Subject, User
app/Support/Grading.php Marks to grade, GPA and CGPA calculations
config/grading.php      The grading scale
config/portal.php       Portal and institution names
database/migrations/    Table definitions
database/seeders/       Sample data
resources/views/        Blade templates (layouts, components and one folder per module)
public/css/app.css      All styles
routes/web.php          All URLs
tests/Feature/          Automated tests
```
