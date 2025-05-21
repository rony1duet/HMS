# 🏫 DUET Hall Management System (HMS)

<div align="center">
  <img src="assets/images/duet-logo.png" alt="DUET HMS Logo" width="150">
  <h3>Smart Hall Management for the Digital Era</h3>
  <p>A modern, comprehensive web-based system that digitizes and streamlines university residence hall operations.</p>
  <p>
    <strong>Institution:</strong> Dhaka University of Engineering & Technology (DUET)
  </p>
  
  ![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
  ![MySQL](https://img.shields.io/badge/MySQL-Latest-orange)
  ![Bootstrap](https://img.shields.io/badge/Bootstrap-5-purple)
  ![License](https://img.shields.io/badge/License-MIT-green)
</div>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Key Features](#-key-features)
- [System Architecture](#-system-architecture)
- [User Roles](#-user-roles)
- [Technology Stack](#-technology-stack)
- [Installation & Setup](#-installation--setup)
- [Database Structure](#-database-structure)
- [Project Structure](#-project-structure)
- [Authentication Flow](#-authentication-flow)
- [Screenshots](#-screenshots)
- [API Documentation](#-api-documentation)
- [Contributing](#-contributing)
- [Security Guidelines](#-security-guidelines)
- [License](#-license)
- [Support](#-support)

---

## 🌟 Overview

The DUET Hall Management System (HMS) is an integrated web-based platform designed to modernize and streamline the administration of university residence halls. Developed for the Dhaka University of Engineering & Technology, this system addresses key challenges in hall management through a secure, user-friendly interface that serves multiple stakeholders including administrators, provosts, staff, and students.

The system facilitates efficient communication, transparent resource allocation, and simplified administrative processes while providing a seamless digital experience for all users.

---

## 🚀 Key Features

### 🔐 Authentication & Authorization
- **Multi-role access control** - Admin, Provost, Staff, and Student portals
- **Social login integration** - Google OAuth and Microsoft Azure AD
- **Advanced security measures** - CSRF protection, rate limiting, secure sessions
- **Role-specific permissions** - Tailored access based on user roles

### 👥 User Management
- **Comprehensive profiles** - Detailed user information with profile pictures
- **Admin dashboard** - System-wide monitoring and management
- **Provost approval workflow** - Structured approval process for hall authorities
- **Staff management** - Track and manage hall staff efficiently

### 🏢 Hall Administration
- **Hall assignment system** - Automated hall and room allocation
- **Room management** - Track occupancy, maintenance status, and availability
- **Staff assignment** - Link staff to specific halls with defined responsibilities
- **Maintenance requests** - Digital tracking of maintenance needs and status

### 🍽️ Meal Management System
- **Digital credit system** - Prepaid meal credits with online recharge
- **Meal scheduling** - Calendar-based meal planning for students
- **Real-time cancellation** - Flexible meal cancellation system
- **Menu management** - Daily menu planning and publication
- **Minimum recharge enforcement** - Maintain system financial stability

### 📢 Notice Management
- **Digital notice board** - Paperless communication
- **Hall-specific notices** - Targeted information distribution
- **File attachments** - Support for documents, images, and other files
- **Advanced filtering** - Category and date-based notice filtering
- **Importance levels** - Highlight critical announcements

### 📍 Location Management
- **Bangladesh geographic hierarchy** - Division > District > Upazila structure
- **Dynamic location selection** - Interactive location picker
- **Address standardization** - Consistent address formatting across the system

---

## 🏗️ System Architecture

The HMS follows a layered architecture pattern:

1. **Presentation Layer** - Bootstrap-based responsive UI
2. **Controller Layer** - PHP action handlers and route processors
3. **Service Layer** - Business logic encapsulated in models
4. **Data Access Layer** - PDO-based database operations
5. **Database Layer** - MySQL/MariaDB relational database

The system utilizes REST principles for API endpoints and follows MVC design patterns for organized code structure.

---

## 👤 User Roles

| Role | Permissions | Dashboard Features |
|------|-------------|-------------------|
| **Admin** | System-wide access, User management, Hall setup | Hall statistics, Student management, Provost approvals |
| **Provost** | Hall-specific management, Notice posting, Staff management | Hall overview, Notice board, Staff management |
| **Staff** | Room maintenance, Basic student support | Room status, Maintenance requests |
| **Student** | Profile updates, Meal management, Notice viewing | Personal status, Meal planner, Notice board |

---

## 💻 Technology Stack

### Backend Technologies
- **PHP 7.4+** - Core server-side language
- **MySQL/MariaDB** - Relational database
- **PDO** - Database abstraction and security
- **Composer** - Dependency management

### Frontend Technologies
- **HTML5/CSS3** - Modern markup and styling
- **JavaScript** - Client-side interactivity
- **Bootstrap 5** - Responsive design framework
- **Font Awesome** - Icon library

### Security Implementation
- **Session management** - Secure server-side sessions
- **CSRF protection** - Cross-site request forgery prevention
- **Rate limiting** - Brute force attack prevention
- **Input validation** - Client and server-side validation
- **Prepared statements** - SQL injection prevention

### External Services Integration
- **Google OAuth2** - Google authentication
- **Microsoft Azure AD** - Microsoft authentication
- **Firebase JWT** - Token authentication

---

## 📦 Installation & Setup

### Prerequisites
- XAMPP, WAMP, LAMP, or MAMP stack
- PHP 7.4+
- MySQL/MariaDB
- Composer
- Web server (Apache/Nginx)

### Step-by-Step Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/HMS.git
   cd HMS
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Database setup:**
   ```bash
   mysql -u root -p
   CREATE DATABASE hms;
   USE hms;
   source database/schema.sql
   source database/locations_data.sql
   ```

4. **Configure the application:**
   - Create configuration files:
     ```bash
     cp config/database.example.php config/database.php
     cp config/google.example.php config/google.php
     cp config/microsoft.example.php config/microsoft.php
     ```
   - Update database credentials in `config/database.php`
   - Set up OAuth credentials in config files

5. **Directory permissions:**
   ```bash
   chmod 775 uploads/
   chmod 775 uploads/notices/
   ```

6. **Virtual host configuration (optional):**
   ```apache
   <VirtualHost *:80>
       DocumentRoot "c:/xampp/htdocs/HMS"
       ServerName hms.local
       <Directory "c:/xampp/htdocs/HMS">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

7. **Create initial admin user:**
   ```bash
   mysql -u root -p hms < database/admin_user.sql
   ```

### Configuration Files

- `config/database.php` - Database connection details
- `config/google.php` - Google OAuth credentials
- `config/microsoft.php` - Microsoft Azure AD settings

---

## 🗃️ Database Structure

The system consists of several interconnected tables:

### Core Tables
- **users** - Authentication and basic user information
- **student_profiles** - Extended student information
- **staff_profiles** - Staff member details
- **admin_profiles** - Administrator information

### Hall Management
- **halls** - Hall information and capacity
- **rooms** - Room details and status
- **provost_approvals** - Provost assignment workflow

### Meal System
- **meal_schedules** - Student meal plan calendar
- **meal_menu** - Daily menu items
- **meal_statistics** - Usage analytics
- **student_meal_credits** - Digital credit balance
- **credit_transactions** - Credit history

### Communication
- **notices** - Announcements and notices
- **notice_attachments** - Files linked to notices

### Location Data
- **divisions** - Administrative divisions of Bangladesh
- **districts** - Districts within divisions
- **upazilas** - Sub-districts

### Entity-Relationship Diagram
![ER Diagram](https://example.com/er-diagram.png)

---

## 📁 Project Structure

```
HMS/
├── actions/          # Action handlers and form processors
├── api/              # RESTful API endpoints for AJAX operations
├── assets/           # Frontend resources (CSS, JS, images)
│   ├── css/          # Stylesheets
│   ├── js/           # JavaScript files
│   └── images/       # System images and icons
├── auth/             # Authentication controllers
├── config/           # Configuration files
├── dashboard/        # Role-specific dashboards
├── database/         # SQL schemas and migrations
├── includes/         # Common PHP includes
├── meals/            # Meal management system
├── models/           # Data models and business logic
├── profiles/         # User profile management
├── uploads/          # User uploaded content
│   └── notices/      # Notice attachments storage
└── vendor/           # Composer dependencies
```

---

## 🔄 Authentication Flow

### Google Authentication
1. User selects "Login with Google"
2. System redirects to Google OAuth
3. User authenticates with Google
4. Google redirects back with access token
5. System verifies token and creates/updates user
6. Role-specific redirection occurs

### Microsoft Authentication
1. User selects "Login with Microsoft"
2. System redirects to Azure AD OAuth
3. User authenticates with Microsoft credentials
4. Microsoft redirects back with access token
5. System verifies token and creates/updates user
6. Role-specific redirection occurs

### Admin Authentication
Utilizes secure form-based authentication with rate limiting and lockouts after failed attempts.

---

## 📸 Screenshots

<div align="center">
  <img src="https://example.com/screenshot-login.png" width="45%" alt="Login Screen">
  <img src="https://example.com/screenshot-dashboard.png" width="45%" alt="Dashboard">
  <img src="https://example.com/screenshot-meals.png" width="45%" alt="Meal Management">
  <img src="https://example.com/screenshot-notices.png" width="45%" alt="Notice Board">
</div>

---

## 📜 API Documentation

The system includes several internal APIs for dynamic content:

### `/api/notices.php`
- `GET /api/notices.php` - List notices with pagination
- `GET /api/notices.php?id=X` - Get specific notice details
- `POST /api/notices.php` - Create new notice
- `DELETE /api/notices.php?id=X` - Remove notice

### `/api/locations.php`
- `GET /api/locations.php?type=divisions` - List all divisions
- `GET /api/locations.php?type=districts&division_id=X` - Districts by division
- `GET /api/locations.php?type=upazilas&district_id=X` - Upazilas by district

---

## 🤝 Contributing

We welcome contributions to improve the Hall Management System:

1. **Fork the repository**
2. **Create a feature branch:**
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Make your changes and commit:**
   ```bash
   git commit -m 'Add some amazing feature'
   ```
4. **Push to the branch:**
   ```bash
   git push origin feature/amazing-feature
   ```
5. **Create a Pull Request**

### Development Guidelines
- Follow PSR-12 coding standards
- Include descriptive comments
- Write meaningful commit messages
- Test thoroughly before submitting

---

## 🔒 Security Guidelines

The system implements several security measures:

- **Configuration Protection** - Keep sensitive files secure
- **Regular Updates** - Maintain latest dependencies
- **Input Validation** - Validate and sanitize all inputs
- **Prepared Statements** - Prevent SQL injection
- **CSRF Tokens** - Protect against cross-site request forgery
- **Rate Limiting** - Prevent brute force attacks
- **Session Security** - Secure cookie handling

### Security Checklist
- [ ] Keep configuration files outside web root
- [ ] Use HTTPS for production deployment
- [ ] Implement proper error logging
- [ ] Regularly audit user permissions
- [ ] Set appropriate file permissions

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🆘 Support

For support and questions about the Hall Management System:

- **Email:** 2204045@student.duet.ac.bd
- **Issue Tracker:** [GitHub Issues](https://github.com/yourusername/HMS/issues)
- **Documentation:** [Project Wiki](https://github.com/yourusername/HMS/wiki)

For immediate assistance, contact the system administrator or raise an issue on the project repository.

---

<div align="center">
  <p>Developed with @ MD RONY HOSSEN <p>
</div>
