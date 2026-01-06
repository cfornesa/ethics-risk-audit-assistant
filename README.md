# Ethics/Risk Audit Assistant

A production-ready Laravel application for auditing political content using AI-powered ethics and risk analysis. Built with Laravel 11, MySQL, and Mistral AI (Ministral-3-14B-Reasoning-2512).

## Features

- **Project Management**: Organize political content audits into projects
- **AI-Powered Ethics Auditing**: Automated risk assessment using Mistral AI
- **Comprehensive Risk Rubric**: 7 risk categories including microtargeting, disinformation, emotional manipulation, and more
- **Risk Scoring**: Automated risk level classification (low, medium, high, critical)
- **Automated Notifications**: Email alerts for high-risk content detection
- **Human Review Flags**: Auto-flagging of content requiring human oversight
- **IRB/Ethics Export**: Export audit reports in HTML and Markdown formats
- **Queue-Based Processing**: Background processing with automatic retries
- **REST API**: Full API support for programmatic access
- **Responsive Dashboard**: Clean Tailwind CSS interface

## Tech Stack

- **Backend**: PHP 8.2+, Laravel 11
- **Frontend**: Blade Templates + Tailwind CSS
- **Database**: MySQL 8 / MariaDB
- **AI**: Mistral Ministral-3-14B-Reasoning-2512
- **Queue**: Database-backed queues
- **Deployment**: Hostinger-compatible (shared hosting)

## Risk Categories

The application evaluates content across 7 dimensions:

1. **Microtargeting** (0-10): Exploitation of personal data, psychological profiles
2. **Emotional Manipulation** (0-10): Fear-mongering, outrage exploitation
3. **Disinformation** (0-10): False claims, misleading information
4. **Voter Suppression** (0-10): Discouraging participation, false voting info
5. **Vulnerable Populations** (0-10): Exploitation of children, elderly, disadvantaged groups
6. **AI/Transparency** (0-10): Undisclosed AI content, synthetic media
7. **Legal/Regulatory** (0-10): Election law violations, privacy breaches

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8 or MariaDB
- Node.js & NPM (for asset compilation)
- Mistral API key

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ethics-risk-audit-assistant
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   ```

5. **Configure the `.env` file**
   ```env
   APP_NAME="Ethics Risk Audit Assistant"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ethics_risk_audit
   DB_USERNAME=your_db_username
   DB_PASSWORD=your_db_password

   QUEUE_CONNECTION=database

   MAIL_MAILER=smtp
   MAIL_HOST=your-smtp-host
   MAIL_PORT=587
   MAIL_USERNAME=your-email@example.com
   MAIL_PASSWORD=your-email-password
   MAIL_FROM_ADDRESS="noreply@example.com"
   MAIL_FROM_NAME="${APP_NAME}"

   # Mistral AI Configuration
   MISTRAL_API_KEY=your_mistral_api_key
   MISTRAL_API_BASE=https://api.mistral.ai/v1
   MISTRAL_MODEL=ministral-3-14b-reasoning-2512

   # Ethics Configuration (Optional)
   ETHICS_NOTIFICATIONS_ENABLED=true
   ETHICS_NOTIFICATION_EMAIL=admin@example.com
   ```

6. **Generate application key**
   ```bash
   php artisan key:generate
   ```

7. **Run database migrations**
   ```bash
   php artisan migrate
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   ```

9. **Create storage symlink**
   ```bash
   php artisan storage:link
   ```

10. **Set up cron job** (for queue processing and scheduler)
    ```cron
    * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
    ```

11. **Start queue worker** (for development)
    ```bash
    php artisan queue:work --sleep=3 --tries=3
    ```

## Configuration

### Risk Thresholds

Edit `config/ethics.php` to customize risk thresholds and notification settings.

## Usage

### Web Interface

1. Create a Project
2. Add Items (political content)
3. Items are automatically queued for ethics/risk analysis
4. Review Results with detailed risk breakdowns
5. Export IRB-style reports

### API Endpoints

**Projects:**
- `GET /api/v1/projects` - List projects
- `POST /api/v1/projects` - Create project
- `GET /api/v1/projects/{id}` - Get project
- `PUT /api/v1/projects/{id}` - Update project
- `DELETE /api/v1/projects/{id}` - Delete project

**Items:**
- `GET /api/v1/items` - List items (supports filtering)
- `POST /api/v1/items` - Create item (auto-queues for audit)
- `GET /api/v1/items/{id}` - Get item
- `POST /api/v1/items/{id}/reaudit` - Re-run audit
- `POST /api/v1/items/{id}/mark-reviewed` - Mark as reviewed

## File Structure

```
├── app/
│   ├── Http/Controllers/          # Web & API controllers
│   ├── Jobs/RunEthicsAudit.php   # Queue job for AI audits
│   ├── Models/                    # Eloquent models
│   ├── Notifications/             # Email notifications
│   └── Services/MistralClient.php # Mistral AI integration
├── config/ethics.php              # Ethics/risk configuration
├── database/migrations/           # Database schema
├── resources/views/               # Blade templates
└── routes/                        # Web & API routes
```

## License

MIT License
