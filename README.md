# Task Management Backend (WAMP + CodeIgniter 4)

This backend is now a full CodeIgniter 4 project inside:
`C:\wamp64\www\Task_management\backend`

It is configured for WAMP (Apache + MySQL + PHP).

## Implemented
- JWT authentication APIs
- RBAC permission filters
- Users / Departments / Tasks / Comments / Notifications APIs
- Task assignment + status history + deadline history
- Attachment upload validation (stored in `writable/uploads/task_attachments`)
- Activity logs
- Daily reminder command (`task:send-reminders`)
- MySQL migration + seeder

## WAMP URLs
- Backend base URL: `http://localhost/Task_management/backend/public/`
- API base URL: `http://localhost/Task_management/backend/public/api`

## Local Runtime (verified)
Use WAMP PHP directly:
```bash
C:\wamp64\bin\php\php8.2.29\php.exe spark --version
```

## Database Setup (already tested)
```bash
C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS task_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\wamp64\bin\php\php8.2.29\php.exe spark migrate
C:\wamp64\bin\php\php8.2.29\php.exe spark db:seed TaskManagementSeeder
```

## Run Backend
Option 1: Through WAMP Apache
- Keep project in `C:\wamp64\www\Task_management`
- Open: `http://localhost/Task_management/backend/public/api`

Option 2: CI dev server
```bash
C:\wamp64\bin\php\php8.2.29\php.exe spark serve --port=8080
```
Then API URL is `http://localhost:8080/api`.

## Cron Reminder
Daily task reminder job:
```bash
C:\wamp64\bin\php\php8.2.29\php.exe spark task:send-reminders
```
Configure this in Windows Task Scheduler daily.

## Auth Seed Accounts
- `admin@taskflow.io` / `admin123`
- `manager@taskflow.io` / `admin123`
- `member@taskflow.io` / `admin123`

## Frontend API Base
Use one of:
- `http://localhost/Task_management/backend/public/api` (WAMP Apache)
- `http://localhost:8080/api` (spark serve)
