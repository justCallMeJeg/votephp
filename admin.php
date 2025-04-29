<?php
session_start();
require_once 'classes/VotingSystem.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect non-admin users to voter page
if ($_SESSION['role'] !== 'admin') {
    header('Location: voter.php');
    exit;
}

// Initialize the voting system
$votingSystem = new VotingSystem();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle new poll creation
    if (isset($_POST['create_poll'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $options = explode("\n", $_POST['options']);
        $options = array_map('trim', $options);
        $options = array_filter($options);
        
        // Get advanced poll settings
        $allowMultipleVotes = isset($_POST['allow_multiple_votes']);
        $showResultsMode = $_POST['show_results_mode'];
        $isRestricted = isset($_POST['is_restricted']);
        $allowedUsers = $isRestricted && isset($_POST['allowed_users']) ? $_POST['allowed_users'] : [];
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] . ' ' . $_POST['end_time'] : null;
        $maxSelectableOptions = isset($_POST['max_selectable_options']) ? intval($_POST['max_selectable_options']) : 1;
        $requiresVote = isset($_POST['requires_vote']);
        
        $votingSystem->createPoll(
            $title, 
            $description, 
            $options, 
            $allowMultipleVotes, 
            $showResultsMode, 
            $isRestricted, 
            $allowedUsers, 
            $endDate,
            $maxSelectableOptions,
            $requiresVote
        );
    }
    
    // Handle poll update
    if (isset($_POST['update_poll'])) {
        $pollId = $_POST['poll_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        
        // Get advanced poll settings
        $allowMultipleVotes = isset($_POST['allow_multiple_votes']);
        $showResultsMode = $_POST['show_results_mode'];
        $isRestricted = isset($_POST['is_restricted']);
        $allowedUsers = $isRestricted && isset($_POST['allowed_users']) ? $_POST['allowed_users'] : [];
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] . ' ' . $_POST['end_time'] : null;
        $maxSelectableOptions = isset($_POST['max_selectable_options']) ? intval($_POST['max_selectable_options']) : 1;
        $requiresVote = isset($_POST['requires_vote']);
        
        $votingSystem->updatePoll(
            $pollId,
            $title, 
            $description, 
            $allowMultipleVotes, 
            $showResultsMode, 
            $isRestricted, 
            $allowedUsers, 
            $endDate,
            $maxSelectableOptions,
            $requiresVote
        );
    }
    
    // Handle user creation
    if (isset($_POST['create_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        $result = $votingSystem->createUser($username, $password, $role);
        if (!$result) {
            $error = "Username already exists";
        }
    }
    
    // Handle user update
    if (isset($_POST['update_user'])) {
        $userId = $_POST['user_id'];
        $username = $_POST['username'];
        $password = $_POST['password']; // May be empty if not changing password
        $role = $_POST['role'];
        
        $result = $votingSystem->updateUser($userId, $username, $password, $role);
        if (!$result) {
            $error = "Username already exists or user not found";
        }
    }
    
    // Handle user deletion
    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        
        // Don't allow deleting the current user
        if ($userId === $_SESSION['user_id']) {
            $error = "You cannot delete your own account";
        } else {
            $votingSystem->deleteUser($userId);
        }
    }
    
    // Handle poll deletion
    if (isset($_POST['delete_poll'])) {
        $pollId = $_POST['poll_id'];
        $votingSystem->deletePoll($pollId);
    }
}

// Get all polls
$polls = $votingSystem->getPolls();

// Get all users
$users = $votingSystem->getUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PHP Voting App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container-fluid py-4">
        <header class="pb-3 mb-4 border-bottom d-flex justify-content-between align-items-center">
            <h1 class="display-5 fw-bold">Admin Dashboard</h1>
            <div>
                <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createPollModal">
                    <i class="bi bi-plus-circle"></i> Create Poll
                </button>
                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus"></i> Create User
                </button>
                <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="p-4 mb-4 bg-light rounded-3">
                    <h2>Manage Polls</h2>
                    
                    <?php if (empty($polls)): ?>
                        <div class="alert alert-info">No polls available. Create one!</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Options</th>
                                        <th>Total Votes</th>
                                        <th>Settings</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($polls as $poll): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($poll->getTitle()) ?></td>
                                            <td><?= htmlspecialchars(substr($poll->getDescription(), 0, 30)) ?>...</td>
                                            <td><?= count($poll->getOptions()) ?></td>
                                            <td><?= $poll->getTotalVotes() ?></td>
                                            <td>
                                                <small>
                                                    <?php if ($poll->allowsMultipleVotes()): ?>
                                                        <span class="badge bg-info">Multiple Votes</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Single Vote</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($poll->getMaxSelectableOptions() > 1): ?>
                                                        <span class="badge bg-primary">Multiple Choice (<?= $poll->getMaxSelectableOptions() ?>)</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Single Choice</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($poll->requiresVote()): ?>
                                                        <span class="badge bg-danger">Voting Required</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Voting Optional</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($poll->getShowResultsMode() === 'always'): ?>
                                                        <span class="badge bg-success">Results: Always</span>
                                                    <?php elseif ($poll->getShowResultsMode() === 'after_vote'): ?>
                                                        <span class="badge bg-primary">Results: After Vote</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Results: After Close</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($poll->isRestricted()): ?>
                                                        <span class="badge bg-danger">Restricted</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Open</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($poll->isClosed()): ?>
                                                        <span class="badge bg-danger">Closed</span>
                                                    <?php elseif ($poll->getEndDate()): ?>
                                                        <span class="badge bg-warning">Ends: <?= date('m/d/Y', strtotime($poll->getEndDate())) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">No End Date</span>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary me-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editPollModal" 
                                                        data-poll-id="<?= $poll->getId() ?>"
                                                        data-poll-title="<?= htmlspecialchars($poll->getTitle()) ?>"
                                                        data-poll-description="<?= htmlspecialchars($poll->getDescription()) ?>"
                                                        data-poll-multiple-votes="<?= $poll->allowsMultipleVotes() ? '1' : '0' ?>"
                                                        data-poll-results-mode="<?= $poll->getShowResultsMode() ?>"
                                                        data-poll-restricted="<?= $poll->isRestricted() ? '1' : '0' ?>"
                                                        data-poll-end-date="<?= $poll->getEndDate() ? date('Y-m-d', strtotime($poll->getEndDate())) : '' ?>"
                                                        data-poll-end-time="<?= $poll->getEndDate() ? date('H:i', strtotime($poll->getEndDate())) : '' ?>"
                                                        data-poll-allowed-users="<?= htmlspecialchars(json_encode($poll->getAllowedUsers())) ?>"
                                                        data-poll-max-selectable-options="<?= $poll->getMaxSelectableOptions() ?>"
                                                        data-poll-requires-vote="<?= $poll->requiresVote() ? '1' : '0' ?>">
                                                    Edit
                                                </button>
                                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this poll?');">
                                                    <input type="hidden" name="poll_id" value="<?= $poll->getId() ?>">
                                                    <button type="submit" name="delete_poll" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="p-4 mb-4 bg-light rounded-3">
                    <h2>Manage Users</h2>
                    
                    <?php if (empty($users)): ?>
                        <div class="alert alert-info">No users available.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>User ID</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>"><?= ucfirst($user['role']) ?></span></td>
                                            <td><small class="text-muted"><?= $user['id'] ?></small></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary me-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editUserModal" 
                                                        data-user-id="<?= $user['id'] ?>"
                                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                                        data-role="<?= $user['role'] ?>">
                                                    Edit
                                                </button>
                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-danger" disabled title="You cannot delete your own account">Delete</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Poll Modal -->
    <div class="modal fade" id="createPollModal" tabindex="-1" aria-labelledby="createPollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPollModalLabel">Create New Poll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="createPollForm">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="options" class="form-label">Options (one per line)</label>
                            <textarea class="form-control" id="options" name="options" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="max_selectable_options" class="form-label">Maximum selectable options</label>
                            <input type="number" class="form-control" id="max_selectable_options" name="max_selectable_options" min="1" value="1">
                            <div class="form-text">Set to 1 for single-choice polls, or higher for multiple-choice polls</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="requires_vote" name="requires_vote" checked>
                            <label class="form-check-label" for="requires_vote">Require voting (uncheck to make voting optional)</label>
                        </div>
                        
                        <div class="accordion" id="advancedSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingAdvancedSettings">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdvancedSettings" aria-expanded="false" aria-controls="collapseAdvancedSettings">
                                        Advanced Settings
                                    </button>
                                </h2>
                                <div id="collapseAdvancedSettings" class="accordion-collapse collapse" aria-labelledby="headingAdvancedSettings" data-bs-parent="#advancedSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="allow_multiple_votes" name="allow_multiple_votes">
                                            <label class="form-check-label" for="allow_multiple_votes">Allow users to vote multiple times</label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="show_results_mode" class="form-label">Show Results</label>
                                            <select class="form-select" id="show_results_mode" name="show_results_mode">
                                                <option value="always">Always show results</option>
                                                <option value="after_vote">Show results after voting</option>
                                                <option value="after_close">Show results after poll closes</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date (optional)</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="end_time" class="form-label">End Time (optional)</label>
                                            <input type="time" class="form-control" id="end_time" name="end_time" value="23:59">
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="is_restricted" name="is_restricted">
                                            <label class="form-check-label" for="is_restricted">Restrict to specific users</label>
                                        </div>
                                        
                                        <div class="mb-3" id="allowed_users_container" style="display: none;">
                                            <label for="allowed_users" class="form-label">Allowed Users</label>
                                            <select class="form-select" id="allowed_users" name="allowed_users[]" multiple size="5">
                                                <?php foreach ($users as $user): ?>
                                                    <?php if ($user['role'] === 'voter'): ?>
                                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Hold Ctrl (or Cmd) to select multiple users</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="createPollForm" name="create_poll" class="btn btn-success">Create Poll</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Poll Modal -->
    <div class="modal fade" id="editPollModal" tabindex="-1" aria-labelledby="editPollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPollModalLabel">Edit Poll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="editPollForm">
                        <input type="hidden" id="edit_poll_id" name="poll_id">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_max_selectable_options" class="form-label">Maximum selectable options</label>
                            <input type="number" class="form-control" id="edit_max_selectable_options" name="max_selectable_options" min="1" value="1">
                            <div class="form-text">Set to 1 for single-choice polls, or higher for multiple-choice polls</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_requires_vote" name="requires_vote" checked>
                            <label class="form-check-label" for="edit_requires_vote">Require voting (uncheck to make voting optional)</label>
                        </div>
                        
                        <div class="accordion" id="editAdvancedSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingEditAdvancedSettings">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditAdvancedSettings" aria-expanded="false" aria-controls="collapseEditAdvancedSettings">
                                        Advanced Settings
                                    </button>
                                </h2>
                                <div id="collapseEditAdvancedSettings" class="accordion-collapse collapse" aria-labelledby="headingEditAdvancedSettings" data-bs-parent="#editAdvancedSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="edit_allow_multiple_votes" name="allow_multiple_votes">
                                            <label class="form-check-label" for="edit_allow_multiple_votes">Allow users to vote multiple times</label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="edit_show_results_mode" class="form-label">Show Results</label>
                                            <select class="form-select" id="edit_show_results_mode" name="show_results_mode">
                                                <option value="always">Always show results</option>
                                                <option value="after_vote">Show results after voting</option>
                                                <option value="after_close">Show results after poll closes</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="edit_end_date" class="form-label">End Date (optional)</label>
                                            <input type="date" class="form-control" id="edit_end_date" name="end_date">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="edit_end_time" class="form-label">End Time (optional)</label>
                                            <input type="time" class="form-control" id="edit_end_time" name="end_time" value="23:59">
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="edit_is_restricted" name="is_restricted">
                                            <label class="form-check-label" for="edit_is_restricted">Restrict to specific users</label>
                                        </div>
                                        
                                        <div class="mb-3" id="edit_allowed_users_container" style="display: none;">
                                            <label for="edit_allowed_users" class="form-label">Allowed Users</label>
                                            <select class="form-select" id="edit_allowed_users" name="allowed_users[]" multiple size="5">
                                                <?php foreach ($users as $user): ?>
                                                    <?php if ($user['role'] === 'voter'): ?>
                                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Hold Ctrl (or Cmd) to select multiple users</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editPollForm" name="update_poll" class="btn btn-primary">Update Poll</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="createUserForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="voter">Voter</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="createUserForm" name="create_user" class="btn btn-success">Create User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="editUserForm">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                            <div class="form-text">Leave blank to keep current password</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="voter">Voter</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editUserForm" name="update_user" class="btn btn-primary">Update User</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Show/hide allowed users container based on is_restricted checkbox
        document.getElementById('is_restricted').addEventListener('change', function() {
            document.getElementById('allowed_users_container').style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('edit_is_restricted').addEventListener('change', function() {
            document.getElementById('edit_allowed_users_container').style.display = this.checked ? 'block' : 'none';
        });
        
        // Handle edit poll modal
        const editPollModal = document.getElementById('editPollModal');
        if (editPollModal) {
            editPollModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const pollId = button.getAttribute('data-poll-id');
                const pollTitle = button.getAttribute('data-poll-title');
                const pollDescription = button.getAttribute('data-poll-description');
                const pollMultipleVotes = button.getAttribute('data-poll-multiple-votes') === '1';
                const pollResultsMode = button.getAttribute('data-poll-results-mode');
                const pollRestricted = button.getAttribute('data-poll-restricted') === '1';
                const pollEndDate = button.getAttribute('data-poll-end-date');
                const pollEndTime = button.getAttribute('data-poll-end-time');
                const pollAllowedUsers = JSON.parse(button.getAttribute('data-poll-allowed-users'));
                const pollMaxSelectableOptions = button.getAttribute('data-poll-max-selectable-options');
                const pollRequiresVote = button.getAttribute('data-poll-requires-vote') === '1';
                
                document.getElementById('edit_poll_id').value = pollId;
                document.getElementById('edit_title').value = pollTitle;
                document.getElementById('edit_description').value = pollDescription;
                document.getElementById('edit_allow_multiple_votes').checked = pollMultipleVotes;
                document.getElementById('edit_show_results_mode').value = pollResultsMode;
                document.getElementById('edit_is_restricted').checked = pollRestricted;
                document.getElementById('edit_end_date').value = pollEndDate;
                document.getElementById('edit_end_time').value = pollEndTime || '23:59';
                document.getElementById('edit_max_selectable_options').value = pollMaxSelectableOptions;
                document.getElementById('edit_requires_vote').checked = pollRequiresVote;
                
                // Show/hide allowed users container
                document.getElementById('edit_allowed_users_container').style.display = pollRestricted ? 'block' : 'none';
                
                // Set selected users
                const allowedUsersSelect = document.getElementById('edit_allowed_users');
                for (let i = 0; i < allowedUsersSelect.options.length; i++) {
                    allowedUsersSelect.options[i].selected = pollAllowedUsers.includes(allowedUsersSelect.options[i].value);
                }
            });
        }
        
        // Handle edit user modal
        const editUserModal = document.getElementById('editUserModal');
        if (editUserModal) {
            editUserModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const username = button.getAttribute('data-username');
                const role = button.getAttribute('data-role');
                
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_username').value = username;
                document.getElementById('edit_password').value = ''; // Clear password field
                document.getElementById('edit_role').value = role;
            });
        }
    </script>
</body>
</html>
