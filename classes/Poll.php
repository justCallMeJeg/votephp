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
    protected $status; // 'draft', 'active', 'ended'
    protected $startDate;
    protected $actualEndDate;
    protected $hideAfterEnd; // Whether to hide poll from voters after it ends
    
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
        $pollType = 'basic',
        $status = 'draft',
        $startDate = null,
        $actualEndDate = null,
        $hideAfterEnd = false
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
        $this->status = $status;
        $this->startDate = $startDate;
        $this->actualEndDate = $actualEndDate;
        $this->hideAfterEnd = $hideAfterEnd;
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
    public function getStatus() { return $this->status; }
    public function getStartDate() { return $this->startDate; }
    public function getActualEndDate() { return $this->actualEndDate; }
    public function getHideAfterEnd() { return $this->hideAfterEnd; }
    
    /**
     * Set poll status
     */
    public function setStatus($status) {
        $this->status = $status;
    }
    
    /**
     * Set start date
     */
    public function setStartDate($startDate) {
        $this->startDate = $startDate;
    }
    
    /**
     * Set actual end date
     */
    public function setActualEndDate($actualEndDate) {
        $this->actualEndDate = $actualEndDate;
    }
    
    /**
     * Check if poll is in draft status
     */
    public function isDraft() {
        return $this->status === 'draft';
    }
    
    /**
     * Check if poll is active
     */
    public function isActive() {
        return $this->status === 'active';
    }
    
    /**
     * Check if poll is ended
     */
    public function isEnded() {
        return $this->status === 'ended';
    }
    
    /**
     * Check if poll can be edited
     */
    public function canBeEdited() {
        return $this->status === 'draft';
    }
    
    /**
     * Check if poll can be voted on
     */
    public function canBeVotedOn() {
        return $this->status === 'active';
    }
    
    /**
     * Check if poll should be visible to voters
     */
    public function isVisibleToVoters() {
        if ($this->status === 'draft') {
            return false;
        }
        
        if ($this->status === 'ended' && $this->hideAfterEnd) {
            return false;
        }
        
        return true;
    }
    
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
     * Check if poll is closed (by scheduled end date)
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
                return $this->isClosed() || $this->isEnded();
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
    
    /**
     * Get status badge class
     */
    public function getStatusBadgeClass() {
        switch ($this->status) {
            case 'draft':
                return 'bg-secondary';
            case 'active':
                return 'bg-success';
            case 'ended':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }
    
    /**
     * Get status display name
     */
    public function getStatusDisplayName() {
        switch ($this->status) {
            case 'draft':
                return 'Draft';
            case 'active':
                return 'Active';
            case 'ended':
                return 'Ended';
            default:
                return 'Unknown';
        }
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
        $endDate = null, $votedUsers = [], $requiresVote = true, $status = 'draft',
        $startDate = null, $actualEndDate = null, $hideAfterEnd = false
    ) {
        parent::__construct(
            $id, $title, $description, $options, $allowMultipleVotes, 
            $showResultsMode, $isRestricted, $allowedUsers, $endDate, 
            $votedUsers, $requiresVote, 'single_choice', $status,
            $startDate, $actualEndDate, $hideAfterEnd
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
        $votedUsers = [], $requiresVote = true, $status = 'draft',
        $startDate = null, $actualEndDate = null, $hideAfterEnd = false
    ) {
        parent::__construct(
            $id, $title, $description, $options, $allowMultipleVotes, 
            $showResultsMode, $isRestricted, $allowedUsers, $endDate, 
            $votedUsers, $requiresVote, 'multiple_choice', $status,
            $startDate, $actualEndDate, $hideAfterEnd
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
        $endDate = null, $votedUsers = [], $requiresVote = true, $status = 'draft',
        $startDate = null, $actualEndDate = null, $hideAfterEnd = false
    ) {
        $options = [
            new Option('yes_' . $id, 'Yes', 0),
            new Option('no_' . $id, 'No', 0)
        ];
        
        parent::__construct(
            $id, $title, $description, $options, $allowMultipleVotes, 
            $showResultsMode, $isRestricted, $allowedUsers, $endDate, 
            $votedUsers, $requiresVote, 'yes_no', $status,
            $startDate, $actualEndDate, $hideAfterEnd
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
