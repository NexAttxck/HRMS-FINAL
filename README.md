# HRMSV3 (Staffora HRMS)

Welcome to **HRMSV3**, a comprehensive Human Resource Management System built with PHP and MySQL. 

## Requirements
- **XAMPP**, **WAMP**, or any local server running PHP and MySQL
- PHP 7.4 or higher
- MySQL / MariaDB

## Installation & Setup

1. **Clone the repository** (if you haven't already):
   ```bash
   git clone https://github.com/NexAttxck/HRMSV3.git
   ```
   *Make sure the folder is placed in your server's root directory (e.g., `C:\xampp\htdocs\HRMSV3`).*

2. **Database Setup**:
   - Open [phpMyAdmin](http://localhost/phpmyadmin) (or your preferred MySQL client).
   - Create a new database named **`hrms_db`**.
   - Import the **`hrms_db_schema.sql`** file provided in the repository to create the tables.

3. **Populate the Database with Test Data**:
   Instead of inserting data manually, you can run the built-in database seeder to populate realistic dummy data.
   - Open your browser and navigate to:  
     👉 **[http://localhost/HRMSV3/seed.php?force=1](http://localhost/HRMSV3/seed.php?force=1)**
   - *This will automatically clear existing data and insert employees, roles, departments, payroll, and more.*

## How to Run the App

After seeding the database, navigate to the main application in your browser:
**[http://localhost/HRMSV3](http://localhost/HRMSV3)**

You can log in using the following test accounts:

| Role | Email | Password |
|---|---|---|
| **Super Admin** | `admin@cura.ph` | `Password123` |
| **HR Manager** | `hr@cura.ph` | `Password123` |
| **Employee** | `juan.delacruz@cura.ph` | `Password123` |

---
*Happy coding and managing!*
