Reclamation Management System
The Reclamation Management System is a web application module designed to allow users to submit, manage, and track reclamations (complaints or issues) related to a service or product. It includes features for voice input, real-time validation, and integration with an AI-powered API for processing reclamation details.
Features

User authentication with login/logout functionality
Submit reclamations with voice input support using the Web Speech API
Fields for title, description, location, and problem type (mechanical, battery, screen, tire, other)
Real-time form validation with custom error messages
Flash messages for success/error feedback
Multilingual support (English and French) with dynamic language switching
Integration with Gemini API for intelligent text extraction from voice input
Responsive design with a custom CSS stylesheet
Secure session management

Technologies Used

PHP (backend with session management)
JavaScript (frontend logic and Speech Recognition)
Web Speech API for voice input
Gemini API for AI-powered text processing
HTML5 and CSS3 for structure and styling
MySQL/MariaDB (assumed for data storage)

Setup Instructions

Clone the RepositoryDownload or clone this project to your local machine.

Database Setup  

Ensure a MySQL/MariaDB database is set up with a table to store reclamation data (e.g., reclamations table with columns for user_id, titre, description, lieu, type_probleme, etc.).
Update database credentials in your configuration file (e.g., config/database.php).


Configure XAMPP  

Place the project folder in your XAMPP htdocs directory (e.g., C:\xampp\htdocs\green-tn).
Start Apache and MySQL from the XAMPP control panel.


API Configuration  

Obtain a Gemini API key from Google AI and replace the placeholder "AIzaSyABlV8PDgpUhcUV9GLGD_w_s8dpQ6LAeHQ" in ajouter_reclamation.php with your actual key.
Ensure the API endpoint URL is correct: https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent.


Access the Application  

Open your browser and go to:http://localhost/green-tn/views/ajouter_reclamation.php


Default User Login  

Register or log in with an existing user account. Session-based authentication is required to submit reclamations.



Folder Structure

views/ — Contains the main view files (e.g., ajouter_reclamation.php)
controllers/ — Handles business logic (e.g., ReclamationController.php)
image/ — Stores icons and images (e.g., mic.png, ve.png)
translate.php — Manages multilingual translations

Customization

To add new languages, extend the translation array in translate.php.
To modify the supported problem types, update the $options array in ajouter_reclamation.php.
Adjust the CSS in ajouter_reclamation.php to match your preferred design.

Security

Sessions are managed with session_start() to track user authentication.
Form data is preserved in session flash messages for validation errors.
Input is sanitized using htmlspecialchars() to prevent XSS.

Contributing
Feel free to submit pull requests or suggest improvements! For significant changes, please open an issue to discuss first.
License
This project is for educational and personal use.

ZNAIDI Habib
