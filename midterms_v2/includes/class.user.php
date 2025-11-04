<?php
require_once 'class.database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Register a new user
    public function register($username, $password, $role) {
        $this->db->query('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
        $this->db->bind(':username', $username);
        $this->db->bind(':password', password_hash($password, PASSWORD_DEFAULT)); // Hash password
        $this->db->bind(':role', $role);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    // Login user - NOW INCLUDES SUSPENSION CHECK
    public function login($username, $password) {
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);

        $row = $this->db->single();

        if ($row) {
            // Check if account is suspended BEFORE checking password
            if ($row->is_suspended == 1) {
                return 'suspended'; // Return special status if suspended
            }

            $hashed_password = $row->password;
            if (password_verify($password, $hashed_password)) {
                return $row; // Return user object on successful login
            } else {
                return false; // Password incorrect
            }
        } else {
            return false; // User not found
        }
    }
    
    // Find user by username
    public function findUserByUsername($username) {
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    // Get user by ID
    public function getUserById($id) {
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Get all admins (for Superadmin to view/manage) - NOW INCLUDES SUSPENDED FIELD
    public function getAllAdmins() {
        $this->db->query("SELECT id, username, role, is_suspended, created_at FROM users WHERE role = 'admin'");
        return $this->db->resultSet();
    }
    
    // NEW: Suspend a user account
    public function suspendUser($id) {
        // Prevent Superadmin from suspending themselves or other Superadmins (optional, but safe)
        $this->db->query('UPDATE users SET is_suspended = 1 WHERE id = :id AND role = "admin"');
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->execute();
    }

    // NEW: Unsuspend a user account
    public function unsuspendUser($id) {
        $this->db->query('UPDATE users SET is_suspended = 0 WHERE id = :id');
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->execute();
    }
}
?>
