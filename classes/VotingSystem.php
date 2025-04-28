<?php
require_once 'Poll.php';
require_once 'Option.php';
require_once 'FileHandler.php';
require_once 'User.php';

/**
 * Main class for the voting system
 * Demonstrates OOP principles: Encapsulation, Abstraction
 */
class VotingSystem {
    private $fileHandler;
    private $userFileHandler;
    private $polls = [];
    private $users = [];
    
    /**
     * Constructor initializes the file handlers and loads data
     */
    public function __construct() {
        $this->fileHandler = new FileHandler('data/polls.json');
        $this->userFileHandler = new FileHandler('data/users.json');
        $this->loadPolls();
        $this->loadUsers();
        
        // Create default users if none exist
        if (empty($this->users)) {
            $this->createDefaultUsers();
        }
    }
    
    /**
     * Create default admin and voter users
     */
    private function createDefaultUsers() {
        $this->createUser('admin', 'admin123', 'admin');
        $this->createUser('voter', 'voter123', 'voter');
    }
    
    /**
     * Load polls from the JSON file
     */
    private function loadPolls() {
        $data = $this->fileHandler->readData();
        
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
                
                $this->polls[] = new Poll(
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
                    $pollData['max_selectable_options'] ?? 1,
                    $pollData['requires_vote'] ?? true
                );
            }
        }
    }
    
    /**
     * Load users from the JSON file
     */
    private function loadUsers() {
        $data = $this->userFileHandler->readData();
        
        if (!empty($data)) {
            $this->users = $data;
        }
    }
    
    /**
     * Save polls to the JSON file
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
            
            $data[] = [
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
                'max_selectable_options' => $poll->getMaxSelectableOptions(),
                'requires_vote' => $poll->requiresVote()
            ];
        }
        
        $this->fileHandler->writeData($data);
    }
    
    /**
     * Save users to the JSON file
     */
    private function saveUsers() {
        $this->userFileHandler->writeData($this->users);
    }
    
    /**
     * Get all polls
     * 
     * @return array Array of Poll objects
     */
    public function getPolls() {
        return $this->polls;
    }
    
    /**
     * Get polls accessible to a specific user
     * 
     * @param string $userId User ID
     * @return array Array of Poll objects
     */
    public function getPollsForUser($userId) {
        $accessiblePolls = [];
        
        foreach ($this->polls as $poll) {
            if (!$poll->isRestricted() || $poll->isUserAllowed($userId)) {
                $accessiblePolls[] = $poll;
            }
        }
        
        return $accessiblePolls;
    }
    
    /**
     * Get all users
     * 
     * @return array Array of user data
     */
    public function getUsers() {
        return $this->users;
    }
    
    /**
     * Create a new poll
     * 
     * @param string $title Poll title
     * @param string $description Poll description
     * @param array $optionsText Array of option texts
     * @param bool $allowMultipleVotes Whether users can vote multiple times
     * @param string $showResultsMode When to show results
     * @param bool $isRestricted Whether the poll is restricted to specific users
     * @param array $allowedUsers Array of user IDs allowed to vote
     * @param string $endDate End date of the poll
     * @param int $maxSelectableOptions Maximum number of options a user can select
     * @return Poll The newly created poll
     */
    public function createPoll(
        $title, 
        $description, 
        $optionsText, 
        $allowMultipleVotes = true, 
        $showResultsMode = 'always', 
        $isRestricted = false, 
        $allowedUsers = [], 
        $endDate = null,
        $maxSelectableOptions = 1,
        $requiresVote = true
    ) {
        // Generate a unique ID for the poll
        $pollId = uniqid('poll_');
        
        // Create options
        $options = [];
        foreach ($optionsText as $index => $text) {
            $optionId = 'option_' . $pollId . '_' . $index;
            $options[] = new Option($optionId, $text, 0);
        }
        
        // Ensure maxSelectableOptions is valid
        $maxSelectableOptions = max(1, min(count($options), intval($maxSelectableOptions)));
        
        // Create the poll
        $poll = new Poll(
            $pollId, 
            $title, 
            $description, 
            $options, 
            $allowMultipleVotes, 
            $showResultsMode, 
            $isRestricted, 
            $allowedUsers, 
            $endDate,
            [],
            $maxSelectableOptions,
            $requiresVote
        );
        
        // Add to polls array
        $this->polls[] = $poll;
        
        // Save to file
        $this->savePolls();
        
        return $poll;
    }
    
    /**
     * Update an existing poll
     * 
     * @param string $pollId ID of the poll to update
     * @param string $title New poll title
     * @param string $description New poll description
     * @param bool $allowMultipleVotes Whether users can vote multiple times
     * @param string $showResultsMode When to show results
     * @param bool $isRestricted Whether the poll is restricted to specific users
     * @param array $allowedUsers Array of user IDs allowed to vote
     * @param string $endDate End date of the poll
     * @param int $maxSelectableOptions Maximum number of options a user can select
     * @return bool True if successful, false otherwise
     */
    public function updatePoll(
        $pollId,
        $title, 
        $description, 
        $allowMultipleVotes = true, 
        $showResultsMode = 'always', 
        $isRestricted = false, 
        $allowedUsers = [], 
        $endDate = null,
        $maxSelectableOptions = 1,
        $requiresVote = true
    ) {
        foreach ($this->polls as $key => $poll) {
            if ($poll->getId() === $pollId) {
                $options = $poll->getOptions();
                
                // Ensure maxSelectableOptions is valid
                $maxSelectableOptions = max(1, min(count($options), intval($maxSelectableOptions)));
                
                // Create a new poll object with updated values but keep the original options and votes
                $updatedPoll = new Poll(
                    $pollId,
                    $title,
                    $description,
                    $options,
                    $allowMultipleVotes,
                    $showResultsMode,
                    $isRestricted,
                    $allowedUsers,
                    $endDate,
                    $poll->getVotedUsers(),
                    $maxSelectableOptions,
                    $requiresVote
                );
                
                // Replace the old poll with the updated one
                $this->polls[$key] = $updatedPoll;
                
                // Save to file
                $this->savePolls();
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Delete a poll
     * 
     * @param string $pollId ID of the poll to delete
     * @return bool True if successful, false otherwise
     */
    public function deletePoll($pollId) {
        foreach ($this->polls as $key => $poll) {
            if ($poll->getId() === $pollId) {
                array_splice($this->polls, $key, 1);
                $this->savePolls();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vote for option(s) in a poll
     * 
     * @param string $pollId ID of the poll
     * @param string|array $optionIds ID(s) of the option(s)
     * @param string $userId ID of the user voting
     * @return bool True if vote was successful, false otherwise
     */
    public function vote($pollId, $optionIds, $userId) {
        foreach ($this->polls as $poll) {
            if ($poll->getId() === $pollId) {
                // Check if poll is closed
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
                
                // Convert single option ID to array for consistent handling
                if (!is_array($optionIds)) {
                    $optionIds = [$optionIds];
                }
                
                // Check if number of selected options is valid
                if (count($optionIds) > $poll->getMaxSelectableOptions()) {
                    return false;
                }
                
                // Increment votes for each selected option
                foreach ($optionIds as $optionId) {
                    $option = $poll->getOptionById($optionId);
                    if ($option) {
                        $option->incrementVotes();
                    }
                }
                
                // Mark user as voted
                $poll->addVotedUser($userId);
                
                // Save to file
                $this->savePolls();
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create a new user
     * 
     * @param string $username Username
     * @param string $password Plain text password
     * @param string $role User role (admin or voter)
     * @return bool True if successful, false if username already exists
     */
    public function createUser($username, $password, $role) {
        // Check if username already exists
        foreach ($this->users as $user) {
            if ($user['username'] === $username) {
                return false;
            }
        }
        
        // Generate a unique ID for the user
        $userId = uniqid('user_');
        
        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Create user data
        $userData = [
            'id' => $userId,
            'username' => $username,
            'password_hash' => $passwordHash,
            'role' => $role
        ];
        
        // Add to users array
        $this->users[] = $userData;
        
        // Save to file
        $this->saveUsers();
        
        return true;
    }
    
    /**
     * Update an existing user
     * 
     * @param string $userId ID of the user to update
     * @param string $username New username
     * @param string $password New password (if empty, keep the old password)
     * @param string $role New user role
     * @return bool True if successful, false if username already exists or user not found
     */
    public function updateUser($userId, $username, $password, $role) {
        // Find the user to update
        $userIndex = -1;
        foreach ($this->users as $index => $user) {
            if ($user['id'] === $userId) {
                $userIndex = $index;
                break;
            }
        }
        
        // If user not found, return false
        if ($userIndex === -1) {
            return false;
        }
        
        // Check if username already exists (except for the current user)
        foreach ($this->users as $user) {
            if ($user['username'] === $username && $user['id'] !== $userId) {
                return false;
            }
        }
        
        // Update user data
        $this->users[$userIndex]['username'] = $username;
        $this->users[$userIndex]['role'] = $role;
        
        // Update password if provided
        if (!empty($password)) {
            $this->users[$userIndex]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        // Save to file
        $this->saveUsers();
        
        return true;
    }
    
    /**
     * Delete a user
     * 
     * @param string $userId ID of the user to delete
     * @return bool True if successful, false otherwise
     */
    public function deleteUser($userId) {
        // Find the user to delete
        $userIndex = -1;
        foreach ($this->users as $index => $user) {
            if ($user['id'] === $userId) {
                $userIndex = $index;
                break;
            }
        }
        
        // If user not found, return false
        if ($userIndex === -1) {
            return false;
        }
        
        // Remove user from users array
        array_splice($this->users, $userIndex, 1);
        
        // Save to file
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
        
        return true;
    }
    
    /**
     * Authenticate a user
     * 
     * @param string $username Username
     * @param string $password Plain text password
     * @return array|false User data if authenticated, false otherwise
     */
    public function authenticateUser($username, $password) {
        foreach ($this->users as $user) {
            if ($user['username'] === $username) {
                if (password_verify($password, $user['password_hash'])) {
                    return $user;
                }
                break;
            }
        }
        
        return false;
    }
    
    /**
     * Get a poll by ID
     * 
     * @param string $pollId Poll ID
     * @return Poll|null Poll object if found, null otherwise
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
     * 
     * @param string $userId User ID
     * @return array|null User data if found, null otherwise
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
