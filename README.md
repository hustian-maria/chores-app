# Household Services Platform

A complete household services booking platform built with PHP, MySQL, HTML, and CSS. This platform connects users who need household services with qualified workers who can provide those services.

## Features

- **User Registration & Login**: Users can register, book services, and manage their bookings
- **Worker Registration & Login**: Workers can register with their skills and accept jobs
- **Service Booking**: Users can book various household services with preferred dates and times
- **Dashboard Management**: Separate dashboards for users and workers with full functionality
- **Admin Panel**: Complete admin dashboard for managing users, workers, and bookings
- **Real-time Status Updates**: Track booking status from pending to completed
- **Secure Authentication**: Password hashing and session management
- **Responsive Design**: Mobile-friendly interface with modern CSS

## Installation

### Prerequisites
- XAMPP (or similar web server with PHP and MySQL)
- Web browser

### Setup Instructions

1. **Start XAMPP**
   - Start Apache and MySQL services from XAMPP Control Panel

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the `database_schema.sql` file or run the SQL commands manually
   - This will create the `chores_app` database with all required tables

3. **Deploy Files**
   - Copy the entire `chores_app` folder to `C:\xampp\htdocs\` (or your htdocs directory)
   - Ensure the folder structure is maintained

4. **Access the Application**
   - Open your browser and navigate to: `http://localhost/chores_app/`
   - You'll see the homepage with options to register or login

## File Structure

```
chores_app/
├── index.html                 # Homepage
├── user_register.html         # User registration form
├── worker_register.html        # Worker registration form
├── user_login.html            # User login form
├── worker_login.html          # Worker login form
├── register.php              # Registration handler
├── login.php                 # Login handler
├── user_dashboard.php        # User dashboard
├── worker_dashboard.php      # Worker dashboard
├── admin_dashboard.php       # Admin dashboard
├── logout.php                # Logout handler
├── style.css                 # Global styles
├── database_schema.sql       # MySQL database schema
└── README.md                 # This file
```

## Database Schema

### Tables

1. **users**
   - id, name, email, phone, address, password, created_at

2. **workers**
   - id, name, email, phone, password, skills, created_at

3. **bookings**
   - id, user_email, service, date, time, status, worker_email, created_at

## How to Use

### For Users
1. Register as a user with your details
2. Select a service, date, and time during registration
3. View your bookings in the user dashboard
4. Cancel or reschedule bookings if needed
5. Track booking status updates

### For Workers
1. Register as a worker with your skills
2. View available jobs matching your skills
3. Accept or decline job offers
4. Mark jobs as completed when done
5. Track your work history

### For Admins
1. Access admin dashboard (password: admin123)
2. View all users, workers, and bookings
3. Delete users, workers, or bookings as needed
4. Monitor platform statistics

## Available Services

- House Cleaning
- Cooking
- Laundry
- Gardening
- Plumbing
- Electrical Work
- Carpentry
- Painting
- Moving Help
- Pet Care

## Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- SQL injection prevention with prepared statements
- Input validation and sanitization
- CSRF protection in forms

## Customization

### Changing Admin Password
Edit the password in `admin_dashboard.php` (line ~15):
```php
if ($_POST['admin_password'] === 'your_new_password') {
```

### Adding New Services
1. Update the service options in `user_register.html`
2. Update the skills checkboxes in `worker_register.html`
3. No database changes needed (services are stored as strings)

### Styling
All styles are in `style.css`. The CSS uses:
- Modern gradient backgrounds
- Responsive grid layouts
- Card-based design
- Smooth transitions and hover effects

## Browser Support

- Chrome/Chromium 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check that the `chores_app` database exists
   - Verify database credentials in PHP files

2. **404 Not Found Errors**
   - Ensure files are in the correct `htdocs/chores_app/` directory
   - Check that Apache is running

3. **Session Issues**
   - Ensure PHP sessions are enabled
   - Check browser cookie settings

4. **Permission Issues**
   - Ensure XAMPP has write permissions if needed
   - Check file permissions on the project folder

## License

This project is for educational purposes. Feel free to modify and use it for learning or development.

## Support

For issues or questions, check the troubleshooting section or verify your XAMPP installation is working correctly.
