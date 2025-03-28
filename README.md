# Account Management API

## 📌 Technology Stack
- **PHP Version**: 8.2
- **Laravel Version**: 12
- **Database**: MySQL

---

## 🚀 Installation Guide

### 1️⃣ Clone the Repository
```bash
git clone <repository-url>
cd <project-directory>
```

### 2️⃣ Install Dependencies
```bash
composer update
```

### 3️⃣ Configure Environment
1. Create a MySQL database.
2. Configure the `.env` file with your database details:
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```

### 4️⃣ Run Migrations
```bash
php artisan migrate
```

### 5️⃣ Serve the Application
```bash
php artisan serve
```
📍 The API will be accessible at: `http://127.0.0.1:8000/`

### 6️⃣ API Documentation
📄 Swagger API documentation can be accessed at:
```
http://127.0.0.1:8000/api/documentation#/
```

---

## 🔑 Authentication
1. **Register an Account** (`POST /api/register`)
2. **Login** (`POST /api/login`)
3. **Retrieve Bearer Token** (Response from login)
4. **Authorize API Requests** (Include the Bearer Token in the Authorization header)

---

## 📌 API Endpoints

### 🏦 **Accounts API**
- **Create a New Account** → `POST /api/accounts`
- **Get Account Details** → `GET /api/accounts/{account_number}`
- **Update Account** → `PUT /api/accounts/{account_number}`
- **Delete Account** → `DELETE /api/accounts/{account_number}`

### 💳 **Transactions API**
- **Get Account Transactions** → `GET /api/transactions`
- **Create a New Transaction** → `POST /api/transactions`

---

## 📜 License
This project is licensed under the [MIT License](LICENSE).

