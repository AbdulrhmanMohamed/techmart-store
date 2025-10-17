# TechMart - Ecommerce Application

A modern ecommerce application built with PHP and Tailwind CSS, featuring a clean and responsive design.

## Features

- Product catalog with categories
- Shopping cart functionality
- Responsive design with Tailwind CSS
- MySQL database
- Easy deployment on shared hosting or VPS

## Local Development Setup

### Prerequisites

- Docker and Docker Compose
- PHP 7.4+ (if running PHP locally)

### Quick Start

1. Clone or download this project
2. Run the following command to start the development environment:

```bash
docker-compose up -d
```

This will start:
- MySQL database on port 3306
- phpMyAdmin on port 8080

3. Access the application:
   - Main site: http://localhost:8000 (if using PHP built-in server)
   - phpMyAdmin: http://localhost:8080

### Using PHP Built-in Server

```bash
php -S localhost:8000
```

Then visit http://localhost:8000

### Database Configuration

The application is configured to connect to:
- Host: localhost
- Database: phpstore
- Username: phpstore_user
- Password: phpstore_pass

## Project Structure

```
phpstore/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── functions.php         # Helper functions
│   ├── header.php           # Site header
│   └── footer.php           # Site footer
├── database/
│   └── init.sql             # Database initialization
├── docker-compose.yml       # Docker configuration
├── index.php               # Homepage
└── README.md               # This file
```

## Next Steps

1. Set up user authentication
2. Implement shopping cart functionality
3. Add payment processing
4. Create admin panel
5. Add product management features

## Deployment

For shared hosting:
1. Upload all files to your web directory
2. Import the database using phpMyAdmin
3. Update database configuration if needed

For VPS:
1. Install PHP, MySQL, and web server
2. Upload files and configure database
3. Set up SSL certificate
4. Configure domain and DNS
