# API Endpoints Reference

This RESTful API backend is isolated in the `/api` route space to support mobile applications (Android, iOS, Flutter, React Native) across diverse network environments, including `localhost`, local area network (LAN) IP addresses (e.g., `192.168.x.x`), and the Android Emulator host bridge (`10.0.2.2`).

The standard web interface coexists independently on root and standard web routes (e.g., `?url=product`, `?url=category`).

---

## 1. Network Configuration & Base URLs

The dynamic router strips base paths and project aliases automatically. The base URL adapts to your host environment:

| Client Environment | Base API URL Pattern |
| :--- | :--- |
| **Android Emulator** | `http://10.0.2.2/web-api/api` (Apache) or `http://10.0.2.2:8000/api` (PHP CLI) |
| **Physical Mobile Device (LAN)** | `http://<YOUR_COMPUTER_LAN_IP>/web-api/api` (e.g., `http://192.168.1.50/web-api/api`) |
| **Local Machine / Desktop** | `http://localhost/web-api/api` or `http://127.0.0.1/web-api/api` |

> Both direct REST paths (e.g., `/api/products`) and query parameter routes (e.g., `?url=api/products`) are supported.

---

## 2. Global Headers & CORS Configuration

Every API response emits the following global headers:

```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
Content-Type: application/json; charset=UTF-8
```

### Preflight Requests (`OPTIONS`)
All HTTP `OPTIONS` requests under `/api/*` are intercepted immediately and terminated with HTTP status `200 OK` and an empty response body.

---

## 3. Standard JSON Response Envelope & Status Codes

All API responses strictly follow a uniform envelope structure.

### Success Response (`200 OK`, `201 Created`)
```json
{
  "success": true,
  "data": {},
  "message": "Operation description string."
}
```

### Error Response (`400`, `401`, `404`, `422`, `500`)
```json
{
  "success": false,
  "errors": [
    "Specific error description 1",
    "Specific error description 2"
  ],
  "message": "High-level error summary."
}
```

### Supported HTTP Status Codes
- **`200 OK`**: Successful `GET`, `PUT`, or `DELETE` request.
- **`201 Created`**: Successful `POST` creation of a product, category, or user.
- **`400 Bad Request`**: Invalid query parameter, non-numeric ID format, or constraint violation.
- **`401 Unauthorized`**: Missing, invalid, or expired `Authorization: Bearer <token>`.
- **`404 Not Found`**: Target endpoint, controller, or specific resource ID does not exist.
- **`422 Unprocessable Entity`**: Request body failed validation rules.
- **`500 Internal Server Error`**: Database connection failure or unhandled server exception.

---

## 4. Endpoints Reference

### 4.1 System & Health

#### Check API Status
- **Method**: `GET`
- **URI**: `/api`
- **Required Headers**: None
- **Body Parameters**: None
- **Response Example (`200 OK`)**:
```json
{
  "success": true,
  "data": {
    "api": "Mobile Backend REST API",
    "version": "1.0.0",
    "status": "online",
    "timestamp": "2026-09-30T23:30:00+08:00",
    "endpoints": {
      "products": "/api/products",
      "categories": "/api/categories",
      "auth": "/api/auth"
    }
  },
  "message": "Mobile API backend is running successfully."
}
```

---

### 4.2 Products

#### List All Products
- **Method**: `GET`
- **URI**: `/api/products`
- **Required Headers**:
  - `Accept: application/json`
- **Body Parameters**: None
- **Response Example (`200 OK`)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 22,
      "category_id": 4,
      "name": "Smart Watch",
      "price": "199.99",
      "quantity": 10,
      "created_at": "2026-09-30 23:17:49",
      "updated_at": "2026-09-30 23:17:49",
      "category_name": "Electronics"
    }
  ],
  "message": "Products retrieved successfully."
}
```

---

#### Get Product by ID
- **Method**: `GET`
- **URI**: `/api/products/{id}`
- **Required Headers**:
  - `Accept: application/json`
- **URL Parameters**:
  - `id` *(integer, required)*: Unique product identifier.
- **Body Parameters**: None
- **Success Example (`200 OK`)**:
```json
{
  "success": true,
  "data": {
    "id": 22,
    "category_id": 4,
    "name": "Smart Watch",
    "price": "199.99",
    "quantity": 10,
    "created_at": "2026-09-30 23:17:49",
    "updated_at": "2026-09-30 23:17:49",
    "category_name": "Electronics"
  },
  "message": "Product retrieved successfully."
}
```
- **Error Example (`404 Not Found`)**:
```json
{
  "success": false,
  "errors": [
    "Product with ID 9999 does not exist."
  ],
  "message": "Product not found."
}
```

---

#### Create Product
- **Method**: `POST`
- **URI**: `/api/products`
- **Required Headers**:
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Body Parameters (JSON)**:
  - `name` *(string, required)*: Unique name of the product.
  - `category_id` *(integer, required)*: Existing category ID.
  - `price` *(numeric, required)*: Decimal price greater than `0`.
  - `quantity` *(integer, required)*: Integer quantity greater than `0`.
- **Request Body Example**:
```json
{
  "name": "Noise Cancelling Headphones",
  "category_id": 4,
  "price": 149.99,
  "quantity": 25
}
```
- **Success Example (`201 Created`)**:
```json
{
  "success": true,
  "data": {
    "id": 23,
    "category_id": 4,
    "name": "Noise Cancelling Headphones",
    "price": "149.99",
    "quantity": 25,
    "created_at": "2026-09-30 23:35:39",
    "updated_at": "2026-09-30 23:35:39",
    "category_name": "Electronics"
  },
  "message": "Product created successfully."
}
```
- **Validation Error Example (`422 Unprocessable Entity`)**:
```json
{
  "success": false,
  "errors": [
    "Product name is required.",
    "The selected category does not exist.",
    "Product price must be a valid number greater than 0."
  ],
  "message": "Validation failed."
}
```

---

#### Update Product
- **Method**: `PUT` (or `POST` / `PATCH`)
- **URI**: `/api/products/{id}`
- **Required Headers**:
  - `Content-Type: application/json`
  - `Accept: application/json`
- **URL Parameters**:
  - `id` *(integer, required)*: Existing product ID.
- **Body Parameters (JSON)**:
  - `name` *(string, required)*: Updated name.
  - `category_id` *(integer, required)*: Valid category ID.
  - `price` *(numeric, required)*: Price greater than `0`.
  - `quantity` *(integer, required)*: Quantity greater than `0`.
- **Request Body Example**:
```json
{
  "name": "Noise Cancelling Headphones Pro",
  "category_id": 4,
  "price": 179.99,
  "quantity": 30
}
```
- **Success Example (`200 OK`)**:
```json
{
  "success": true,
  "data": {
    "id": 23,
    "category_id": 4,
    "name": "Noise Cancelling Headphones Pro",
    "price": "179.99",
    "quantity": 30,
    "created_at": "2026-09-30 23:35:39",
    "updated_at": "2026-09-30 23:36:00",
    "category_name": "Electronics"
  },
  "message": "Product updated successfully."
}
```

---

#### Delete Product
- **Method**: `DELETE`
- **URI**: `/api/products/{id}`
- **Required Headers**:
  - `Accept: application/json`
- **URL Parameters**:
  - `id` *(integer, required)*: ID of product to delete.
- **Body Parameters**: None
- **Success Example (`200 OK`)**:
```json
{
  "success": true,
  "data": {
    "id": 23
  },
  "message": "Product deleted successfully."
}
```

---

### 4.3 Categories

#### List All Categories
- **Method**: `GET`
- **URI**: `/api/categories`
- **Required Headers**:
  - `Accept: application/json`
- **Body Parameters**: None
- **Response Example (`200 OK`)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 4,
      "name": "Electronics",
      "description": "Electronic gadgets and equipment",
      "created_at": "2026-09-30 23:17:35"
    }
  ],
  "message": "Categories retrieved successfully."
}
```

---

#### Get Category by ID
- **Method**: `GET`
- **URI**: `/api/categories/{id}`
- **Required Headers**:
  - `Accept: application/json`
- **URL Parameters**:
  - `id` *(integer, required)*: Category ID.
- **Body Parameters**: None
- **Success Example (`200 OK`)**:
```json
{
  "success": true,
  "data": {
    "id": 4,
    "name": "Electronics",
    "description": "Electronic gadgets and equipment",
    "created_at": "2026-09-30 23:17:35"
  },
  "message": "Category retrieved successfully."
}
```

---

#### Create Category
- **Method**: `POST`
- **URI**: `/api/categories`
- **Required Headers**:
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Body Parameters (JSON)**:
  - `name` *(string, required)*: Category name (max 55 characters).
  - `description` *(string, optional)*: Category description.
- **Request Body Example**:
```json
{
  "name": "Accessories",
  "description": "Mobile and computer accessories"
}
```
- **Success Example (`201 Created`)**:
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "Accessories",
    "description": "Mobile and computer accessories",
    "created_at": "2026-09-30 23:40:00"
  },
  "message": "Category created successfully."
}
```
- **Validation Error Example (`422 Unprocessable Entity`)**:
```json
{
  "success": false,
  "errors": [
    "Category name is required."
  ],
  "message": "Validation failed."
}
```

---

#### Update Category
- **Method**: `PUT` (or `POST` / `PATCH`)
- **URI**: `/api/categories/{id}`
- **Required Headers**:
  - `Content-Type: application/json`
  - `Accept: application/json`
- **URL Parameters**:
  - `id` *(integer, required)*: Category ID.
- **Body Parameters (JSON)**:
  - `name` *(string, required)*: Updated name (max 55 characters).
  - `description` *(string, optional)*: Updated description.
- **Request Body Example**:
```json
{
  "name": "Mobile Accessories",
  "description": "Cases, chargers, and screen protectors"
}
```
- **Success Example (`200 OK`)**:
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "Mobile Accessories",
    "description": "Cases, chargers, and screen protectors"
  },
  "message": "Category updated successfully."
}
```

---

#### Delete Category
- **Method**: `DELETE`
- **URI**: `/api/categories/{id}`
- **Required Headers**:
  - `Accept: application/json`
- **URL Parameters**:
  - `id` *(integer, required)*: Category ID.
- **Body Parameters**: None
- **Success Example (`200 OK`)**:
```json
{
  "success": true,
  "data": {
    "id": 5
  },
  "message": "Category deleted successfully."
}
```
- **Foreign Key Conflict Error (`400 Bad Request`)**:
```json
{
  "success": false,
  "errors": [
    "Foreign key constraint violation: products exist in this category."
  ],
  "message": "Cannot delete this category because products are currently assigned to it."
}
```

---

### 4.4 Authentication & Users

#### User Registration
- **Method**: `POST`
- **URI**: `/api/auth/register`
- **Required Headers**:
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Body Parameters (JSON)**:
  - `name` *(string, required)*: Full user name.
  - `email` *(string, required)*: Valid email address.
  - `password` *(string, required)*: Password (minimum 6 characters).
- **Request Body Example**:
```json
{
  "name": "Alex Developer",
  "email": "alex@example.com",
  "password": "Password123"
}
```
- **Success Example (`201 Created`)**:
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Alex Developer",
    "email": "alex@example.com",
    "created_at": "2026-09-30 23:45:00",
    "updated_at": "2026-09-30 23:45:00"
  },
  "message": "User registered successfully."
}
```

---

#### User Login
- **Method**: `POST`
- **URI**: `/api/auth/login`
- **Required Headers**:
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Body Parameters (JSON)**:
  - `email` *(string, required)*: User account email.
  - `password` *(string, required)*: Account password.
- **Request Body Example**:
```json
{
  "email": "alex@example.com",
  "password": "Password123"
}
```
- **Success Example (`200 OK`)**:
```json
{
  "success": true,
  "token": "7a3f8b1c4e9d2a0f5e6b7c8d9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b",
  "token_type": "Bearer"
}
```
- **Authentication Failure (`401 Unauthorized`)**:
```json
{
  "success": false,
  "errors": [
    "Authentication failed. Invalid credentials."
  ],
  "message": "Invalid email or password."
}
```

---

#### User Logout
- **Method**: `POST`
- **URI**: `/api/auth/logout`
- **Required Headers**:
  - `Authorization: Bearer <TOKEN>`
  - `Accept: application/json`
- **Body Parameters**: None
- **Success Example (`200 OK`)**:
```json
{
  "success": true,
  "message": "Logged out successfully. Token has been revoked."
}
```
- **Missing Token Error (`401 Unauthorized`)**:
```json
{
  "success": false,
  "errors": [
    "A Bearer token must be provided in the Authorization header."
  ],
  "message": "Unauthorized: Missing Authorization header."
}
```

---

#### Get Authenticated Profile
- **Method**: `GET`
- **URI**: `/api/auth/profile`
- **Required Headers**:
  - `Authorization: Bearer <TOKEN>`
  - `Accept: application/json`
- **Body Parameters**: None
- **Success Example (`200 OK`)**:
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Alex Developer",
    "email": "alex@example.com",
    "created_at": "2026-09-30 23:45:00",
    "updated_at": "2026-09-30 23:45:00"
  },
  "message": "Authenticated user profile retrieved."
}
```
- **Missing / Invalid Token Error (`401 Unauthorized`)**:
```json
{
  "success": false,
  "errors": [
    "The provided Bearer token is invalid or has expired."
  ],
  "message": "Unauthorized: Invalid or expired access token."
}
```
