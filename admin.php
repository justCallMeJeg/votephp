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
        $pollType = $_POST['poll_type'];
        $options = [];
        
        if ($pollType !== 'yes_no') {
            $options = explode("\n", $_POST['options']);
            $options = array_map('trim', $options);
            $options = array_filter($options);
        }
        
        // Get advanced poll settings
        $allowMultipleVotes = isset($_POST['allow_multiple_votes']);
        $showResultsMode = $_POST['show_results_mode'];
        $isRestricted = isset($_POST['is_restricted']);
        $allowedUsers = $isRestricted && isset($_POST['allowed_users']) ? $_POST['allowed_users'] : [];
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] . ' ' . $_POST['end_time'] : null;
        $maxSelectableOptions = isset($_POST['max_selectable_options']) ? intval($_POST['max_selectable_options']) : 2;
        $requiresVote = isset($_POST['requires_vote']);
        
        $votingSystem->createPoll(
            $title, $description, $pollType, $options, $maxSelectableOptions,
            $allowMultipleVotes, $showResultsMode, $isRestricted, 
            $allowedUsers, $endDate, $requiresVote,
            $_SESSION['user_id'], $_SESSION['username']
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
        $maxSelectableOptions = isset($_POST['max_selectable_options']) ? intval($_POST['max_selectable_options']) : 2;
        $requiresVote = isset($_POST['requires_vote']);
        
        $result = $votingSystem->updatePoll(
            $pollId, $title, $description, $allowMultipleVotes, 
            $showResultsMode, $isRestricted, $allowedUsers, 
            $endDate, $maxSelectableOptions, $requiresVote,
            $_SESSION['user_id'], $_SESSION['username']
        );
        
        if (!$result) {
            $error = "Poll cannot be edited once it has been started";
        }
    }
    
    // Handle poll start
    if (isset($_POST['start_poll'])) {
        $pollId = $_POST['poll_id'];
        $votingSystem->startPoll($pollId, $_SESSION['user_id'], $_SESSION['username']);
    }
    
    // Handle poll end
    if (isset($_POST['end_poll'])) {
        $pollId = $_POST['poll_id'];
        $hideAfterEnd = isset($_POST['hide_after_end']);
        $votingSystem->endPoll($pollId, $hideAfterEnd, $_SESSION['user_id'], $_SESSION['username']);
    }
    
    // Handle user creation
    if (isset($_POST['create_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        $result = $votingSystem->createUser(
            $username, $password, $role,
            $_SESSION['user_id'], $_SESSION['username']
        );
        if (!$result) {
            $error = "Username already exists";
        }
    }
    
    // Handle user update
    if (isset($_POST['update_user'])) {
        $userId = $_POST['user_id'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        $result = $votingSystem->updateUser(
            $userId, $username, $password, $role,
            $_SESSION['user_id'], $_SESSION['username']
        );
        if (!$result) {
            $error = "Username already exists or user not found";
        }
    }
    
    // Handle user deletion
    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        
        if ($userId === $_SESSION['user_id']) {
            $error = "You cannot delete your own account";
        } else {
            $votingSystem->deleteUser(
                $userId, $_SESSION['user_id'], $_SESSION['username']
            );
        }
    }
    
    // Handle poll deletion
    if (isset($_POST['delete_poll'])) {
        $pollId = $_POST['poll_id'];
        $votingSystem->deletePoll(
            $pollId, $_SESSION['user_id'], $_SESSION['username']
        );
    }
}

// Get all polls
$polls = $votingSystem->getPolls();

// Get all users
$users = $votingSystem->getUsers();

// Get audit logs with pagination and filters
$currentPage = isset($_GET['audit_page']) ? max(1, intval($_GET['audit_page'])) : 1;
$actionFilter = isset($_GET['action_filter']) ? $_GET['action_filter'] : '';
$userFilter = isset($_GET['user_filter']) ? $_GET['user_filter'] : '';
$auditData = $votingSystem->getAuditLogs($currentPage, 15, $actionFilter, $userFilter);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PHP Voting App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container-fluid py-4">
        <header class="pb-3 mb-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h1 class="display-5 fw-bold mb-2">
                    <i class="bi bi-speedometer2 text-primary"></i>
                    Admin Dashboard
                </h1>
                <p class="text-muted mb-0">Manage polls, users, and monitor system activity</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPollModal">
                    <i class="bi bi-plus-circle"></i> Create Poll
                </button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus"></i> Create User
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= count($polls) ?></h3>
                            <p class="mb-0">Total Polls</p>
                        </div>
                        <i class="bi bi-bar-chart-fill fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= count($users) ?></h3>
                            <p class="mb-0">Total Users</p>
                        </div>
                        <i class="bi bi-people-fill fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= array_sum(array_map(function($poll) { return $poll->getTotalVotes(); }, $polls)) ?></h3>
                            <p class="mb-0">Total Votes</p>
                        </div>
                        <i class="bi bi-check2-square fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= count(array_filter($polls, function($poll) { return $poll->isActive(); })) ?></h3>
                            <p class="mb-0">Active Polls</p>
                        </div>
                        <i class="bi bi-play-circle-fill fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="bi bi-bar-chart"></i> Manage Polls</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($polls)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                No polls available. Create your first poll!
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="bi bi-card-text"></i> Title</th>
                                            <th><i class="bi bi-type"></i> Type</th>
                                            <th><i class="bi bi-activity"></i> Status</th>
                                            <th><i class="bi bi-check-square"></i> Votes</th>
                                            <th><i class="bi bi-bar-chart"></i> Results</th>
                                            <th><i class="bi bi-gear"></i> Settings</th>
                                            <th><i class="bi bi-tools"></i> Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($polls as $poll): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($poll->getTitle()) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars(substr($poll->getDescription(), 0, 50)) ?>...</small>
                                                    <?php if ($poll->getEndDate()): ?>
                                                        <br><small class="text-info">
                                                            <i class="bi bi-calendar"></i> 
                                                            Scheduled: <?= date('M j, Y g:i A', strtotime($poll->getEndDate())) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="poll-type-indicator">
                                                        <i class="bi bi-<?= $poll->getPollType() === 'yes_no' ? 'toggle-on' : ($poll->allowsMultipleSelections() ? 'check2-all' : 'check2') ?>"></i>
                                                        <?= $poll->getDisplayName() ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $poll->getStatusBadgeClass() ?>">
                                                        <i class="bi bi-<?= $poll->isDraft() ? 'file-earmark' : ($poll->isActive() ? 'play-circle' : 'stop-circle') ?>"></i>
                                                        <?= $poll->getStatusDisplayName() ?>
                                                    </span>
                                                    <?php if ($poll->getStartDate()): ?>
                                                        <br><small class="text-muted">Started: <?= date('M j, Y g:i A', strtotime($poll->getStartDate())) ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($poll->getActualEndDate()): ?>
                                                        <br><small class="text-muted">Ended: <?= date('M j, Y g:i A', strtotime($poll->getActualEndDate())) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= $poll->getTotalVotes() ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($poll->isActive() || $poll->isEnded()): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#pollResultsModal"
                                                                data-poll-id="<?= $poll->getId() ?>"
                                                                data-poll-title="<?= htmlspecialchars($poll->getTitle()) ?>"
                                                                data-poll-options="<?= htmlspecialchars(json_encode(array_map(function($option) {
                                                                    return ['text' => $option->getText(), 'votes' => $option->getVotes()];
                                                                }, $poll->getOptions()))) ?>"
                                                                data-poll-total-votes="<?= $poll->getTotalVotes() ?>">
                                                            <i class="bi bi-bar-chart"></i> View
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php if ($poll->allowsMultipleVotes()): ?>
                                                            <span class="badge bg-info">Multiple Votes</span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($poll->requiresVote()): ?>
                                                            <span class="badge bg-danger">Required</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Optional</span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($poll->isRestricted()): ?>
                                                            <span class="badge bg-warning">Restricted</span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($poll->isEnded() && $poll->getHideAfterEnd()): ?>
                                                            <span class="badge bg-secondary">Hidden</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($poll->isDraft()): ?>
                                                            <button type="button" class="btn btn-sm btn-success" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#startPollModal"
                                                                    data-poll-id="<?= $poll->getId() ?>"
                                                                    data-poll-title="<?= htmlspecialchars($poll->getTitle()) ?>"
                                                                    data-poll-scheduled="<?= $poll->getEndDate() ? '1' : '0' ?>">
                                                                <i class="bi bi-play-circle"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-primary" 
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
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                        <?php elseif ($poll->isActive()): ?>
                                                            <button type="button" class="btn btn-sm btn-danger" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#endPollModal"
                                                                    data-poll-id="<?= $poll->getId() ?>"
                                                                    data-poll-title="<?= htmlspecialchars($poll->getTitle()) ?>">
                                                                <i class="bi bi-stop-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this poll?');">
                                                            <input type="hidden" name="poll_id" value="<?= $poll->getId() ?>">
                                                            <button type="submit" name="delete_poll" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="bi bi-people"></i> Manage Users</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                No users available.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="bi bi-person"></i> Username</th>
                                            <th><i class="bi bi-shield"></i> Role</th>
                                            <th><i class="bi bi-key"></i> User ID</th>
                                            <th><i class="bi bi-tools"></i> Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person-circle fs-4 text-primary me-2"></i>
                                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                                        <i class="bi bi-<?= $user['role'] === 'admin' ? 'shield-fill' : 'person' ?>"></i>
                                                        <?= ucfirst($user['role']) ?>
                                                    </span>
                                                </td>
                                                <td><small class="text-muted font-monospace"><?= $user['id'] ?></small></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editUserModal" 
                                                                data-user-id="<?= $user['id'] ?>"
                                                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                                                data-role="<?= $user['role'] ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                            <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-danger" disabled title="You cannot delete your own account">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Audit Log Section -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="bi bi-clock-history"></i> Audit Log</h2>
                    </div>
                    <div class="card-body">
                        <!-- Audit Filters -->
                        <div class="audit-filters">
                            <form method="get" action="" class="row g-3">
                                <div class="col-md-4">
                                    <label for="action_filter" class="form-label">Filter by Action</label>
                                    <select class="form-select" id="action_filter" name="action_filter">
                                        <option value="">All Actions</option>
                                        <option value="user_login" <?= $actionFilter === 'user_login' ? 'selected' : '' ?>>User Login</option>
                                        <option value="poll_created" <?= $actionFilter === 'poll_created' ? 'selected' : '' ?>>Poll Created</option>
                                        <option value="poll_started" <?= $actionFilter === 'poll_started' ? 'selected' : '' ?>>Poll Started</option>
                                        <option value="poll_ended" <?= $actionFilter === 'poll_ended' ? 'selected' : '' ?>>Poll Ended</option>
                                        <option value="vote_cast" <?= $actionFilter === 'vote_cast' ? 'selected' : '' ?>>Vote Cast</option>
                                        <option value="user_created" <?= $actionFilter === 'user_created' ? 'selected' : '' ?>>User Created</option>
                                        <option value="user_updated" <?= $actionFilter === 'user_updated' ? 'selected' : '' ?>>User Updated</option>
                                        <option value="user_deleted" <?= $actionFilter === 'user_deleted' ? 'selected' : '' ?>>User Deleted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="user_filter" class="form-label">Filter by User</label>
                                    <select class="form-select" id="user_filter" name="user_filter">
                                        <option value="">All Users</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['username'] ?>" <?= $userFilter === $user['username'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['username']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                    <a href="?" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </a>
                                </div>
                            </form>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="text-muted">
                                    Showing <?= count($auditData['logs']) ?> of <?= $auditData['totalLogs'] ?> entries
                                </span>
                            </div>
                            <div>
                                <span class="text-muted">Page <?= $auditData['currentPage'] ?> of <?= $auditData['totalPages'] ?></span>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-clock"></i> Timestamp</th>
                                        <th><i class="bi bi-activity"></i> Action</th>
                                        <th><i class="bi bi-person"></i> User</th>
                                        <th><i class="bi bi-info-circle"></i> Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($auditData['logs'])): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No audit logs available</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($auditData['logs'] as $log): ?>
                                            <tr>
                                                <td>
                                                    <small class="audit-timestamp">
                                                        <?= date('M j, Y g:i A', strtotime($log->getTimestamp())) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        strpos($log->getAction(), 'login') !== false ? 'success' :
                                                        (strpos($log->getAction(), 'created') !== false ? 'primary' :
                                                        (strpos($log->getAction(), 'deleted') !== false ? 'danger' :
                                                        (strpos($log->getAction(), 'updated') !== false ? 'warning' : 
                                                        (strpos($log->getAction(), 'started') !== false ? 'success' :
                                                        (strpos($log->getAction(), 'ended') !== false ? 'danger' : 'info')))))
                                                    ?>">
                                                        <?= ucwords(str_replace('_', ' ', $log->getAction())) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($log->getUsername()) ?></td>
                                                <td><?= htmlspecialchars($log->getDetails()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($auditData['totalPages'] > 1): ?>
                            <nav aria-label="Audit log pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($auditData['currentPage'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?audit_page=<?= $auditData['currentPage'] - 1 ?>&action_filter=<?= urlencode($actionFilter) ?>&user_filter=<?= urlencode($userFilter) ?>">
                                                <i class="bi bi-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $auditData['currentPage'] - 2);
                                    $endPage = min($auditData['totalPages'], $auditData['currentPage'] + 2);
                                    ?>
                                    
                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?= $i === $auditData['currentPage'] ? 'active' : '' ?>">
                                            <a class="page-link" href="?audit_page=<?= $i ?>&action_filter=<?= urlencode($actionFilter) ?>&user_filter=<?= urlencode($userFilter) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($auditData['currentPage'] < $auditData['totalPages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?audit_page=<?= $auditData['currentPage'] + 1 ?>&action_filter=<?= urlencode($actionFilter) ?>&user_filter=<?= urlencode($userFilter) ?>">
                                                Next <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Poll Results Modal -->
    <div class="modal fade" id="pollResultsModal" tabindex="-1" aria-labelledby="pollResultsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pollResultsModalLabel">
                        <i class="bi bi-bar-chart"></i> Poll Results
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 id="pollResultsTitle"></h6>
                    <div id="pollResultsContent"></div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-people"></i>
                            Total votes: <span id="pollResultsTotalVotes"></span>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Poll Modal -->
    <div class="modal fade" id="startPollModal" tabindex="-1" aria-labelledby="startPollModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="startPollModalLabel">
                        <i class="bi bi-play-circle"></i> Start Poll
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> Once you start this poll, it cannot be edited anymore. Voters will be able to cast their votes.
                    </div>
                    <div id="scheduledPollInfo" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-calendar"></i>
                            <strong>Scheduled Poll:</strong> This poll has a scheduled end date. You can start it manually now or wait for the scheduled time.
                        </div>
                    </div>
                    <p>Are you sure you want to start the poll "<span id="startPollTitle"></span>"?</p>
                    <form method="post" action="" id="startPollForm">
                        <input type="hidden" id="start_poll_id" name="poll_id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" form="startPollForm" name="start_poll" class="btn btn-success">
                        <i class="bi bi-play-circle"></i> Start Poll Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- End Poll Modal -->
    <div class="modal fade" id="endPollModal" tabindex="-1" aria-labelledby="endPollModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="endPollModalLabel">
                        <i class="bi bi-stop-circle"></i> End Poll
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> Once you end this poll, voters will no longer be able to vote on it.
                    </div>
                    <p>Are you sure you want to end the poll "<span id="endPollTitle"></span>"?</p>
                    
                    <form method="post" action="" id="endPollForm">
                        <input type="hidden" id="end_poll_id" name="poll_id">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="hide_after_end" name="hide_after_end">
                            <label class="form-check-label" for="hide_after_end">
                                <i class="bi bi-eye-slash"></i> Hide poll from voters after ending
                            </label>
                            <div class="form-text">If checked, voters will no longer see this poll once it's ended</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" form="endPollForm" name="end_poll" class="btn btn-danger">
                        <i class="bi bi-stop-circle"></i> End Poll
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Poll Modal -->
    <div class="modal fade" id="createPollModal" tabindex="-1" aria-labelledby="createPollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPollModalLabel">
                        <i class="bi bi-plus-circle"></i> Create New Poll
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="createPollForm">
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                <i class="bi bi-card-text"></i> Title
                            </label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="bi bi-file-text"></i> Description
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="poll_type" class="form-label">
                                <i class="bi bi-diagram-3"></i> Poll Type
                            </label>
                            <select class="form-select" id="poll_type" name="poll_type" required>
                                <option value="single_choice">Single Choice Poll</option>
                                <option value="multiple_choice">Multiple Choice Poll</option>
                                <option value="yes_no">Yes/No Poll</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="options_container">
                            <label for="options" class="form-label">
                                <i class="bi bi-list-ul"></i> Options (one per line)
                            </label>
                            <textarea class="form-control" id="options" name="options" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3" id="max_options_container" style="display: none;">
                            <label for="max_selectable_options" class="form-label">
                                <i class="bi bi-check2-all"></i> Maximum selectable options
                            </label>
                            <input type="number" class="form-control" id="max_selectable_options" name="max_selectable_options" min="2" value="2">
                            <div class="form-text">Users can select up to this many options</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="requires_vote" name="requires_vote" checked>
                            <label class="form-check-label" for="requires_vote">
                                <i class="bi bi-exclamation-triangle"></i> Require voting (uncheck to make voting optional)
                            </label>
                        </div>
                        
                        <div class="accordion" id="advancedSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingAdvancedSettings">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdvancedSettings" aria-expanded="false" aria-controls="collapseAdvancedSettings">
                                        <i class="bi bi-gear"></i> Advanced Settings
                                    </button>
                                </h2>
                                <div id="collapseAdvancedSettings" class="accordion-collapse collapse" aria-labelledby="headingAdvancedSettings" data-bs-parent="#advancedSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="allow_multiple_votes" name="allow_multiple_votes">
                                            <label class="form-check-label" for="allow_multiple_votes">
                                                <i class="bi bi-arrow-repeat"></i> Allow users to vote multiple times
                                            </label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="show_results_mode" class="form-label">
                                                <i class="bi bi-eye"></i> Show Results
                                            </label>
                                            <select class="form-select" id="show_results_mode" name="show_results_mode">
                                                <option value="always">Always show results</option>
                                                <option value="after_vote">Show results after voting</option>
                                                <option value="after_close">Show results after poll closes</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">
                                                <i class="bi bi-calendar"></i> Scheduled End Date (optional)
                                            </label>
                                            <input type="date" class="form-control" id="end_date" name="end_date">
                                            <div class="form-text">If set, poll will automatically end at this date</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="end_time" class="form-label">
                                                <i class="bi bi-clock"></i> End Time (optional)
                                            </label>
                                            <input type="time" class="form-control" id="end_time" name="end_time" value="23:59">
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="is_restricted" name="is_restricted">
                                            <label class="form-check-label" for="is_restricted">
                                                <i class="bi bi-lock"></i> Restrict to specific users
                                            </label>
                                        </div>
                                        
                                        <div class="mb-3" id="allowed_users_container" style="display: none;">
                                            <label for="allowed_users" class="form-label">
                                                <i class="bi bi-people"></i> Allowed Users
                                            </label>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" form="createPollForm" name="create_poll" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Create Poll
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Poll Modal -->
    <div class="modal fade" id="editPollModal" tabindex="-1" aria-labelledby="editPollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPollModalLabel">
                        <i class="bi bi-pencil"></i> Edit Poll
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Note:</strong> Polls can only be edited while in draft status.
                    </div>
                    <form method="post" action="" id="editPollForm">
                        <input type="hidden" id="edit_poll_id" name="poll_id">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">
                                <i class="bi bi-card-text"></i> Title
                            </label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">
                                <i class="bi bi-file-text"></i> Description
                            </label>
                            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3" id="edit_max_options_container">
                            <label for="edit_max_selectable_options" class="form-label">
                                <i class="bi bi-check2-all"></i> Maximum selectable options
                            </label>
                            <input type="number" class="form-control" id="edit_max_selectable_options" name="max_selectable_options" min="1" value="1">
                            <div class="form-text">Set to 1 for single-choice polls, or higher for multiple-choice polls</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_requires_vote" name="requires_vote" checked>
                            <label class="form-check-label" for="edit_requires_vote">
                                <i class="bi bi-exclamation-triangle"></i> Require voting (uncheck to make voting optional)
                            </label>
                        </div>
                        
                        <div class="accordion" id="editAdvancedSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingEditAdvancedSettings">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditAdvancedSettings" aria-expanded="false" aria-controls="collapseEditAdvancedSettings">
                                        <i class="bi bi-gear"></i> Advanced Settings
                                    </button>
                                </h2>
                                <div id="collapseEditAdvancedSettings" class="accordion-collapse collapse" aria-labelledby="headingEditAdvancedSettings" data-bs-parent="#editAdvancedSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="edit_allow_multiple_votes" name="allow_multiple_votes">
                                            <label class="form-check-label" for="edit_allow_multiple_votes">
                                                <i class="bi bi-arrow-repeat"></i> Allow users to vote multiple times
                                            </label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="edit_show_results_mode" class="form-label">
                                                <i class="bi bi-eye"></i> Show Results
                                            </label>
                                            <select class="form-select" id="edit_show_results_mode" name="show_results_mode">
                                                <option value="always">Always show results</option>
                                                <option value="after_vote">Show results after voting</option>
                                                <option value="after_close">Show results after poll closes</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="edit_end_date" class="form-label">
                                                <i class="bi bi-calendar"></i> Scheduled End Date (optional)
                                            </label>
                                            <input type="date" class="form-control" id="edit_end_date" name="end_date">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="edit_end_time" class="form-label">
                                                <i class="bi bi-clock"></i> End Time (optional)
                                            </label>
                                            <input type="time" class="form-control" id="edit_end_time" name="end_time" value="23:59">
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="edit_is_restricted" name="is_restricted">
                                            <label class="form-check-label" for="edit_is_restricted">
                                                <i class="bi bi-lock"></i> Restrict to specific users
                                            </label>
                                        </div>
                                        
                                        <div class="mb-3" id="edit_allowed_users_container" style="display: none;">
                                            <label for="edit_allowed_users" class="form-label">
                                                <i class="bi bi-people"></i> Allowed Users
                                            </label>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" form="editPollForm" name="update_poll" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Poll
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">
                        <i class="bi bi-person-plus"></i> Create New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="createUserForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person"></i> Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-key"></i> Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">
                                <i class="bi bi-shield"></i> Role
                            </label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="voter">Voter</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" form="createUserForm" name="create_user" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Create User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="bi bi-pencil"></i> Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="editUserForm">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">
                                <i class="bi bi-person"></i> Username
                            </label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">
                                <i class="bi bi-key"></i> Password
                            </label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                            <div class="form-text">Leave blank to keep current password</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">
                                <i class="bi bi-shield"></i> Role
                            </label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="voter">Voter</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" form="editUserForm" name="update_user" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Poll type change handler
        document.getElementById('poll_type').addEventListener('change', function() {
            const pollType = this.value;
            const optionsContainer = document.getElementById('options_container');
            const maxOptionsContainer = document.getElementById('max_options_container');
            const optionsField = document.getElementById('options');
            
            if (pollType === 'yes_no') {
                optionsContainer.style.display = 'none';
                maxOptionsContainer.style.display = 'none';
                optionsField.required = false;
            } else {
                optionsContainer.style.display = 'block';
                optionsField.required = true;
                
                if (pollType === 'multiple_choice') {
                    maxOptionsContainer.style.display = 'block';
                } else {
                    maxOptionsContainer.style.display = 'none';
                }
            }
        });
        
        // Show/hide allowed users container based on is_restricted checkbox
        document.getElementById('is_restricted').addEventListener('change', function() {
            document.getElementById('allowed_users_container').style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('edit_is_restricted').addEventListener('change', function() {
            document.getElementById('edit_allowed_users_container').style.display = this.checked ? 'block' : 'none';
        });
        
        // Handle poll results modal
        const pollResultsModal = document.getElementById('pollResultsModal');
        if (pollResultsModal) {
            pollResultsModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const pollTitle = button.getAttribute('data-poll-title');
                const pollOptions = JSON.parse(button.getAttribute('data-poll-options'));
                const totalVotes = parseInt(button.getAttribute('data-poll-total-votes'));
                
                document.getElementById('pollResultsTitle').textContent = pollTitle;
                document.getElementById('pollResultsTotalVotes').textContent = totalVotes;
                
                let resultsHtml = '';
                pollOptions.forEach(option => {
                    const percentage = totalVotes > 0 ? Math.round((option.votes / totalVotes) * 100) : 0;
                    resultsHtml += `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium">${option.text}</span>
                                <span class="text-muted">${option.votes} (${percentage}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" style="width: ${percentage}%" aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    `;
                });
                
                document.getElementById('pollResultsContent').innerHTML = resultsHtml;
            });
        }
        
        // Handle start poll modal
        const startPollModal = document.getElementById('startPollModal');
        if (startPollModal) {
            startPollModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const pollId = button.getAttribute('data-poll-id');
                const pollTitle = button.getAttribute('data-poll-title');
                const isScheduled = button.getAttribute('data-poll-scheduled') === '1';
                
                document.getElementById('start_poll_id').value = pollId;
                document.getElementById('startPollTitle').textContent = pollTitle;
                
                const scheduledInfo = document.getElementById('scheduledPollInfo');
                if (isScheduled) {
                    scheduledInfo.style.display = 'block';
                } else {
                    scheduledInfo.style.display = 'none';
                }
            });
        }
        
        // Handle end poll modal
        const endPollModal = document.getElementById('endPollModal');
        if (endPollModal) {
            endPollModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const pollId = button.getAttribute('data-poll-id');
                const pollTitle = button.getAttribute('data-poll-title');
                
                document.getElementById('end_poll_id').value = pollId;
                document.getElementById('endPollTitle').textContent = pollTitle;
            });
        }
        
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
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_role').value = role;
            });
        }
    </script>
</body>
</html>
