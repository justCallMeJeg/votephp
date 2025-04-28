<?php
/**
 * Class to represent a user
 * Demonstrates OOP principles: Encapsulation
 */
class User {
    private $id;
    private $username;
    private $passwordHash;
    private $role;
    
    /**
     * Constructor
     * 
     * @param string $id Unique identifier
     * @param string $username Username
     * @param string $passwordHash Hashed password
     * @param string $role User role (admin or voter)
     */
    public function __construct($id, $username, $passwordHash, $role) {
        $this->id = $id;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
    }
    
    /**
     * Get user ID
     * 
     * @return string User ID
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Get username
     * 
     * @return string Username
     */
    public function getUsername() {
        return $this->username;
    }
    
    /**
     * Get password hash
     * 
     * @return string Password hash
     */
    public function getPasswordHash() {
        return $this->passwordHash;
    }
    
    /**
     * Get user role
     * 
     * @return string User role
     */
    public function getRole() {
        return $this->role;
    }
    
    /**
     * Verify password
     * 
     * @param string $password Plain text password
     * @return bool True if password matches, false otherwise
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->passwordHash);
    }
    
    /**
     * Convert user to array
     * 
     * @return array User data as array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'password_hash' => $this->passwordHash,
            'role' => $this->role
        ];
    }
}
