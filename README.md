# Football News API

A comprehensive REST API for football news, fixtures, teams, and competitions built with Laravel.

## Features

- **Authentication & Authorization**
  - User registration and login
  - JWT-based authentication
  - Role-based access control

- **Football Data**
  - Teams and team information
  - Competitions and seasons
  - Fixtures and match details
  - Standings and league tables
  - Team formations and lineups
  - Match predictions

- **News & Social**
  - Football news articles
  - Comments and discussions
  - User notifications

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register a new user
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout

### Teams
- `GET /api/teams` - List all teams
- `GET /api/teams/{id}` - Get team details
- `GET /api/teams/{id}/fixtures` - Get team fixtures
- `GET /api/teams/{id}/lineups` - Get team lineups

### Competitions
- `GET /api/competitions` - List all competitions
- `GET /api/competitions/{id}` - Get competition details
- `GET /api/competitions/{id}/standings` - Get competition standings
- `GET /api/competitions/{id}/seasons` - Get competition seasons

### Fixtures
- `GET /api/fixtures` - List fixtures
- `GET /api/fixtures/{id}` - Get fixture details
- `GET /api/fixtures/{id}/lineups` - Get fixture lineups
- `GET /api/fixtures/{id}/predictions` - Get fixture predictions

### News
- `GET /api/news` - List news articles
- `GET /api/news/{id}` - Get news article details
- `POST /api/news/{id}/comments` - Add comment to news article

### Areas
- `GET /api/areas` - List all areas/regions
- `GET /api/areas/{id}` - Get area details

## Models

The application uses the following main models:

- `User` - User accounts and authentication
- `Team` - Football teams
- `Competition` - Football competitions/leagues
- `Season` - Competition seasons
- `Fixture` - Match fixtures
- `News` - News articles
- `Comment` - User comments
- `Area` - Geographical areas
- `Formation` - Team formations
- `Lineup` - Match lineups
- `Standing` - League standings
- `Goal` - Match goals
- `Notification` - User notifications

## Setup

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Copy `.env.example` to `.env` and configure your environment variables
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations:
   ```bash
   php artisan migrate
   ```
6. Start the development server:
   ```bash
   php artisan serve
   ```

## Docker Support

The application includes Docker configuration for easy deployment:

```bash
docker-compose up -d
```

## Testing

Run tests using PHPUnit:

```bash
php artisan test
```

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License.
