<?php
/**
 * Abstract base class for polls
 * Demonstrates OOP principles: Inheritance, Abstraction
 */
abstract class Poll {
    protected $id;
    protected $title;
    protected $description;
    protected $options;
    protected $allowMultipleVotes;
    protected $showResultsMode;
    protected $isRestricted;
    protected $allowedUsers;
    protected $endDate;
    protected $votedUsers;
    protected $requiresVote;
    protected $pollType;
    
    /**
     * Constructor
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
        $requiresVote = true,
        $pollType = 'basic'
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
        $this->requiresVote = $requiresVote;
        $this->pollType = $pollType;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getOptions() { return $this->options; }
    public function allowsMultipleVotes() { return $this->allowMultipleVotes; }
    public function getShowResultsMode() { return $this->showResultsMode; }
    public function isRestricted() { return $this->isRestricted; }
    public function getAllowedUsers() { return $this->allowedUsers; }
    public function getEndDate() { return $this->endDate; }
    public function getVotedUsers() { return $this->votedUsers; }
    public function requiresVote() { return $this->requiresVote; }
    public function getPollType() { return $this->pollType; }
    
    /**
     * Get total votes for this poll
     */
    public function getTotalVotes() {
        $total = 0;
        foreach ($this->options as $option) {
            $total += $option->getVotes();
        }
        return $total;
    }
    
    /**
     * Check if poll is closed
     */
    public function isClosed() {
        if ($this->endDate === null) {
            return false;
        }
        return strtotime($this->endDate) < time();
    }
    
    /**
     * Check if user is allowed to vote
     */
    public function isUserAllowed($userId) {
        if (!$this->isRestricted) {
            return true;
        }
        return in_array($userId, $this->allowedUsers);
    }
    
    /**
     * Check if user has already voted
     */
    public function hasUserVoted($userId) {
        return in_array($userId, $this->votedUsers);
    }
    
    /**
     * Add user to voted users
     */
    public function addVotedUser($userId) {
        if (!in_array($userId, $this->votedUsers)) {
            $this->votedUsers[] = $userId;
        }
    }
    
    /**
     * Check if results should be shown to user
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
     * Get option by ID
     */
    public function getOptionById($optionId) {
        foreach ($this->options as $option) {
            if ($option->getId() === $optionId) {
                return $option;
            }
        }
        return null;
    }
    
    // Abstract methods that must be implemented by subclasses
    abstract public function getMaxSelectableOptions();
    abstract public function allowsMultipleSelections();
    abstract public function validateVote($optionIds);
    abstract public function getDisplayName();
}

/**
 * Single choice poll - users can select only one option
 */
class SingleChoicePoll extends Poll {
    public function __construct(
        $id, $title, $description, $options, $allowMultipleVotes = true, 
        $showResultsMode = 'always', $isRestricted = false, $allowedUsers = [], 
        $endDate = null, $votedUsers = [], $requiresVote = true
    ) {
        parent::__construct(
            $id, $title, $description, $options, $allowMultipleVotes, 
            $showResultsMode, $isRestricted, $allowedUsers, $endDate, 
            $votedUsers, $requiresVote, 'single_choice'
        );
    }
    
    public function getMaxSelectableOptions() {
        return 1;
    }
    
    public function allowsMultipleSelections() {
        return false;
    }
    
    public function validateVote($optionIds) {
        if (!is_array($optionIds)) {
            $optionIds = [$optionIds];
        }
        return count($optionIds) === 1;
    }
    
    public function getDisplayName() {
        return 'Single Choice Poll';
    }
}

/**
 * Multiple choice poll - users can select multiple options
 */
class MultipleChoicePoll extends Poll {
    private $maxSelectableOptions;
    
    public function __construct(
        $id, $title, $description, $options, $maxSelectableOptions = 2,
        $allowMultipleVotes = true, $showResultsMode = 'always', 
        $isRestricted = false, $allowedUsers = [], $endDate = null, 
        $votedUsers = [], $requiresVote = true
    ) {
        parent::__construct(
            $id, $title, $description, $options, $allowMultipleVotes, 
            $showResultsMode, $isRestricted, $allowedUsers, $endDate, 
            $votedUsers, $requiresVote, 'multiple_choice'
        );
        $this->maxSelectableOptions = max(2, min(count($options), intval($maxSelectableOptions)));
    }
    
    public function getMaxSelectableOptions() {
        return $this->maxSelectableOptions;
    }
    
    public function allowsMultipleSelections() {
        return true;
    }
    
    public function validateVote($optionIds) {
        if (!is_array($optionIds)) {
            $optionIds = [$optionIds];
        }
        return count($optionIds) >= 1 && count($optionIds) <= $this->maxSelectableOptions;
    }
    
    public function getDisplayName() {
        return 'Multiple Choice Poll (up to ' . $this->maxSelectableOptions . ' selections)';
    }
}

/**
 * Yes/No poll - simple binary choice
 */
class YesNoPoll extends Poll {
    public function __construct(
        $id, $title, $description, $allowMultipleVotes = true, 
        $showResultsMode = 'always', $isRestricted = false, $allowedUsers = [], 
        $endDate = null, $votedUsers = [], $requiresVote = true
    ) {
        $options = [
            new Option('yes_' . $id, 'Yes', 0),
            new Option('no_' . $id, 'No', 0)
        ];
        
        parent::__construct(
            $id, $title, $description, $options, $allowMultipleVotes, 
            $showResultsMode, $isRestricted, $allowedUsers, $endDate, 
            $votedUsers, $requiresVote, 'yes_no'
        );
    }
    
    public function getMaxSelectableOptions() {
        return 1;
    }
    
    public function allowsMultipleSelections() {
        return false;
    }
    
    public function validateVote($optionIds) {
        if (!is_array($optionIds)) {
            $optionIds = [$optionIds];
        }
        return count($optionIds) === 1;
    }
    
    public function getDisplayName() {
        return 'Yes/No Poll';
    }
}
