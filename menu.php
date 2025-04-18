<header>
        <div class="logo">
            <h1>Green.tn</h1>
            <p>Mobilité durable, énergie propre</p>
        </div>
        <nav>
            <ul>
                <!-- Adjust links relative to the ROOT or use absolute paths -->
                <!-- If this view is in /views/, these need to point up or use root-relative paths -->
                <li><a href="./index.php">Accueil</a></li>
                <li><a href="./ajouter_reclamation.php">Nouvelle réclamation</a></li>
                <li><a href="liste_reclamations.php">Voir réclamations</a> 
            
                <li><a href="./logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <style>
        /* Basic Styles - same as before */
        body { font-family: sans-serif; line-height: 1.6; margin: 0; background-color: #f4f7f6; }
        .content-container { max-width: 1100px; margin: 30px auto; padding: 20px 30px; background-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 5px; }
        h2 { color: #333; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        .error-message { color: #D8000C; background-color: #FFD2D2; border: 1px solid #D8000C; padding: 10px 15px; margin: 20px 0; border-radius: 4px; }
        .info-message { color: #00529B; background-color: #BDE5F8; border: 1px solid #00529B; padding: 10px 15px; margin: 20px 0; border-radius: 4px; }
        .info-message a { color: #00529B; font-weight: bold; }
        .search-bar { margin-bottom: 25px; padding: 15px; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; display: flex; align-items: center; flex-wrap: wrap; gap: 10px;}
        .search-bar label { font-weight: bold; margin-right: 5px; }
        .search-bar input[type="text"] { padding: 8px 10px; border: 1px solid #ced4da; border-radius: 3px; flex-grow: 1; min-width: 150px;}
        .search-bar button { padding: 8px 15px; background-color: #5cb85c; color: white; border: none; border-radius: 3px; cursor: pointer; transition: background-color 0.2s ease; }
        .search-bar button:hover { background-color: #4cae4c; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background-color: #fff; }
        th, td { border: 1px solid #dee2e6; padding: 10px 15px; text-align: left; vertical-align: top; }
        th { background-color: #e9ecef; color: #495057; font-weight: bold; white-space: nowrap; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        /* Ensure buttons/links in cells display well */
        td a.btn { margin-right: 5px; margin-bottom: 5px; display: inline-block; /* Make sure they behave like buttons */ }
        .btn { display: inline-block; padding: 6px 12px; background-color: #5bc0de; color: white; text-decoration: none; border-radius: 3px; font-size: 0.9em; border: none; cursor: pointer; text-align: center; white-space: nowrap; }
        .btn:hover { opacity: 0.9; color: white; }
        .btn-danger { background-color: #d9534f; }
        .btn-danger:hover { background-color: #c9302c; }
        header { background-color:rgb(10, 73, 15); color: white; padding: 15px 0; }
        header .logo { text-align: center; margin-bottom: 10px; }
        header .logo h1 { margin: 0; font-size: 2em; color: #28a745; }
        header .logo p { margin: 0; font-size: 0.9em; color: #adb5bd; }
        nav { text-align: center; }
        nav ul { list-style: none; padding: 0; margin: 0; }
        nav ul li { display: inline-block; margin: 0 10px; }
        nav a { color: white; text-decoration: none; padding: 5px 10px; }
        nav a:hover { color: #28a745; }
        footer { text-align: center; padding: 20px; margin-top: 30px; background-color: #e9ecef; color: #6c757d; font-size: 0.9em; border-top: 1px solid #dee2e6;}
        
   </style>
    
    