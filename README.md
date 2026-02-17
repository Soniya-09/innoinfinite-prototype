# Software Consultancy Management System

A comprehensive web-based system for managing software consultancy operations, supporting both Admin and Client roles on a single platform.

## 🎯 Features

### Admin Features
- **Service Management**: Add, update, and delete consultancy services
- **Client Management**: View and manage client accounts
- **Request Management**: Review and approve/reject service requests
- **Proposal System**: Create and send project proposals with quotations
- **Project Management**: Track projects, update status, manage milestones
- **Invoice Management**: Generate invoices and track payments
- **Messaging**: Communicate with clients
- **Reports & Analytics**: View business statistics and revenue reports

### Client Features
- **Service Browsing**: View available consultancy services
- **Request Submission**: Submit service or project requests
- **Proposal Review**: View and respond to proposals
- **Project Tracking**: Monitor project progress and milestones
- **Invoice Management**: View invoices and payment status
- **Payment Processing**: Make payments using dummy payment module
- **Messaging**: Communicate with the company
- **Notifications**: Receive updates on requests, proposals, and projects

## 🛠️ Tech Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
- **Backend**: PHP
- **Database**: MySQL
- **Authentication**: Session-based
- **Payment**: Dummy payment module

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Web browser

## 🚀 Installation

1. **Clone or download the project** to your web server directory (e.g., `htdocs` for XAMPP or `www` for WAMP)

2. **Create the database**:
   - Open phpMyAdmin or MySQL command line
   - Import the `database/schema.sql` file to create the database and tables
   - Or run: `mysql -u root -p < database/schema.sql`

3. **Configure database connection**:
   - Edit `config/database.php`
   - Update the database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'consultancy_management');
     ```

4. **Set up admin account**:
   - Run `setup.php` in your browser: `http://localhost/consultancy_management/setup.php`
   - This will create/update the admin account with the correct password hash
   - **Important**: Delete `setup.php` after running it for security

5. **Set up the web server**:
   - Ensure your web server is running
   - Access the application via: `http://localhost/consultancy_management/`

## 🔐 Default Login Credentials

### Admin Account
- **Username**: `admin`
- **Password**: `admin123`

### Client Account
- Register a new account through the registration page

## 📁 Project Structure

```
consultancy_management/
├── admin/              # Admin dashboard and features
│   ├── dashboard.php
│   ├── services.php
│   ├── clients.php
│   ├── requests.php
│   ├── projects.php
│   ├── invoices.php
│   ├── messages.php
│   └── reports.php
├── client/             # Client portal
│   ├── dashboard.php
│   ├── services.php
│   ├── requests.php
│   ├── projects.php
│   ├── invoices.php
│   └── messages.php
├── auth/               # Authentication pages
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── profile.php
├── config/             # Configuration files
│   ├── config.php
│   ├── database.php
│   └── session.php
├── includes/           # Shared components
│   ├── header.php
│   └── footer.php
├── assets/             # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── database/           # Database schema
│   └── schema.sql
├── index.php           # Home page
└── README.md           # This file
```

## 🔄 Workflow

1. **Client Registration**: Clients register and create an account
2. **Service Request**: Clients browse services and submit requests
3. **Admin Review**: Admin reviews requests and creates proposals
4. **Proposal Acceptance**: Client accepts/rejects proposals
5. **Project Creation**: Upon acceptance, a project is created
6. **Project Management**: Admin manages project progress and milestones
7. **Invoice Generation**: Admin generates invoices for completed work
8. **Payment Processing**: Client makes payments through the dummy payment module
9. **Communication**: Both parties can communicate through the messaging system

## 🎨 Features in Detail

### Authentication & Security
- Session-based authentication
- Role-based access control (Admin/Client)
- Secure password hashing
- Session management

### Project Workflow
- Requested → Approved → In Progress → Completed
- Milestone tracking
- Progress percentage updates

### Payment System
- Dummy payment module for demonstration
- Payment history tracking
- Invoice status management

### Notifications
- Real-time notifications for important events
- Unread notification counts
- Notification types: request, proposal, project, invoice, payment, message

## 📝 Notes

- This is a demonstration system for internship/learning purposes
- The payment module is a dummy implementation for testing
- All data is stored in MySQL database
- The system uses Bootstrap 5 for responsive design
- Session-based authentication ensures secure access

## 🔧 Troubleshooting

1. **Database Connection Error**:
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database name exists

2. **Session Issues**:
   - Ensure PHP sessions are enabled
   - Check file permissions

3. **Page Not Found**:
   - Verify web server is running
   - Check URL path is correct
   - Ensure `.htaccess` (if used) is configured properly

## 📄 License

This project is created for educational/internship purposes.

## 👨‍💻 Development

For development and customization:
- Modify database schema in `database/schema.sql`
- Update styles in `assets/css/style.css`
- Add JavaScript functionality in `assets/js/main.js`
- Customize pages in respective directories

## 🎓 Learning Outcomes

This project demonstrates:
- Full-stack web development
- Database design and management
- User authentication and authorization
- CRUD operations
- Role-based access control
- Payment processing workflow
- Project management concepts
- Real-world application development

---

**Note**: This is a complete, working system ready for demonstration and learning purposes.
