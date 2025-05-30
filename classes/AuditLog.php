<?php
/**
 * Class to handle audit logging
 * Tracks all user and admin actions for security and monitoring
 */
class AuditLog {
    private $action;
    private $userId;
    private $username;
    private $details;
    private $timestamp;
    private $ipAddress;
    
    /**
     * Constructor
     * 
     * @param string $action Action performed
     * @param string $userId User ID who performed the action
     * @param string $username Username who performed the action
     * @param string $details Additional details about the action
     * @param string $ipAddress IP address of the user
     */
    public function __construct($action, $userId, $username, $details = '', $ipAddress = '') {
        $this->action = $action;
        $this->userId = $userId;
        $this->username = $username;
        $this->details = $details;
        $this->timestamp = date('Y-m-d H:i:s');
        $this->ipAddress = $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Get action
     * 
     * @return string Action
     */
    public function getAction() {
        return $this->action;
    }
    
    /**
     * Get user ID
     * 
     * @return string User ID
     */
    public function getUserId() {
        return $this->userId;
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
     * Get details
     * 
     * @return string Details
     */
    public function getDetails() {
        return $this->details;
    }
    
    /**
     * Get timestamp
     * 
     * @return string Timestamp
     */
    public function getTimestamp() {
        return $this->timestamp;
    }
    
    /**
     * Get IP address
     * 
     * @return string IP address
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }
    
    /**
     * Convert to array
     * 
     * @return array Audit log data as array
     */
    public function toArray() {
        return [
            'action' => $this->action,
            'user_id' => $this->userId,
            'username' => $this->username,
            'details' => $this->details,
            'timestamp' => $this->timestamp,
            'ip_address' => $this->ipAddress
        ];
    }
}
