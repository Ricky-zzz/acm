# ACM

## Overview
This repository contains two related web applications:

- **counterra**: Application with a PHP API and a Vue (Vite) web client.
- **voterra**: Application with a PHP API and a Vue (Vite) web client.

Each application is organized as a separate module with its own backend (`api/`) and frontend (`web/`). Shared SQL notes and scripts are stored under `sql/`.

## Repository Structure
- `counterra/`
  - `api/`: PHP API (Slim-based) entry point in `public/index.php`
  - `web/`: Vue 3 + Vite client application
- `voterra/`
  - `api/`: PHP API (Slim-based) entry point in `public/index.php`
  - `web/`: Vue 3 + Vite client application
- `sql/`: SQL scripts and query notes

## Prerequisites
- PHP 8.x
- Composer
- Node.js 18+ and npm
- A MySQL/MariaDB server (recommended when running under Laragon)

## Local Development
### Backend API (per module)
From either `counterra/api` or `voterra/api`:

1. Install dependencies:
   - `composer install`
2. Serve the API:
   - If using Laragon/Apache, map the web root to the module’s `api/public/` directory.
   - If using PHP’s built-in server (development only):
     - `php -S localhost:8000 -t public`

### Frontend Web (per module)
From either `counterra/web` or `voterra/web`:

1. Install dependencies:
   - `npm install`
2. Start the development server:
   - `npm run dev`
3. Build for production:
   - `npm run build`

## Notes
- Database schema and sample queries are available under `sql/`.
- Configuration details (such as API base URLs) are typically defined in the respective frontend codebase and may need to be adjusted for local environments.
