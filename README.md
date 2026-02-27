<p align="center"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></p>

# Laravel 12 AI-First Boilerplate

Welcome to the **VotaBoilerplate**, a highly-opinionated, production-ready full-stack template built on **Laravel 12** and **Nuxt 3**. 

This boilerplate is designed from the ground up to be **"AI-First"** — it includes built-in skills, rules, and workflows specifically tailored for Agentic IDEs (like Antigravity, Cursor, or Windsurf) to write clean, maintainable, and architecturally sound code automatically.

---

## 🚀 Core Technologies

### Backend (API)
- **Framework**: Laravel 12 + PHP 8.4
- **Server**: FrankenPHP (via Laravel Octane) for maximum performance.
- **Queue/Jobs**: Laravel Horizon (running in a dedicated worker container).
- **Authentication**: Laravel Passport (OAuth2).
- **Permissions**: Spatie Laravel Permissions (RBAC).
- **Database Architecture**: PostgreSQL 16 + Redis 7 (Cache & Sessions).
- **CRUD Scaffolding**: `votapil/votacrudgenerator` (Database-first generation).

### Frontend (SPA/SSR)
- **Framework**: Nuxt 3 (Vue 3 Composition API).
- **UI/UX System**: Vuetify 3 (Material Design 3).
- **State Management**: Pinia.

---

## 🛠️ Getting Started

### Prerequisites
- Docker & Docker Compose
- `make` utility

### Local Environment Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/votapil/votaboilerplate.git
   cd votaboilerplate
   ```

2. **Configure Environment:**
   ```bash
   cp .env.example .env
   cp docker/.env.example docker/.env # (If applicable)
   ```

3. **Build & Start Containers:**
   Our custom Makefile wraps Docker operations for convenience.
   ```bash
   make rebuild
   ```
   *This takes a few minutes as it compiles FrankenPHP with PHP 8.4 and Node.js for the frontend.*

4. **Verify it's running:**
   - **Backend API**: `http://localhost:80` (or `https://localhost:443`)
   - **Frontend UI**: `http://localhost:3000`
   - **Horizon Dashboard**: `http://localhost/horizon`

---

## 🤖 AI Integration & Rules

This repository teaches AI exactly *how* you want your code written.

### `.antigravityrules`
The global rule file for the Agentic IDE. It enforces:
- Strict **Domain-Driven Design (DDD)**.
- Mandatory use of **DTOs (Data Transfer Objects)**.
- Usage of **Actions** over bloated Services or fat Controllers.
- Prohibition of `static` variables (to ensure **Octane compliance**).
- Avoidance of N+1 database queries.

### `.agents/workflows/`
Contains step-by-step Standard Operating Procedures (SOPs) for the AI.
- `new_api_endpoint.md`: Instructs the AI to design migrations, run `php artisan vota:crud`, and then manually extract complex business logic into DTOs and Actions.

### `.agents/skills/`
Context files defining the "personas" and technical baselines for the AI:
- `laravel_architecture.md`: Project-specific architectural boundaries (Octane, Horizon, Spatie Permissions).
- `laravel-expert`: Enforces Senior-level Laravel techniques.
- `laravel-security-audit`: Reviews code against OWASP standards.
- `vue3-nuxt3-expert`: Enforces Composition API, Nuxt 3 auto-imports, and Pinia.
- `vuetify3-material-design`: Enforces Material Design 3 guidelines utilizing Vuetify components over custom CSS.

---

## ⚙️ Quick Commands (Makefile)

The `Makefile` is your single entry point for all commands. **Do not run `php artisan` on your host machine.**

| Command | Description |
|---|---|
| `make up` | Start all Docker containers in detached mode |
| `make down` | Stop and remove all containers |
| `make rebuild` | Force rebuild all Docker images from scratch |
| `make app-shell` | SSH into the main FrankenPHP (API) container |
| `make worker-shell`| SSH into the Queue/Horizon worker container |
| `make webapp-shell`| SSH into the Nuxt 3 (Frontend) container |
| `make pg-shell` | Drop into `psql` interactive prompt |
| `make redis-cli` | Drop into `redis-cli` interactive prompt |
| `make artisan ...` | Run any artisan command (e.g. `make artisan migrate`) |
| `make composer ...`| Run composer commands (e.g. `make composer install`) |
| `make test` | Run Pest PHP feature tests |

---

## 📚 Development Workflow Example

Want to add a new entity (e.g., `Product`)? Let the AI handle the heavy lifting:

1. Ask your AI Assistant to implement a new `Product` API endpoint.
2. The AI reads `.agents/workflows/new_api_endpoint.md` and generates a migration file with `->comment()` annotations for context.
3. You run `make artisan migrate`.
4. The AI runs `make artisan args="vota:crud Product"` to scaffold the base REST API.
5. For complex logic, the AI will inject an Action (e.g. `CreateProductAction`) and a DTO within the generated Controller.
6. The AI writes a Pest Feature test and executes `make test`.
