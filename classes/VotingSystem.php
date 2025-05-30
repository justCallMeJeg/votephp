<?php
require_once 'Poll.php';
require_once 'Option.php';
require_once 'User.php';
require_once 'AuditLog.php';

/**
 * Main class for the voting system with integrated file handling
 * Demonstrates OOP principles: Encapsulation, Abstraction
 */
class VotingSystem {
    private $polls = [];
    private $users = [];
    private $auditLogs = [];
    private $dataDir = 'data/';
    
    /**
     * Constructor initializes the system and loads data
     */
    public function __construct() {
        $this->ensureDataDirectory();
        $this->loadPolls();
        $this->loadUsers();
        $this->loadAuditLogs();
        
        // Create default users if none exist
        if (empty($this->users)) {
            $this->createDefaultUsers();
        }
    }
    
    /**
     * Ensure data directory exists
     */
    private function ensureDataDirectory() {
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }
    
    /**
     * Read data from JSON file
     */
    private function readJsonFile($filename) {
        $filepath = $this->dataDir . $filename;
        if (!file_exists($filepath)) {
            file_put_contents($filepath, json_encode([]));
            return [];
        }
        
        $content = file_get_contents($filepath);
        return json_decode($content, true) ?: [];
    }
    
    /**
     * Write data to JSON file
     */
    private function writeJsonFile($filename, $data) {
        $filepath = $this->dataDir . $filename;
        $content = json_encode($data, JSON_PRETTY_PRINT);
        return file_put_contents($filepath, $content) !== false;
    }
    
    /**
     * Create default admin and voter users
     */
    private function createDefaultUsers() {
        $this->createUser('admin', 'admin123', 'admin');
        $this->createUser('voter', 'voter123', 'voter');
    }
    
    /**
     * Load polls from JSON file
     */
    private function loadPolls() {
        $data = $this->readJsonFile('polls.json');
        
        if (!empty($data)) {
            foreach ($data as $pollData) {
                $options = [];
                foreach ($pollData['options'] as $optionData) {
                    $options[] = new Option(
                        $optionData['id'],
                        $optionData['text'],
                        $optionData['votes']
                    );
                }
                
                // Create appropriate poll type based on stored type
                $pollType = $pollData['poll_type'] ?? 'single_choice';
                
                switch ($pollType) {
                    case 'multiple_choice':
                        $poll = new MultipleChoicePoll(
                            $pollData['id'],
                            $pollData['title'],
                            $pollData['description'],
                            $options,
                            $pollData['max_selectable_options'] ?? 2,
                            $pollData['allow_multiple_votes'] ?? true,
                            $pollData['show_results_mode'] ?? 'always',
                            $pollData['is_restricted'] ?? false,
                            $pollData['allowed_users'] ?? [],
                            $pollData['end_date'] ?? null,
                            $pollData['voted_users'] ?? [],
                            $pollData['requires_vote'] ?? true,
                            $pollData['status'] ?? 'draft',
                            $pollData['start_date'] ?? null,
                            $pollData['actual_end_date'] ?? null,
                            $pollData['hide_after_end'] ?? false
                        );
                        break;
                    case 'yes_no':
                        $poll = new YesNoPoll(
                            $pollData['id'],
                            $pollData['title'],
                            $pollData['description'],
                            $pollData['allow_multiple_votes'] ?? true,
                            $pollData['show_results_mode'] ?? 'always',
                            $pollData['is_restricted'] ?? false,
                            $pollData['allowed_users'] ?? [],
                            $pollData['end_date'] ?? null,
                            $pollData['voted_users'] ?? [],
                            $pollData['requires_vote'] ?? true,
                            $pollData['status'] ?? 'draft',
                            $pollData['start_date'] ?? null,
                            $pollData['actual_end_date'] ?? null,
                            $pollData['hide_after_end'] ?? false
                        );
                        break;
                    default: // single_choice
                        $poll = new SingleChoicePoll(
                            $pollData['id'],
                            $pollData['title'],
                            $pollData['description'],
                            $options,
                            $pollData['allow_multiple_votes'] ?? true,
                            $pollData['show_results_mode'] ?? 'always',
                            $pollData['is_restricted'] ?? false,
                            $pollData['allowed_users'] ?? [],
                            $pollData['end_date'] ?? null,
                            $pollData['voted_users'] ?? [],
                            $pollData['requires_vote'] ?? true,
                            $pollData['status'] ?? 'draft',
                            $pollData['start_date'] ?? null,
                            $pollData['actual_end_date'] ?? null,
                            $pollData['hide_after_end'] ?? false
                        );
                        break;
                }
                
                $this->polls[] = $poll;
            }
        }
    }
    
    /**
     * Load users from JSON file
     */
    private function loadUsers() {
        $this->users = $this->readJsonFile('users.json');
    }
    
    /**
     * Load audit logs from JSON file
     */
    private function loadAuditLogs() {
        $data = $this->readJsonFile('audit.json');
        
        foreach ($data as $logData) {
            $this->auditLogs[] = new AuditLog(
                $logData['action'],
                $logData['user_id'],
                $logData['username'],
                $logData['details'],
                $logData['ip_address'] ?? ''
            );
        }
    }
    
    /**
     * Save polls to JSON file
     */
    private function savePolls() {
        $data = [];
        
        foreach ($this->polls as $poll) {
            $optionsData = [];
            foreach ($poll->getOptions() as $option) {
                $optionsData[] = [
                    'id' => $option->getId(),
                    'text' => $option->getText(),
                    'votes' => $option->getVotes()
                ];
            }
            
            $pollData = [
                'id' => $poll->getId(),
                'title' => $poll->getTitle(),
                'description' => $poll->getDescription(),
                'options' => $optionsData,
                'allow_multiple_votes' => $poll->allowsMultipleVotes(),
                'show_results_mode' => $poll->getShowResultsMode(),
                'is_restricted' => $poll->isRestricted(),
                'allowed_users' => $poll->getAllowedUsers(),
                'end_date' => $poll->getEndDate(),
                'voted_users' => $poll->getVotedUsers(),
                'requires_vote' => $poll->requiresVote(),
                'poll_type' => $poll->getPollType(),
                'status' => $poll->getStatus(),
                'start_date' => $poll->getStartDate(),
                'actual_end_date' => $poll->getActualEndDate(),
                'hide_after_end' => $poll->getHideAfterEnd()
            ];
            
            // Add max_selectable_options for multiple choice polls
            if ($poll instanceof MultipleChoicePoll) {
                $pollData['max_selectable_options'] = $poll->getMaxSelectableOptions();
            }
            
            $data[] = $pollData;
        }
        
        $this->writeJsonFile('polls.json', $data);
    }
    
    /**
     * Save users to JSON file
     */
    private function saveUsers() {
        $this->writeJsonFile('users.json', $this->users);
    }
    
    /**
     * Save audit logs to JSON file
     */
    private function saveAuditLogs() {
        $data = [];
        foreach ($this->auditLogs as $log) {
            $data[] = $log->toArray();
        }
        $this->writeJsonFile('audit.json', $data);
    }
    
    /**
     * Add audit log entry
     */
    public function addAuditLog($action, $userId, $username, $details = '') {
        $log = new AuditLog($action, $userId, $username, $details);
        $this->auditLogs[] = $log;
        $this->saveAuditLogs();
    }
    
    /**
     * Get audit logs with pagination
     */
    public function getAuditLogs($page = 1, $perPage = 20) {
        $totalLogs = count($this->auditLogs);
        $totalPages = ceil($totalLogs / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $reversedLogs = array_reverse($this->auditLogs);
        $paginatedLogs = array_slice($reversedLogs, $offset, $perPage);
        
        return [
            'logs' => $paginatedLogs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs,
            'perPage' => $perPage
        ];
    }
    
    /**
     * Start a poll
     */
    public function startPoll($pollId, $userId = null, $username = null) {
        foreach ($this->polls as $poll) {
            if ($poll->getId() === $pollId && $poll->isDraft()) {
                $poll->setStatus('active');
                $poll->setStartDate(date('Y-m-d H:i:s'));
                $this->savePolls();
                
                // Add audit log
                if ($userId && $username) {
                    $this->addAuditLog(
                        'poll_started', 
                        $userId, 
                        $username, 
                        "Started poll: {$poll->getTitle()}"
                    );
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * End a poll
     */
    public function endPoll($pollId, $hideAfterEnd = false, $userId = null, $username = null) {
        foreach ($this->polls as $poll) {
            if ($poll->getId() === $pollId && $poll->isActive()) {
                $poll->setStatus('ended');
                $poll->setActualEndDate(date('Y-m-d H:i:s'));
                
                // Update poll with new hide_after_end setting
                $this->updatePollHideAfterEnd($pollId, $hideAfterEnd);
                
                $this->savePolls();
                
                // Add audit log
                if ($userId && $username) {
                    $hideText = $hideAfterEnd ? ' (hidden from voters)' : ' (visible to voters)';
                    $this->addAuditLog(
                        'poll_ended', 
                        $userId, 
                        $username, 
                        "Ended poll: {$poll->getTitle()}{$hideText}"
                    );
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Update poll hide after end setting
     */
    private function updatePollHideAfterEnd($pollId, $hideAfterEnd) {
        foreach ($this->polls as $key => $poll) {
            if ($poll->getId() === $pollId) {
                // Create a new poll object with updated hide_after_end setting
                $pollType = $poll->getPollType();
                $options = $poll->getOptions();
                
                switch ($pollType) {
                    case 'multiple_choice':
                        $updatedPoll = new MultipleChoicePoll(
                            $poll->getId(), $poll->getTitle(), $poll->getDescription(), 
                            $options, $poll->getMaxSelectableOptions(),
                            $poll->allowsMultipleVotes(), $poll->getShowResultsMode(),
                            $poll->isRestricted(), $poll->getAllowedUsers(),
                            $poll->getEndDate(), $poll->getVotedUsers(),
                            $poll->requiresVote(), $poll->getStatus(),
                            $poll->getStartDate(), $poll->getActualEndDate(), $hideAfterEnd
                        );
                        break;
                    case 'yes_no':
                        $updatedPoll = new YesNoPoll(
                            $poll->getId(), $poll->getTitle(), $poll->getDescription(),
                            $poll->allowsMultipleVotes(), $poll->getShowResultsMode(),
                            $poll->isRestricted(), $poll->getAllowedUsers(),
                            $poll->getEndDate(), $poll->getVotedUsers(),
                            $poll->requiresVote(), $poll->getStatus(),
                            $poll->getStartDate(), $poll->getActualEndDate(), $hideAfterEnd
                        );
                        break;
                    default: // single_choice
                        $updatedPoll = new SingleChoicePoll(
                            $poll->getId(), $poll->getTitle(), $poll->getDescription(),
                            $options, $poll->allowsMultipleVotes(),
                            $poll->getShowResultsMode(), $poll->isRestricted(),
                            $poll->getAllowedUsers(), $poll->getEndDate(),
                            $poll->getVotedUsers(), $poll->requiresVote(),
                            $poll->getStatus(), $poll->getStartDate(),
                            $poll->getActualEndDate(), $hideAfterEnd
                        );
                        break;
                }
                
                $this->polls[$key] = $updatedPoll;
                break;
            }
        }
    }
    
    /**
     * Get all polls
     */
    public function getPolls() {
        return $this->polls;
    }
    
    /**
     * Get polls accessible to a specific user (only visible polls)
     */
    public function getPollsForUser($userId) {
        $accessiblePolls = [];
        
        foreach ($this->polls as $poll) {
            if ($poll->isVisibleToVoters() && (!$poll->isRestricted() || $poll->isUserAllowed($userId))) {
                $accessiblePolls[] = $poll;
            }
        }
        
        return $accessiblePolls;
    }
    
    /**
     * Get all users
     */
    public function getUsers() {
        return $this->users;
    }
    
    /**
     * Create a new poll
     */
    public function createPoll(
        $title, $description, $pollType, $optionsText = [], 
        $maxSelectableOptions = 1, $allowMultipleVotes = true, 
        $showResultsMode = 'always', $isRestricted = false, 
        $allowedUsers = [], $endDate = null, $requiresVote = true,
        $userId = null, $username = null
    ) {
        $pollId = uniqid('poll_');
        
        switch ($pollType) {
            case 'multiple_choice':
                $options = [];
                foreach ($optionsText as $index => $text) {
                    $optionId = 'option_' . $pollId . '_' . $index;
                    $options[] = new Option($optionId, $text, 0);
                }
                $poll = new MultipleChoicePoll(
                    $pollId, $title, $description, $options, $maxSelectableOptions,
                    $allowMultipleVotes, $showResultsMode, $isRestricted, 
                    $allowedUsers, $endDate, [], $requiresVote, 'draft'
                );
                break;
                
            case 'yes_no':
                $poll = new YesNoPoll(
                    $pollId, $title, $description, $allowMultipleVotes, 
                    $showResultsMode, $isRestricted, $allowedUsers, 
                    $endDate, [], $requiresVote, 'draft'
                );
                break;
                
            default: // single_choice
                $options = [];
                foreach ($optionsText as $index => $text) {
                    $optionId = 'option_' . $pollId . '_' . $index;
                    $options[] = new Option($optionId, $text, 0);
                }
                $poll = new SingleChoicePoll(
                    $pollId, $title, $description, $options, $allowMultipleVotes, 
                    $showResultsMode, $isRestricted, $allowedUsers, 
                    $endDate, [], $requiresVote, 'draft'
                );
                break;
        }
        
        $this->polls[] = $poll;
        $this->savePolls();
        
        // Add audit log
        if ($userId && $username) {
            $this->addAuditLog(
                'poll_created', 
                $userId, 
                $username, 
                "Created poll: {$title} (Type: {$poll->getDisplayName()})"
            );
        }
        
        return $poll;
    }
    
    /**
     * Update an existing poll (only if in draft status)
     */
    public function updatePoll(
        $pollId, $title, $description, $allowMultipleVotes = true, 
        $showResultsMode = 'always', $isRestricted = false, 
        $allowedUsers = [], $endDate = null, $maxSelectableOptions = 1,
        $requiresVote = true, $userId = null, $username = null
    ) {
        foreach ($this->polls as $key => $poll) {
            if ($poll->getId() === $pollId && $poll->canBeEdited()) {
                $options = $poll->getOptions();
                $pollType = $poll->getPollType();
                
                // Create updated poll of the same type
                switch ($pollType) {
                    case 'multiple_choice':
                        $maxSelectableOptions = max(2, min(count($options), intval($maxSelectableOptions)));
                        $updatedPoll = new MultipleChoicePoll(
                            $pollId, $title, $description, $options, $maxSelectableOptions,
                            $allowMultipleVotes, $showResultsMode, $isRestricted,
                            $allowedUsers, $endDate, $poll->getVotedUsers(), $requiresVote,
                            $poll->getStatus(), $poll->getStartDate(), $poll->getActualEndDate(),
                            $poll->getHideAfterEnd()
                        );
                        break;
                    case 'yes_no':
                        $updatedPoll = new YesNoPoll(
                            $pollId, $title, $description, $allowMultipleVotes,
                            $showResultsMode, $isRestricted, $allowedUsers,
                            $endDate, $poll->getVotedUsers(), $requiresVote,
                            $poll->getStatus(), $poll->getStartDate(), $poll->getActualEndDate(),
                            $poll->getHideAfterEnd()
                        );
                        break;
                    default: // single_choice
                        $updatedPoll = new SingleChoicePoll(
                            $pollId, $title, $description, $options, $allowMultipleVotes,
                            $showResultsMode, $isRestricted, $allowedUsers,
                            $endDate, $poll->getVotedUsers(), $requiresVote,
                            $poll->getStatus(), $poll->getStartDate(), $poll->getActualEndDate(),
                            $poll->getHideAfterEnd()
                        );
                        break;
                }
                
                $this->polls[$key] = $updatedPoll;
                $this->savePolls();
                
                // Add audit log
                if ($userId && $username) {
                    $this->addAuditLog(
                        'poll_updated', 
                        $userId, 
                        $username, 
                        "Updated poll: {$title}"
                    );
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Delete a poll
     */
    public function deletePoll($pollId, $userId = null, $username = null) {
        foreach ($this->polls as $key => $poll) {
            if ($poll->getId() === $pollId) {
                $pollTitle = $poll->getTitle();
                array_splice($this->polls, $key, 1);
                $this->savePolls();
                
                // Add audit log
                if ($userId && $username) {
                    $this->addAuditLog(
                        'poll_deleted', 
                        $userId, 
                        $username, 
                        "Deleted poll: {$pollTitle}"
                    );
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vote for option(s) in a poll
     */
    public function vote($pollId, $optionIds, $userId) {
        foreach ($this->polls as $poll) {
            if ($poll->getId() === $pollId) {
                // Check if poll can be voted on
                if (!$poll->canBeVotedOn()) {
                    return false;
                }
                
                // Check if poll is closed by scheduled end date
                if ($poll->isClosed()) {
                    return false;
                }
                
                // Check if user is allowed to vote on this poll
                if (!$poll->isUserAllowed($userId)) {
                    return false;
                }
                
                // Check if user has already voted and multiple votes are not allowed
                if (!$poll->allowsMultipleVotes() && $poll->hasUserVoted($userId)) {
                    return false;
                }
                
                // Validate vote using poll-specific validation
                if (!$poll->validateVote($optionIds)) {
                    return false;
                }
                
                // Convert single option ID to array for consistent handling
                if (!is_array($optionIds)) {
                    $optionIds = [$optionIds];
                }
                
                // Increment votes for each selected option
                $selectedOptions = [];
                foreach ($optionIds as $optionId) {
                    $option = $poll->getOptionById($optionId);
                    if ($option) {
                        $option->incrementVotes();
                        $selectedOptions[] = $option->getText();
                    }
                }
                
                // Mark user as voted
                $poll->addVotedUser($userId);
                
                // Save to file
                $this->savePolls();
                
                // Add audit log
                $user = $this->getUserById($userId);
                if ($user) {
                    $this->addAuditLog(
                        'vote_cast', 
                        $userId, 
                        $user['username'], 
                        "Voted on poll: {$poll->getTitle()} - Selected: " . implode(', ', $selectedOptions)
                    );
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create a new user
     */
    public function createUser($username, $password, $role, $createdByUserId = null, $createdByUsername = null) {
        // Check if username already exists
        foreach ($this->users as $user) {
            if ($user['username'] === $username) {
                return false;
            }
        }
        
        $userId = uniqid('user_');
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $userData = [
            'id' => $userId,
            'username' => $username,
            'password_hash' => $passwordHash,
            'role' => $role
        ];
        
        $this->users[] = $userData;
        $this->saveUsers();
        
        // Add audit log
        if ($createdByUserId && $createdByUsername) {
            $this->addAuditLog(
                'user_created', 
                $createdByUserId, 
                $createdByUsername, 
                "Created user: {$username} (Role: {$role})"
            );
        }
        
        return true;
    }
    
    /**
     * Update an existing user
     */
    public function updateUser($userId, $username, $password, $role, $updatedByUserId = null, $updatedByUsername = null) {
        $userIndex = -1;
        foreach ($this->users as $index => $user) {
            if ($user['id'] === $userId) {
                $userIndex = $index;
                break;
            }
        }
        
        if ($userIndex === -1) {
            return false;
        }
        
        // Check if username already exists (except for the current user)
        foreach ($this->users as $user) {
            if ($user['username'] === $username && $user['id'] !== $userId) {
                return false;
            }
        }
        
        $oldUsername = $this->users[$userIndex]['username'];
        
        // Update user data
        $this->users[$userIndex]['username'] = $username;
        $this->users[$userIndex]['role'] = $role;
        
        // Update password if provided
        if (!empty($password)) {
            $this->users[$userIndex]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $this->saveUsers();
        
        // Add audit log
        if ($updatedByUserId && $updatedByUsername) {
            $changes = [];
            if ($oldUsername !== $username) $changes[] = "username: {$oldUsername} → {$username}";
            if (!empty($password)) $changes[] = "password changed";
            $changes[] = "role: {$role}";
            
            $this->addAuditLog(
                'user_updated', 
                $updatedByUserId, 
                $updatedByUsername, 
                "Updated user: " . implode(', ', $changes)
            );
        }
        
        return true;
    }
    
    /**
     * Delete a user
     */
    public function deleteUser($userId, $deletedByUserId = null, $deletedByUsername = null) {
        $userIndex = -1;
        $deletedUsername = '';
        
        foreach ($this->users as $index => $user) {
            if ($user['id'] === $userId) {
                $userIndex = $index;
                $deletedUsername = $user['username'];
                break;
            }
        }
        
        if ($userIndex === -1) {
            return false;
        }
        
        // Remove user from users array
        array_splice($this->users, $userIndex, 1);
        $this->saveUsers();
        
        // Remove user from allowed users in restricted polls
        foreach ($this->polls as $poll) {
            if ($poll->isRestricted()) {
                $allowedUsers = $poll->getAllowedUsers();
                if (in_array($userId, $allowedUsers)) {
                    $key = array_search($userId, $allowedUsers);
                    if ($key !== false) {
                        unset($allowedUsers[$key]);
                        $this->updatePoll(
                            $poll->getId(),
                            $poll->getTitle(),
                            $poll->getDescription(),
                            $poll->allowsMultipleVotes(),
                            $poll->getShowResultsMode(),
                            $poll->isRestricted(),
                            array_values($allowedUsers),
                            $poll->getEndDate(),
                            $poll->getMaxSelectableOptions(),
                            $poll->requiresVote()
                        );
                    }
                }
            }
        }
        
        // Add audit log
        if ($deletedByUserId && $deletedByUsername) {
            $this->addAuditLog(
                'user_deleted', 
                $deletedByUserId, 
                $deletedByUsername, 
                "Deleted user: {$deletedUsername}"
            );
        }
        
        return true;
    }
    
    /**
     * Authenticate a user
     */
    public function authenticateUser($username, $password) {
        foreach ($this->users as $user) {
            if ($user['username'] === $username) {
                if (password_verify($password, $user['password_hash'])) {
                    // Add audit log for successful login
                    $this->addAuditLog(
                        'user_login', 
                        $user['id'], 
                        $username, 
                        "Successful login"
                    );
                    return $user;
                }
                break;
            }
        }
        
        // Add audit log for failed login
        $this->addAuditLog(
            'login_failed', 
            'unknown', 
            $username, 
            "Failed login attempt"
        );
        
        return false;
    }
    
    /**
     * Get a poll by ID
     */
    public function getPollById($pollId) {
        foreach ($this->polls as $poll) {
            if ($poll->getId() === $pollId) {
                return $poll;
            }
        }
        return null;
    }
    
    /**
     * Get a user by ID
     */
    public function getUserById($userId) {
        foreach ($this->users as $user) {
            if ($user['id'] === $userId) {
                return $user;
            }
        }
        return null;
    }
}
