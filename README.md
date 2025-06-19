
Built by https://www.blackbox.ai

---

# EFZEE COTTAGE - Luxury Retreat in Batu Pahat

## Project Overview

EFZEE COTTAGE is a web application designed for managing a homestay service in Batu Pahat. This application offers users the ability to view information about the homestay, make bookings, and even manage their accounts. Administrators can also log in to manage bookings, users, amenities, and payments.

## Installation

To install and run this project locally, follow these steps:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/efzee-cottage.git
   cd efzee-cottage
   ```

2. **Install dependencies:**
   Make sure you have a web server (like Apache or Nginx) and PHP with MySQL support installed.

3. **Create a database:**
   ```sql
   CREATE DATABASE cozyhomestay;
   ```

4. **Import the database structure:**
   Navigate to your MySQL interface (like phpMyAdmin) and import the database file (if provided).

5. **Configure the database connection:**
   Adjust the database connection settings in the `config.php` file to match your environment setup.

6. **Install Composer dependencies (if any):**
   If the project requires Composer packages, run:
   ```bash
   composer install
   ```

7. **Serve the Application:**
   Point your server to serve files in the project directory.

## Usage

1. Open your web browser and navigate to `http://localhost/efzee-cottage`.
2. Users can create an account or log in.
3. Once logged in, navigate through the home, about, gallery, and booking sections.
4. Admin users can access the admin dashboard to manage bookings, users, amenities, payments, and reviews.

## Features

- **User Features:**
  - User registration and login system
  - Booking management
  - View homestay details and amenities
  - Gallery showcasing images

- **Admin Features:**
  - Dashboard for managing homestay operations
  - Manage user accounts and roles
  - Control bookings and payment statuses
  - Review management with the ability to respond to customer reviews

## Dependencies

This project may depend on various libraries and frameworks. The key dependencies include:

- Bootstrap (for responsive layout)
- jQuery (for general utility)
- SweetAlert2 (for enhanced dialogs)
- Font Awesome (for icons)
- FullCalendar (for booking calendar functionality)

Check the `package.json` or `composer.json` for specific package versions.

## Project Structure

Here's a breakdown of the project structure:

```
efzee-cottage/
│
├── about.html          # About Us page
├── admin.html          # Admin Panel page
├── admin.php           # Backend logic for Admin functionality
├── index.html          # Home page
├── styles.css          # CSS styles for the application
├── gallery.html        # Gallery page
├── booking.html        # Booking page
│
├── config.php          # Database configuration
│
└── assets/             # Directory containing additional assets
    ├── css/            # CSS files
    ├── js/             # JavaScript files
    └── images/         # Image assets
```

Each HTML file represents a distinct page for both users and administrators, with clear separation of concerns to facilitate maintenance.

## Contributing

If you would like to contribute to this project, please fork the repository and submit a pull request with your proposed changes and improvements.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---
Feel free to replace `yourusername` with your actual GitHub username in the clone command and adjust any other project details as necessary.