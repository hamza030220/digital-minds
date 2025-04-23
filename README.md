# Green Admin

Green Admin is a web application for managing eco-friendly routes ("trajets") and charging stations ("stations"). It allows administrators to add, edit, and visualize routes and stations on an interactive map, calculate environmental savings, and manage user access.

## Features

- Add, edit, and delete routes with interactive map drawing (Leaflet.js)
- Add, edit, and delete charging stations with geolocation
- Calculate distance, CO₂ savings, battery energy, and fuel saved for each route
- Secure admin authentication
- Responsive Bootstrap-based UI
- Data stored in a MariaDB/MySQL database

## Technologies Used

- PHP (backend)
- MySQL/MariaDB (database)
- JavaScript (frontend logic)
- [Leaflet.js](https://leafletjs.com/) for interactive maps
- [Leaflet Draw](https://github.com/Leaflet/Leaflet.draw) for route drawing
- [Bootstrap 5](https://getbootstrap.com/) for UI
- [Axios](https://axios-http.com/) for HTTP requests

## Setup Instructions

1. **Clone the repository**  
   Download or clone this project to your local machine.

2. **Database Setup**  
   - Import the provided SQL file (`database/green_db.sql`) into your MySQL/MariaDB server.
   - Update the database credentials in `includes/config.php` to match your environment.

3. **Configure XAMPP**  
   - Place the project folder in your XAMPP `htdocs` directory.
   - Start Apache and MySQL from the XAMPP control panel.

4. **Access the Application**  
   - Open your browser and go to:  
     `http://localhost/just in case/`

5. **Default Admin Login**  
   - Username: `admin`
   - Password: (see the `admins` table in your database; you may need to reset it manually)

## Folder Structure

- `assets/` — JavaScript, CSS, and images
- `trajets/` — Route management (add, edit, list, delete)
- `stations/` — Station management (add, edit, list, delete)
- `includes/` — Shared PHP includes (config, sidebar)
- `public/` — Public assets (CSS, images, etc.)
- `database/` — Database SQL dump

## Customization

- To change the map’s default location, edit the coordinates in the JavaScript initialization in `trajets/add.php` and `trajets/edit.php`.
- To add new admin users, insert them into the `admins` table in the database.

## Security

- CSRF protection is implemented on all forms.
- Passwords are hashed using PHP’s `password_hash`.

## Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

## License

This project is for educational and demonstration purposes.

---

*Made with ❤️ HAMZA LE Z 😝