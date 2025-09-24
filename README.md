# Full-Stack 3D SaaS Subscription Platform

This project is a complete, ready-to-use prototype of a Software as a Service (SaaS) platform built with PHP and MySQL on the backend, and vanilla JavaScript with Three.js on the frontend. It includes a robust user authentication system, a multi-tiered subscription model integrated with PayPal, and a 3D scene with image and video export capabilities.

## Features

-   **User Authentication**: Secure registration, login, logout, and password reset. Passwords are encrypted using `password_hash`.
-   **Subscription System**: Free and paid (monthly/yearly) subscription plans managed in a MySQL database.
-   **PayPal Integration**: Seamless subscription payments using PayPal's Subscription API.
-   **3D Scene & Export**: A homepage featuring a Three.js scene with a GLTF model. Users can export images (PNG/JPG) and videos (WEBM/MP4) using ccapture.js.
-   **Usage Tracking & Limits**: The system tracks exports per user and enforces monthly limits based on their subscription plan.
-   **User Dashboard**: A dedicated page for users to view their current plan, remaining credits, and manage their account.
-   **Admin Dashboard**: A secure area for administrators to manage users, plans, and offers, and to view revenue and usage statistics with charts (using Chart.js).
-   **Security & Limitations**:
    -   Limits free account creation to 3 per device/IP address.
    -   Disables video export for free users.
    -   Hides ads for subscribed users.
-   **UI/UX**:
    -   Responsive design with a default dark mode and a light/dark mode switcher.
    -   Multilingual support (English/Arabic) with translations loaded from a JSON file.
-   **Automated Maintenance**: Includes a script for a CRON job to automatically reset monthly usage credits and downgrade expired subscriptions.

## Project Structure

The project is organized into the following directories:

```
.
├── api/
│   ├── admin_data.php           # Backend for the admin dashboard
│   ├── cron_jobs.php            # Script for monthly resets and subscription checks
│   ├── db_connect.php           # Handles MySQL database connection
│   ├── forgot_password.php      # Initiates the password reset process
│   ├── login.php                # Handles user login
│   ├── logout.php               # Handles user logout
│   ├── paypal_transaction.php   # Verifies PayPal payments and updates subscriptions
│   ├── register.php             # Handles new user registration
│   ├── reset_password.php       # Finalizes the password reset process
│   ├── update_usage.php         # Updates user's export usage
│   └── user_status.php          # Fetches current user's session, subscription, and usage data
│
├── assets/
│   ├── css/
│   │   └── style.css            # Main stylesheet for the application
│   └── js/
│       ├── admin.js             # Frontend logic for the admin dashboard
│       ├── auth.js              # Handles login/register modal, forms, and PayPal buttons
│       ├── dashboard.js         # Frontend logic for the user dashboard
│       ├── main.js              # General UI logic (theme, language, user status)
│       └── scene.js             # Handles the Three.js scene, model loading, and export functionality
│
├── models/
│   └── scene.gltf               # Placeholder for the 3D model (must be provided)
│
├── public_html/
│   ├── admin.html               # Admin dashboard page
│   ├── auth-modal.html          # HTML content for the authentication popup
│   ├── dashboard.html           # User dashboard page
│   ├── index.html               # Main homepage with the 3D scene
│   └── reset_password.html      # Page for users to set a new password
│
├── cron_job_setup.md            # Instructions for setting up the CRON job
├── database.sql                 # The complete SQL schema for the project database
└── translations.json            # Contains all UI text for English and Arabic
```

## Setup Instructions

1.  **Database Setup**:
    -   Create a new MySQL database (e.g., `saas_project`).
    -   Import the `database.sql` file to create all the necessary tables and initial data (including the admin account).
    -   The default admin credentials are `ads@4dads.pro` / `Admin@1979#`.

2.  **Configuration**:
    -   Edit `api/db_connect.php` and enter your actual database server name, username, password, and database name.
    -   Edit `public_html/index.html` and replace `YOUR_PAYPAL_CLIENT_ID` in the PayPal SDK URL with your actual PayPal Client ID.

3.  **3D Model**:
    -   Place your own GLTF model file at `models/scene.gltf`.

4.  **CRON Job**:
    -   Follow the instructions in `cron_job_setup.md` to set up the automated script for monthly maintenance tasks on your server.

5.  **Deployment**:
    -   Upload all files to your web server. The web root should point to the `public_html` directory for better security.

---
*This prototype is for demonstration purposes. For a production environment, further security enhancements, such as comprehensive input validation, CSRF protection, and robust error handling, are recommended.*