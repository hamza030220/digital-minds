<?php
/**
 * UserModel Class
 * 
 * Handles all database operations related to users and authentication
 */
class UserModel {
    private $pdo;
    private $error;

    /**
     * Constructor - establishes database connection
     */
    public function __construct() {
        try {
            $this->pdo = getDBConnection();
        } catch (PDOException $e) {
            $this->error = "Database connection failed: " . $e->getMessage();
            error_log($this->error);
        }
    }

    /**
     * Authenticate a user
     * 
     * @param string $username Username
     * @param string $password Password
     * @return array|false User data or false if authentication failed
     */
    public function authenticate($username, $password) {
        try {
            // Validate input
            if (empty($username) || empty($password)) {
                $this->error = "Username and password are required";
                return false;
            }
            
            // Get user by username
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bindValue(1, $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!$user) {
                $this->error = "Invalid username or password";
                return false;
            }
            
            // Verify password
            if (!$this->verifyPassword($password, $user['password'])) {
                $this->error = "Invalid username or password";
                return false;
            }
            
            // Remove password from result
            unset($user['password']);
            
            // Update last login timestamp
            $this->updateLastLogin($user['id']);
            
            return $user;
        } catch (PDOException $e) {
            $this->error = "Authentication error: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Verify a password against a hash
     * 
     * @param string $password Plain password
     * @param string $hash Password hash
     * @return boolean True if password matches hash
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Hash a password securely
     * 
     * @param string $password Plain password
     * @return string Hashed password
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Update the last login timestamp for a user
     * 
     * @param int $userId User ID
     * @return boolean Success status
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Error updating last login: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $id User ID
     * @return array|false User data or false if not found
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?");
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!$user) {
                $this->error = "User not found";
                return false;
            }
            
            return $user;
        } catch (PDOException $e) {
            $this->error = "Error retrieving user: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Get user by username
     * 
     * @param string $username Username
     * @return array|false User data or false if not found
     */
    public function getByUsername($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE username = ?");
            $stmt->bindValue(1, $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!$user) {
                $this->error = "User not found";
                return false;
            }
            
            return $user;
        } catch (PDOException $e) {
            $this->error = "Error retrieving user: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Get user by email
     * 
     * @param string $email Email address
     * @return array|false User data or false if not found
     */
    public function getByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE email = ?");
            $stmt->bindValue(1, $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!$user) {
                $this->error = "User not found";
                return false;
            }
            
            return $user;
        } catch (PDOException $e) {
            $this->error = "Error retrieving user: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     * @return int|false New user ID or false on failure
     */
    public function create($data) {
        try {
            // Validate required fields
            $requiredFields = ['username', 'email', 'password'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->error = "Field '$field' is required";
                    return false;
                }
            }
            
            // Check if username already exists
            if ($this->getByUsername($data['username'])) {
                $this->error = "Username already exists";
                return false;
            }
            
            // Check if email already exists
            if ($this->getByEmail($data['email'])) {
                $this->error = "Email already exists";
                return false;
            }
            
            // Hash password
            $hashedPassword = $this->hashPassword($data['password']);
            
            // Set default role if not provided
            $role = isset($data['role']) ? $data['role'] : 'user';
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, role) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bindValue(1, $data['username'], PDO::PARAM_STR);
            $stmt->bindValue(2, $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(3, $hashedPassword, PDO::PARAM_STR);
            $stmt->bindValue(4, $role, PDO::PARAM_STR);
            $stmt->execute();
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->error = "Error creating user: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $newPassword New password
     * @return boolean Success status
     */
    public function updatePassword($userId, $newPassword) {
        try {
            // Validate password
            if (empty($newPassword)) {
                $this->error = "Password cannot be empty";
                return false;
            }
            
            // Validate user exists
            if (!$this->getById($userId)) {
                return false;
            }
            
            // Hash password
            $hashedPassword = $this->hashPassword($newPassword);
            
            // Update password
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bindValue(1, $hashedPassword, PDO::PARAM_STR);
            $stmt->bindValue(2, $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Error updating password: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Reset user password using email
     * 
     * @param string $email User email
     * @param string $newPassword New password
     * @return boolean Success status
     */
    public function resetPassword($email, $newPassword) {
        try {
            // Get user by email
            $user = $this->getByEmail($email);
            if (!$user) {
                return false;
            }
            
            // Update the password
            return $this->updatePassword($user['id'], $newPassword);
        } catch (PDOException $e) {
            $this->error = "Error resetting password: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Generate a password reset token
     * 
     * @param string $email User email
     * @return string|false Token or false on failure
     */
    public function generateResetToken($email) {
        try {
            // Get user by email
            $user = $this->getByEmail($email);
            if (!$user) {
                return false;
            }
            
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
            
            // Store token in database
            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (user_id, token, expiry) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = ?, expiry = ?
            ");
            $stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $token, PDO::PARAM_STR);
            $stmt->bindValue(3, $expiry, PDO::PARAM_STR);
            $stmt->bindValue(4, $token, PDO::PARAM_STR);
            $stmt->bindValue(5, $expiry, PDO::PARAM_STR);
            $stmt->execute();
            
            return $token;
        } catch (PDOException $e) {
            $this->error = "Error generating reset token: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Verify a password reset token
     * 
     * @param string $email User email
     * @param string $token Reset token
     * @return int|false User ID or false if invalid
     */
    public function verifyResetToken($email, $token) {
        try {
            // Get user by email
            $user = $this->getByEmail($email);
            if (!$user) {
                return false;
            }
            
            // Verify token exists and is not expired
            $stmt = $this->pdo->prepare("
                SELECT * FROM password_resets 
                WHERE user_id = ? AND token = ? AND expiry > NOW()
            ");
            $stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $token, PDO::PARAM_STR);
            $stmt->execute();
            
            $reset = $stmt->fetch();
            if (!$reset) {
                $this->error = "Invalid or expired reset token";
                return false;
            }
            
            return $user['id'];
        } catch (PDOException $e) {
            $this->error = "Error verifying reset token: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Get error message
     * 
     * @return string Error message
     */
    public function getError() {
        return $this->error;
    }
}


/**
 * UserModel Class
 * 
 * Handles all database operations related to users and authentication
 */
class UserModel {
    private $pdo;
    private $error;

    /**
     * Constructor - establishes database connection
     */
    public function __construct() {
        try {
            $this->pdo = getDBConnection();
        } catch (PDOException $e) {
            $this->error = "Database connection failed: " . $e->getMessage();
            error_log($this->error);
        }
    }

    /**
     * Authenticate a user
     * 
     * @param string $username Username
     * @param string $password Password
     * @return array|false User data or false if authentication failed
     */
    public function authenticate($username, $password) {
        try {
            // Validate input
            if (empty($username) || empty($password)) {
                $this->error = "Username and password are required";
                return false;
            }
            
            // Get user by username
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bindValue(1, $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!$user) {
                $this->error = "Invalid username or password";
                return false;
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->error = "Invalid username or password";
                return false;
            }
            
            // Remove password from result
            unset($user['password']);
            
            // Update last login timestamp
            $this->updateLastLogin($user['id']);
            
            return $user;
        } catch (PDOException $e) {
            $this->error = "Authentication error: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Update the last login timestamp for a user
     * 
     * @param int $userId User ID
     * @return boolean Success status
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Error updating last login: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $id User ID
     * @return array|false User data or false if not found
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?");
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!$user) {
                $this->error = "User not found";
                return false;
            }
            
            return $user;
        } catch (PDOException $e) {
            $this->error = "Error retrieving user: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Get user by username
     * 
     * @param string $username Username
     * @return array|false User data or false if not found
     */
    public function getByUsername($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE username = ?");
            $stmt->bindValue(1, $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!$user) {
                $this->error = "User not found";
                return false;
            }
            
            return $user;
        } catch (PDOException $e) {
            $this->error = "Error retrieving user: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Get user by email
     * 
     * @param string $email Email address
     * @return array|false User data or false if not found
     */
    public function getByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE email = ?");
            $stmt->bindValue(1, $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!$user) {
                $this->error = "User not found";
                return false;
            }
            
            return $user;
        } catch (PDOException $e) {
            $this->error = "Error retrieving user: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     * @return int|false New user ID or false on failure
     */
    public function create($data) {
        try {
            // Validate required fields
            $requiredFields = ['username', 'email', 'password'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->error = "Field '$field' is required";
                    return false;
                }
            }
            
            // Check if username already exists
            if ($this->getByUsername($data['username'])) {
                $this->error = "Username already exists";
                return false;
            }
            
            // Check if email already exists
            if ($this->getByEmail($data['email'])) {
                $this->error = "Email already exists";
                return false;
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Set default role if not provided
            $role = isset($data['role']) ? $data['role'] : 'user';
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, role) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bindValue(1, $data['username'], PDO::PARAM_STR);
            $stmt->bindValue(2, $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(3, $hashedPassword, PDO::PARAM_STR);
            $stmt->bindValue(4, $role, PDO::PARAM_STR);
            $stmt->execute();
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->error = "Error creating user: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $newPassword New password
     * @return boolean Success status
     */
    public function updatePassword($userId, $newPassword) {
        try {
            // Validate password
            if (empty($newPassword)) {
                $this->error = "Password cannot be empty";
                return false;
            }
            
            // Validate user exists
            if (!$this->getById($userId)) {
                return false;
            }
            
            // Hash password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bindValue(1, $hashedPassword, PDO::PARAM_STR);
            $stmt->bindValue(2, $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Error updating password: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Reset user password using email
     * 
     * @param string $email User email
     * @param string $newPassword New password
     * @return boolean Success status
     */
    public function resetPassword($email, $newPassword) {
        try {
            // Get user by email
            $user = $this->getByEmail($email);
            if (!$user) {
                return false;
            }
            
            // Update the password
            return $this->updatePassword($user['id'], $newPassword);
        } catch (PDOException $e) {
            $this->error = "Error resetting password: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Generate a password reset token
     * 
     * @param string $email User email
     * @return string|false Token or false on failure
     */
    public function generateResetToken($email) {
        try {
            // Get user by email
            $user = $this->getByEmail($email);
            if (!$user) {
                return false;
            }
            
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
            
            // Store token in database
            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (user_id, token, expiry) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = ?, expiry = ?
            ");
            $stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $token, PDO::PARAM_STR);
            $stmt->bindValue(3, $expiry, PDO::PARAM_STR);
            $stmt->bindValue(4, $token, PDO::PARAM_STR);
            $stmt->bindValue(5, $expiry, PDO::PARAM_STR);
            $stmt->execute();
            
            return $token;
        } catch (PDOException $e) {
            $this->error = "Error generating reset token: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Verify a password reset token
     * 
     * @param string $email User email
     * @param string $token Reset token
     * @return int|false User ID or false if invalid
     */
    public function verifyResetToken($email, $token) {
        try {
            // Get user by email
            $user = $this->getByEmail($email);
            if (!$user) {
                return false;
            }
            
            // Verify token
            $stmt = $this->pdo->prepare("
                SELECT * FROM password_resets 
                WHERE user_id = ? AND token = ? AND expiry > NOW()
            ");
            $stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $token, PDO::PARAM_STR);
            $stmt->execute();
            
            $reset = $stmt->fetch();
            if (!$reset) {
                $this->error = "Invalid or expired reset token";
                return false;
            }
            
            return $user['id'];
        } catch (PDOException $e) {
            $this->error = "Error verifying reset token: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }
    
    /**
     * Get error message
     * 
     * @return string Error message
     */
    public function getError() {
        return $this->error;
    }
}
?>
