<?php
/**
 * Class to represent a voting option
 * Demonstrates OOP principles: Encapsulation
 */
class Option {
    private $id;
    private $text;
    private $votes;
    
    /**
     * Constructor
     * 
     * @param string $id Unique identifier
     * @param string $text Option text
     * @param int $votes Number of votes
     */
    public function __construct($id, $text, $votes = 0) {
        $this->id = $id;
        $this->text = $text;
        $this->votes = $votes;
    }
    
    /**
     * Get option ID
     * 
     * @return string Option ID
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Get option text
     * 
     * @return string Option text
     */
    public function getText() {
        return $this->text;
    }
    
    /**
     * Get number of votes
     * 
     * @return int Number of votes
     */
    public function getVotes() {
        return $this->votes;
    }
    
    /**
     * Increment votes by one
     * 
     * @return int New vote count
     */
    public function incrementVotes() {
        return ++$this->votes;
    }
}
