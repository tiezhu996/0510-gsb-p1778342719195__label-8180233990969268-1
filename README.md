# 医院志愿者服务平台

Hospital Volunteer Service Platform - A PHP-based full-stack web application for managing hospital volunteers.

## 🧭 Project Type

**A) FULLSTACK_WEB** (frontend + backend + database)

## 🚀 How to Run (QA)

```bash
docker compose up
```

No additional parameters required. The command must work without any modifications.

## Services

| Service | Port | URL | Description |
|---------|------|-----|-------------|
| frontend | 3000 | http://localhost:3000 | Web UI (Nginx) |
| backend | 8000 | http://localhost:8000 | REST API (PHP) |
| db | 3306 | internal | MySQL 8.0 Database |

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 8.2, Built-in web server
- **Database**: MySQL 8.0
- **Web Server**: Nginx (frontend), PHP built-in (backend)
- **Containerization**: Docker, Docker Compose

## Features

| Feature | Description |
|---------|-------------|
| F1 档案管理 | Volunteer archive management (CRUD) |
| F2 服务时长 | Service hours tracking and recording |
| F3 考勤管理 | Attendance management (check-in/check-out) |
| F4 星级评定 | Star rating system and incentives |
| F5 岗位分配 | Position/role assignment for volunteers |
| F6 服务评价 | Service evaluation (feedback system) |
| F7 荣誉体系 | Honor/award system |
| F8 积分兑换 | Points exchange/redemption system |

## Verification

### Success Path

1. Open browser to http://localhost:3000
2. Verify the dashboard loads showing statistics
3. Navigate through all menu items (9 sections)
4. Add a volunteer via "志愿者管理" → "添加志愿者"
5. Record service hours via "服务时长" → "记录时长"
6. Check in/out via "考勤管理"
7. Assign positions via "岗位分配"
8. Rate stars via "星级评定"
9. Submit evaluations via "服务评价"
10. Award honors via "荣誉体系"
11. Manage points via "积分管理"

### Failure Path

1. Try submitting empty required fields → should show validation error
2. Try deleting non-existent record → should show error message

## Test Credentials

No authentication required. All features are accessible without login.

## Evidence

| File | Description |
|------|-------------|
| evidence/01_boot.png | docker compose up startup logs with healthy status |
| evidence/02_success.png | Successful UI/API operations |
| evidence/03_failed.png | Validation errors and failure scenarios |
| evidence/04_tree.png | Project directory structure |
| evidence/05_key_code.png | Key code: DB connection, API routing, auth |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/volunteers | List all volunteers |
| POST | /api/volunteers | Create volunteer |
| GET | /api/volunteers/{id} | Get volunteer details |
| PUT | /api/volunteers/{id} | Update volunteer |
| DELETE | /api/volunteers/{id} | Delete volunteer |
| GET | /api/service-hours | List service hours |
| POST | /api/service-hours | Record service hours |
| GET | /api/attendance | List attendance records |
| POST | /api/attendance | Check in/out |
| GET | /api/star-ratings | List star ratings |
| POST | /api/star-ratings | Award star rating |
| GET | /api/positions | List positions |
| POST | /api/positions | Create position |
| GET | /api/position-assignments | List assignments |
| POST | /api/position-assignments | Assign position |
| GET | /api/evaluations | List evaluations |
| POST | /api/evaluations | Submit evaluation |
| GET | /api/honors | List honors |
| POST | /api/honors | Create honor |
| GET | /api/honor-awards | List honor awards |
| POST | /api/honor-awards | Award honor |
| GET | /api/points | List points records |
| POST | /api/points | Add points |
| GET | /api/point-exchanges | List exchanges |
| POST | /api/point-exchanges | Exchange points |
| GET | /api/rewards | List rewards |
| POST | /api/rewards | Create reward |
| GET | /health | Health check |

## Environment Variables

The following environment variables are pre-configured in docker-compose.yml:

| Variable | Value | Description |
|----------|-------|-------------|
| DB_HOST | db | Database service name |
| DB_PORT | 3306 | Database port |
| DB_DATABASE | volunteer_platform | Database name |
| DB_USERNAME | root | Database user |
| DB_PASSWORD | rootpassword | Database password |

## Database Schema

- volunteers: Volunteer profiles
- service_hours: Service time tracking
- attendance: Check-in/check-out records
- star_ratings: Star ratings
- positions: Available positions
- position_assignments: Position assignments
- evaluations: Service evaluations
- honors: Honor types
- honor_awards: Honor awards
- points: Points records
- point_exchanges: Points redemption
- rewards: Redeemable rewards

## Development

For developers who want to modify the code:

```bash
# Build and start containers
docker

# View compose up --build logs
docker compose logs -f

# Stop containers
docker compose down

# Stop and remove volumes (data loss)
docker compose down -v
```

## Notes

- Backend uses PHP built-in web server on port 8000
- Frontend is served by Nginx on port 3000
- Database data persists in Docker volume `mysql_data`
- Health checks ensure proper startup order
- All API endpoints return JSON responses
