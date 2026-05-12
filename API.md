# Rotary Club API Documentation

**Base URL:** `https://rotary.rankstallion.com`  
**Content-Type:** `application/json`  
**Authentication:** Bearer Token (JWT)

---

## Authentication

All protected endpoints require the following header:

```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

Access tokens expire in **15 minutes**. Use the refresh endpoint to get a new one without re-login.

---

## Auth Endpoints

### POST `/auth/login.php`
**Access:** Public

Authenticates a user and returns access + refresh tokens.

**Request Body:**
```json
{
    "email": "rahul@example.com",
    "password": "yourpassword"
}
```

**Success Response `200`:**
```json
{
    "success": true,
    "access_token": "eyJ0eXAiOiJKV1Qi...",
    "refresh_token": "eyJ0eXAiOiJKV1Qi...",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
        "id": 1,
        "name": "Rahul Goel",
        "email": "rahul@example.com",
        "role": "admin",
        "profile_photo": null
    }
}
```

**Error Responses:**
| Code | Message |
|------|---------|
| `400` | Email and password are required |
| `401` | Invalid email or password |
| `405` | Method not allowed |

**cURL Example:**
```bash
curl -X POST https://rotary.rankstallion.com/auth/login.php \
-H "Content-Type: application/json" \
-d '{"email":"rahul@example.com","password":"yourpassword"}'
```

---

### POST `/auth/refresh.php`
**Access:** Public

Issues a new access token using a valid refresh token.

**Request Body:**
```json
{
    "refresh_token": "eyJ0eXAiOiJKV1Qi..."
}
```

**Success Response `200`:**
```json
{
    "success": true,
    "access_token": "eyJ0eXAiOiJKV1Qi...",
    "token_type": "Bearer",
    "expires_in": 900
}
```

**Error Responses:**
| Code | Message |
|------|---------|
| `400` | Refresh token is required |
| `401` | Refresh token expired. Please login again |
| `401` | Refresh token revoked or invalid |

**cURL Example:**
```bash
curl -X POST https://rotary.rankstallion.com/auth/refresh.php \
-H "Content-Type: application/json" \
-d '{"refresh_token":"YOUR_REFRESH_TOKEN"}'
```

---

### POST `/auth/logout.php`
**Access:** Member, Admin

Clears the refresh token from the database, invalidating the session.

**Success Response `200`:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

**Error Responses:**
| Code | Message |
|------|---------|
| `401` | Authorization token missing |
| `401` | Token expired |

**cURL Example:**
```bash
curl -X POST https://rotary.rankstallion.com/auth/logout.php \
-H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

---

## Members Endpoints

### POST `/members/add.php`
**Access:** Admin only

Adds a new member including optional business and family member details.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secret123",
    "country_code": "+91",
    "phone": "9876543210",
    "birth_date": "1990-05-15",
    "anniversary_date": "2015-11-20",
    "blood_group": "O+",
    "country_code_2": "+91",
    "phone_2": "9876500000",
    "gender": "male",
    "language": "English",
    "introduction": "Brief intro here",
    "rotary_id": "ROT2024001",
    "admission_date": "2024-01-01",
    "facebook": "https://facebook.com/johndoe",
    "instagram": "https://instagram.com/johndoe",
    "linkedin": "https://linkedin.com/in/johndoe",
    "twitter": "https://twitter.com/johndoe",
    "youtube": "",
    "website": "https://johndoe.com",
    "address": "123 Main Street",
    "state": "Punjab",
    "city": "Chandigarh",
    "zip_code": "160001",
    "business": {
        "business_name": "Doe Enterprises",
        "business_email": "info@doe.com",
        "designation": "CEO",
        "classification": "Technology",
        "keywords": "software, IT, consulting",
        "country_code": "+91",
        "phone": "9876511111",
        "address": "456 Business Park",
        "state": "Punjab",
        "city": "Chandigarh",
        "zip_code": "160002"
    },
    "family_members": [
        { "name": "Jane Doe", "relation": "spouse" },
        { "name": "Tom Doe", "relation": "son" }
    ]
}
```

> **Note:** Only `name`, `email`, and `password` are required. All other fields are optional.  
> Photos (`profile_photo`, `family_photo`, family member photos) are handled via separate upload endpoints.

**Success Response `200`:**
```json
{
    "success": true,
    "message": "Member added successfully",
    "member_id": 3
}
```

**Error Responses:**
| Code | Message |
|------|---------|
| `400` | Name, email and password are required |
| `401` | Authorization token missing |
| `403` | Access denied. Admins only |
| `409` | Email already exists |
| `500` | Failed to add member |

**cURL Example:**
```bash
curl -X POST https://rotary.rankstallion.com/members/add.php \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
-d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secret123",
    "phone": "9876543210",
    "country_code": "+91",
    "birth_date": "1990-05-15",
    "blood_group": "O+",
    "gender": "male",
    "rotary_id": "ROT2024001",
    "business": {
        "business_name": "Doe Enterprises",
        "designation": "CEO",
        "city": "Chandigarh"
    },
    "family_members": [
        { "name": "Jane Doe", "relation": "spouse" },
        { "name": "Tom Doe", "relation": "son" }
    ]
}'
```

---

### POST `/members/update.php`
**Access:** Admin only

Updates an existing member's details. Only fields included in the request are updated — missing fields are left unchanged.

**Request Body:**
```json
{
    "member_id": 3,
    "name": "John Doe",
    "email": "john@example.com",
    "password": "newpassword123",
    "country_code": "+91",
    "phone": "9876543210",
    "birth_date": "1990-05-15",
    "anniversary_date": "2015-11-20",
    "blood_group": "O+",
    "country_code_2": "+91",
    "phone_2": "9876500000",
    "gender": "male",
    "language": "English",
    "introduction": "Updated intro",
    "rotary_id": "ROT2024001",
    "admission_date": "2024-01-01",
    "facebook": "https://facebook.com/johndoe",
    "instagram": "https://instagram.com/johndoe",
    "linkedin": "https://linkedin.com/in/johndoe",
    "twitter": "https://twitter.com/johndoe",
    "youtube": "",
    "website": "https://johndoe.com",
    "address": "123 Main Street",
    "state": "Punjab",
    "city": "Chandigarh",
    "zip_code": "160001",
    "business": {
        "business_name": "Doe Enterprises",
        "business_email": "info@doe.com",
        "designation": "Director",
        "classification": "Technology",
        "keywords": "software, IT",
        "country_code": "+91",
        "phone": "9876511111",
        "address": "456 Business Park",
        "state": "Punjab",
        "city": "Chandigarh",
        "zip_code": "160002"
    },
    "family_members": [
        { "name": "Jane Doe", "relation": "spouse" },
        { "name": "Tom Doe", "relation": "son" }
    ]
}
```

> **Note:** Only `member_id` is required. Send only the fields you want to update.  
> Sending `family_members` replaces the entire existing family members list.  
> Sending `business` updates if exists, creates if not.  
> Photos are handled via separate upload endpoints.

**Success Response `200`:**
```json
{
    "success": true,
    "message": "Member updated successfully"
}
```

**Error Responses:**
| Code | Message |
|------|---------|
| `400` | member_id is required |
| `401` | Authorization token missing |
| `403` | Access denied. Admins only |
| `404` | Member not found |
| `409` | Email already in use |
| `500` | Failed to update member |

**cURL Example:**
```bash
curl -X POST https://rotary.rankstallion.com/members/update.php \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
-d '{
    "member_id": 3,
    "city": "Mumbai",
    "business": {
        "designation": "Director"
    }
}'
```

---

### POST `/members/delete.php`
**Access:** Admin only

> ⏳ Coming soon

---

### GET `/members/list.php`
**Access:** Member, Admin

> ⏳ Coming soon

---

### GET `/members/view.php?id=1`
**Access:** Member, Admin

> ⏳ Coming soon

---

## Events Endpoints

### POST `/events/add.php`
**Access:** Admin only
> ⏳ Coming soon

### POST `/events/update.php`
**Access:** Admin only
> ⏳ Coming soon

### POST `/events/delete.php`
**Access:** Admin only
> ⏳ Coming soon

### GET `/events/list.php`
**Access:** Member, Admin
> ⏳ Coming soon

---

## Updates Endpoints

### POST `/updates/add.php`
**Access:** Admin only
> ⏳ Coming soon

### POST `/updates/delete.php`
**Access:** Admin only
> ⏳ Coming soon

### GET `/updates/list.php`
**Access:** Member, Admin
> ⏳ Coming soon

---

## Gallery Endpoints

### POST `/gallery/add.php`
**Access:** Admin only
> ⏳ Coming soon

### POST `/gallery/delete.php`
**Access:** Admin only
> ⏳ Coming soon

### GET `/gallery/list.php?event_id=1`
**Access:** Member, Admin
> ⏳ Coming soon

---

## Database Schema

### Table: `users`
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary key |
| `email` | VARCHAR(100) | Unique, required |
| `password` | VARCHAR(255) | Bcrypt hashed |
| `role` | ENUM | `admin` or `member` |
| `refresh_token` | VARCHAR(255) | SHA256 hashed refresh token |
| `token_expires` | DATETIME | Refresh token expiry |
| `profile_photo` | VARCHAR(255) | Photo file path |
| `name` | VARCHAR(100) | Required |
| `country_code` | VARCHAR(10) | e.g. +91 |
| `phone` | VARCHAR(20) | Primary phone |
| `birth_date` | DATE | |
| `anniversary_date` | DATE | |
| `blood_group` | VARCHAR(5) | e.g. O+ |
| `country_code_2` | VARCHAR(10) | Secondary phone code |
| `phone_2` | VARCHAR(20) | Secondary phone |
| `gender` | ENUM | `male`, `female`, `other` |
| `language` | VARCHAR(50) | |
| `introduction` | TEXT | |
| `rotary_id` | VARCHAR(50) | |
| `admission_date` | DATE | |
| `facebook` | VARCHAR(255) | |
| `instagram` | VARCHAR(255) | |
| `linkedin` | VARCHAR(255) | |
| `twitter` | VARCHAR(255) | |
| `youtube` | VARCHAR(255) | |
| `website` | VARCHAR(255) | |
| `address` | TEXT | |
| `state` | VARCHAR(100) | |
| `city` | VARCHAR(100) | |
| `zip_code` | VARCHAR(20) | |
| `family_photo` | VARCHAR(255) | |
| `created_at` | TIMESTAMP | Auto |
| `updated_at` | TIMESTAMP | Auto update |

### Table: `member_business`
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary key |
| `user_id` | INT | Foreign key → users.id |
| `business_name` | VARCHAR(200) | |
| `business_email` | VARCHAR(100) | |
| `designation` | VARCHAR(100) | |
| `classification` | VARCHAR(100) | Profession/industry |
| `keywords` | VARCHAR(255) | |
| `country_code` | VARCHAR(10) | |
| `phone` | VARCHAR(20) | |
| `address` | TEXT | |
| `state` | VARCHAR(100) | |
| `city` | VARCHAR(100) | |
| `zip_code` | VARCHAR(20) | |
| `created_at` | TIMESTAMP | Auto |

### Table: `family_members`
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary key |
| `user_id` | INT | Foreign key → users.id |
| `name` | VARCHAR(100) | Required |
| `relation` | VARCHAR(50) | e.g. spouse, son |
| `photo` | VARCHAR(255) | Photo file path |
| `created_at` | TIMESTAMP | Auto |

### Table: `events`
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary key |
| `title` | VARCHAR(200) | Required |
| `description` | TEXT | |
| `event_date` | DATETIME | |
| `location` | VARCHAR(255) | |
| `featured_photo` | VARCHAR(255) | |
| `created_by` | INT | Foreign key → users.id |
| `created_at` | TIMESTAMP | Auto |

### Table: `updates`
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary key |
| `title` | VARCHAR(200) | Required |
| `content` | TEXT | |
| `featured_photo` | VARCHAR(255) | |
| `posted_by` | INT | Foreign key → users.id |
| `created_at` | TIMESTAMP | Auto |

### Table: `gallery`
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary key |
| `event_id` | INT | Foreign key → events.id |
| `photo_url` | VARCHAR(255) | Required |
| `caption` | VARCHAR(255) | |
| `uploaded_by` | INT | Foreign key → users.id |
| `created_at` | TIMESTAMP | Auto |

---

## Endpoint Status

### Auth
| Method | Endpoint | Access | Status |
|--------|----------|--------|--------|
| POST | `/auth/login.php` | Public | ✅ Done |
| POST | `/auth/refresh.php` | Public | ✅ Done |
| POST | `/auth/logout.php` | Member | ✅ Done |

### Members
| Method | Endpoint | Access | Status |
|--------|----------|--------|--------|
| POST | `/members/add.php` | Admin | ✅ Done |
| POST | `/members/update.php` | Admin | ✅ Done |
| POST | `/members/delete.php` | Admin | ⏳ Pending |
| GET | `/members/list.php` | Member | ⏳ Pending |
| GET | `/members/view.php?id=1` | Member | ⏳ Pending |

### Events
| Method | Endpoint | Access | Status |
|--------|----------|--------|--------|
| POST | `/events/add.php` | Admin | ⏳ Pending |
| POST | `/events/update.php` | Admin | ⏳ Pending |
| POST | `/events/delete.php` | Admin | ⏳ Pending |
| GET | `/events/list.php` | Member | ⏳ Pending |

### Updates
| Method | Endpoint | Access | Status |
|--------|----------|--------|--------|
| POST | `/updates/add.php` | Admin | ⏳ Pending |
| POST | `/updates/delete.php` | Admin | ⏳ Pending |
| GET | `/updates/list.php` | Member | ⏳ Pending |

### Gallery
| Method | Endpoint | Access | Status |
|--------|----------|--------|--------|
| POST | `/gallery/add.php` | Admin | ⏳ Pending |
| POST | `/gallery/delete.php` | Admin | ⏳ Pending |
| GET | `/gallery/list.php?event_id=1` | Member | ⏳ Pending |

---

## Error Code Reference

| HTTP Code | Meaning |
|-----------|---------|
| `200` | Success |
| `400` | Bad Request — missing or invalid input |
| `401` | Unauthorized — token missing, expired, or invalid |
| `403` | Forbidden — insufficient role/permissions |
| `405` | Method Not Allowed |
| `409` | Conflict — e.g. duplicate email |
| `500` | Internal Server Error |

---

## Token Lifecycle

```
Login → Access Token (15 min) + Refresh Token (2 weeks)
         ↓
    Access Token expires
         ↓
    POST /auth/refresh.php → New Access Token
         ↓
    Repeat until Refresh Token expires (2 weeks)
         ↓
    User must login again
```

---

## Project Folder Structure

```
/
├── vendor/                  ← Composer libraries (never edit)
├── config.php               ← JWT secret, DB credentials
├── db.php                   ← PDO database connection
├── README.md                ← This file
├── composer.json
├── composer.lock
├── .gitignore
├── middleware/
│   └── authenticate.php     ← JWT validation + role check
├── auth/
│   ├── login.php            ✅
│   ├── refresh.php          ✅
│   └── logout.php           ✅
├── members/
│   ├── add.php              ✅
│   ├── update.php           ✅
│   ├── delete.php           ⏳
│   ├── list.php             ⏳
│   └── view.php             ⏳
├── events/
│   ├── add.php              ⏳
│   ├── update.php           ⏳
│   ├── delete.php           ⏳
│   └── list.php             ⏳
├── updates/
│   ├── add.php              ⏳
│   ├── delete.php           ⏳
│   └── list.php             ⏳
└── gallery/
    ├── add.php              ⏳
    ├── delete.php           ⏳
    └── list.php             ⏳
```

---

*Last updated: May 2026*
