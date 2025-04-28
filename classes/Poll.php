<?php
/**
 * Class to represent a poll
 * Demonstrates OOP principles: Encapsulation
 */
class Poll {
    private $id;
    private $title;
    private $description;
    private $options;
    private $allowMultipleVotes;
    private $showResultsMode; // 'after_vote', 'after_close', 'always'
    private $isRestricted;
    private $allowedUsers;
    private $endDate;
    private $votedUsers;
    private $maxSelectableOptions; // New property for multiple selections
    private $requiresVote; // Whether voting is required or optional
    
    /**
     * Constructor
     * 
     * @param string $id Unique identifier
     * @param string $title Poll title
     * @param string $description Poll description
     * @param array $options Array of Option objects
     * @param bool $allowMultipleVotes Whether users can vote multiple times
     * @param string $showResultsMode When to show results ('after_vote', 'after_close', 'always')
     * @param bool $isRestricted Whether the poll is restricted to specific users
     * @param array $allowedUsers Array of user IDs allowed to vote
     * @param string $endDate End date of the poll (Y-m-d H:i:s format)
     * @param array $votedUsers Array of user IDs who have voted
     * @param int $maxSelectableOptions Maximum number of options a user can select (1 for single choice)
     */
    public function __construct(
        $id, 
        $title, 
        $description, 
        $options, 
        $allowMultipleVotes = true, 
        $showResultsMode = 'always', 
        $isRestricted = false, 
        $allowedUsers = [], 
        $endDate = null,
        $votedUsers = [],
        $maxSelectableOptions = 1,
        $requiresVote = true
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->options = $options;
        $this->allowMultipleVotes = $allowMultipleVotes;
        $this->showResultsMode = $showResultsMode;
        $this->isRestricted = $isRestricted;
        $this->allowedUsers = $allowedUsers;
        $this->endDate = $endDate;
        $this->votedUsers = $votedUsers;
        $this->maxSelectableOptions = max(1, intval($maxSelectableOptions)); // Ensure at least 1
        $this->requiresVote = $requiresVote;
    }
    
    /**
     * Get poll ID
     * 
     * @return string Poll ID
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Get poll title
     * 
     * @return string Poll title
     */
    public function getTitle() {
        return $this->title;
    }
    
    /**
     * Get poll description
     * 
     * @return string Poll description
     */
    public function getDescription() {
        return $this->description;
    }
    
    /**
     * Get poll options
     * 
     * @return array Array of Option objects
     */
    public function getOptions() {
        return $this->options;
    }
    
    /**
     * Get total votes for this poll
     * 
     * @return int Total votes
     */
    public function getTotalVotes() {
        $total = 0;
        foreach ($this->options as $option) {
            $total += $option->getVotes();
        }
        return $total;
    }
    
    /**
     * Check if multiple votes are allowed
     * 
     * @return bool True if multiple votes are allowed
     */
    public function allowsMultipleVotes() {
        return $this->allowMultipleVotes;
    }
    
    /**
     * Get when to show results
     * 
     * @return string When to show results
     */
    public function getShowResultsMode() {
        return $this->showResultsMode;
    }
    
    /**
     * Check if poll is restricted to specific users
     * 
     * @return bool True if poll is restricted
     */
    public function isRestricted() {
        return $this->isRestricted;
    }
    
    /**
     * Get allowed users
     * 
     * @return array Array of user IDs
     */
    public function getAllowedUsers() {
        return $this->allowedUsers;
    }
    
    /**
     * Get end date
     * 
     * @return string End date
     */
    public function getEndDate() {
        return $this->endDate;
    }
    
    /**
     * Check if poll is closed
     * 
     * @return bool True if poll is closed
     */
    public function isClosed() {
        if ($this->endDate === null) {
            return false;
        }
        
        return strtotime($this->endDate) < time();
    }
    
    /**
     * Check if user is allowed to vote
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to vote
     */
    public function isUserAllowed($userId) {
        if (!$this->isRestricted) {
            return true;
        }
        
        return in_array($userId, $this->allowedUsers);
    }
    
    /**
     * Check if user has already voted
     * 
     * @param string $userId User ID
     * @return bool True if user has already voted
     */
    public function hasUserVoted($userId) {
        return in_array($userId, $this->votedUsers);
    }
    
    /**
     * Add user to voted users
     * 
     * @param string $userId User ID
     */
    public function addVotedUser($userId) {
        if (!in_array($userId, $this->votedUsers)) {
            $this->votedUsers[] = $userId;
        }
    }
    
    /**
     * Check if results should be shown to user
     * 
     * @param string $userId User ID
     * @return bool True if results should be shown
     */
    public function shouldShowResultsToUser($userId) {
        switch ($this->showResultsMode) {
            case 'after_vote':
                return $this->hasUserVoted($userId);
            case 'after_close':
                return $this->isClosed();
            case 'always':
            default:
                return true;
        }
    }
    
    /**
     * Get voted users
     * 
     * @return array Array of user IDs
     */
    public function getVotedUsers() {
        return $this->votedUsers;
    }
    
    /**
     * Get maximum number of options a user can select
     * 
     * @return int Maximum number of selectable options
     */
    public function getMaxSelectableOptions() {
        return $this->maxSelectableOptions;
    }
    
    /**
     * Check if poll allows multiple selections
     * 
     * @return bool True if multiple selections are allowed
     */
    public function allowsMultipleSelections() {
        return $this->maxSelectableOptions > 1;
    }
    
    /**
     * Get option by ID
     * 
     * @param string $optionId Option ID
     * @return Option|null Option object if found, null otherwise
     */
    public function getOptionById($optionId) {
        foreach ($this->options as $option) {
            if ($option->getId() === $optionId) {
                return $option;
            }
        }
        return null;
    }
    
    /**
     * Check if poll requires voting
     * 
     * @return bool True if voting is required
     */
    public function requiresVote() {
        return $this->requiresVote;
    }
}
