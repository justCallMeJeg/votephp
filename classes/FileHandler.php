<?php
/**
 * Class to handle file operations
 * Demonstrates OOP principles: Abstraction
 */
class FileHandler {
    private $filePath;
    
    /**
     * Constructor
     * 
     * @param string $filePath Path to the data file
     */
    public function __construct($filePath) {
        $this->filePath = $filePath;
        
        // Create directory if it doesn't exist
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create file if it doesn't exist
        if (!file_exists($filePath)) {
            file_put_contents($filePath, json_encode([]));
        }
    }
    
    /**
     * Read data from the file
     * 
     * @return array Data from the file
     */
    public function readData() {
        $content = file_get_contents($this->filePath);
        return json_decode($content, true) ?: [];
    }
    
    /**
     * Write data to the file
     * 
     * @param array $data Data to write
     * @return bool True if successful, false otherwise
     */
    public function writeData($data) {
        $content = json_encode($data, JSON_PRETTY_PRINT);
        return file_put_contents($this->filePath, $content) !== false;
    }
}
