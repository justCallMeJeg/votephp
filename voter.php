<?php
session_start();
require_once 'classes/VotingSystem.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect admin to admin page
if ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
    exit;
}

// Initialize the voting system
$votingSystem = new VotingSystem();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle vote submission
    if (isset($_POST['vote'])) {
        $pollId = $_POST['poll_id'];
        
        // Handle multiple selections
        if (isset($_POST['option_ids']) && is_array($_POST['option_ids'])) {
            $optionIds = $_POST['option_ids'];
            $votingSystem->vote($pollId, $optionIds, $_SESSION['user_id']);
        } elseif (isset($_POST['option_id'])) {
            // Handle single selection
            $optionId = $_POST['option_id'];
            $votingSystem->vote($pollId, $optionId, $_SESSION['user_id']);
        }
    }
}

// Get polls accessible to this user
$polls = $votingSystem->getPollsForUser($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - PHP Voting App</title>
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
                    <i class="bi bi-person-check text-primary"></i>
                    Voter Dashboard
                </h1>
                <p class="text-muted mb-0">Cast your votes and view poll results</p>
            </div>
            <div class="d-flex align-items-center gap-2">
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= count($polls) ?></h3>
                            <p class="mb-0">Available Polls</p>
                        </div>
                        <i class="bi bi-bar-chart-fill fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= count(array_filter($polls, function($poll) { return $poll->hasUserVoted($_SESSION['user_id']); })) ?></h3>
                            <p class="mb-0">Polls Voted</p>
                        </div>
                        <i class="bi bi-check2-square fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
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
                <?php if (empty($polls)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                            <h3 class="text-muted">No Polls Available</h3>
                            <p class="text-muted">There are no active polls for you to vote on at the moment.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($polls as $index => $poll): ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-1"><?= htmlspecialchars($poll->getTitle()) ?></h5>
                                            <div class="poll-type-indicator mb-2">
                                                <i class="bi bi-<?= $poll->getPollType() === 'yes_no' ? 'toggle-on' : ($poll->allowsMultipleSelections() ? 'check2-all' : 'check2') ?>"></i>
                                                <?= $poll->getDisplayName() ?>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge <?= $poll->getStatusBadgeClass() ?>">
                                                <i class="bi bi-<?= $poll->isDraft() ? 'file-earmark' : ($poll->isActive() ? 'play-circle' : 'stop-circle') ?>"></i>
                                                <?= $poll->getStatusDisplayName() ?>
                                            </span>
                                            
                                            <?php if (!$poll->allowsMultipleVotes() && $poll->hasUserVoted($_SESSION['user_id'])): ?>
                                                <span class="badge bg-success">Voted</span>
                                            <?php endif; ?>
                                            
                                            <?php if (!$poll->requiresVote()): ?>
                                                <span class="badge bg-info">Optional</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body">
                                        <p class="card-text text-muted mb-3"><?= htmlspecialchars($poll->getDescription()) ?></p>
                                        
                                        <?php if ($poll->canBeVotedOn() && ($poll->allowsMultipleVotes() || !$poll->hasUserVoted($_SESSION['user_id']))): ?>
                                            <form method="post" action="" id="voteForm<?= $index ?>" class="mb-3">
                                                <input type="hidden" name="poll_id" value="<?= $poll->getId() ?>">
                                                
                                                <?php if (!$poll->requiresVote()): ?>
                                                    <div class="alert alert-success alert-sm mb-3">
                                                        <i class="bi bi-info-circle"></i>
                                                        <small><strong>Note:</strong> Voting is optional for this poll.</small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($poll->allowsMultipleSelections()): ?>
                                                    <div class="alert alert-info alert-sm mb-3">
                                                        <i class="bi bi-check2-all"></i>
                                                        <small>Select up to <?= $poll->getMaxSelectableOptions() ?> options</small>
                                                    </div>
                                                    
                                                    <?php foreach ($poll->getOptions() as $option): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input option-checkbox" type="checkbox" 
                                                                   name="option_ids[]" 
                                                                   id="option<?= $option->getId() ?>" 
                                                                   value="<?= $option->getId() ?>"
                                                                   data-max-options="<?= $poll->getMaxSelectableOptions() ?>"
                                                                   data-form-id="voteForm<?= $index ?>">
                                                            <label class="form-check-label" for="option<?= $option->getId() ?>">
                                                                <?= htmlspecialchars($option->getText()) ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <?php foreach ($poll->getOptions() as $option): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="radio" name="option_id" id="option<?= $option->getId() ?>" value="<?= $option->getId() ?>" <?= $poll->requiresVote() ? 'required' : '' ?>>
                                                            <label class="form-check-label" for="option<?= $option->getId() ?>">
                                                                <?= htmlspecialchars($option->getText()) ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex gap-2 mt-3">
                                                    <button type="submit" name="vote" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-check-circle"></i> Cast Vote
                                                    </button>
                                                    
                                                    <?php if (!$poll->requiresVote() && $poll->shouldShowResultsToUser($_SESSION['user_id'])): ?>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#results<?= $index ?>">
                                                            <i class="bi bi-bar-chart"></i> View Results
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </form>
                                        <?php elseif (!$poll->canBeVotedOn()): ?>
                                            <div class="alert alert-warning alert-sm">
                                                <i class="bi bi-pause-circle"></i>
                                                <small>This poll is not yet active for voting.</small>
                                            </div>
                                        <?php elseif (!$poll->allowsMultipleVotes() && $poll->hasUserVoted($_SESSION['user_id'])): ?>
                                            <div class="alert alert-success alert-sm">
                                                <i class="bi bi-check-circle"></i>
                                                <small>You have already voted on this poll.</small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($poll->shouldShowResultsToUser($_SESSION['user_id'])): ?>
                                            <div class="collapse" id="results<?= $index ?>">
                                                <div class="border-top pt-3">
                                                    <h6><i class="bi bi-bar-chart"></i> Results:</h6>
                                                    <?php 
                                                    $totalVotes = $poll->getTotalVotes();
                                                    foreach ($poll->getOptions() as $option): 
                                                        $votes = $option->getVotes();
                                                        $percentage = $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0;
                                                    ?>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <small class="fw-medium"><?= htmlspecialchars($option->getText()) ?></small>
                                                                <small class="text-muted"><?= $votes ?> (<?= $percentage ?>%)</small>
                                                            </div>
                                                            <div class="progress" style="height: 6px;">
                                                                <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <div class="text-muted small mt-2">
                                                        <i class="bi bi-people"></i>
                                                        Total votes: <?= $totalVotes ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif ($poll->getShowResultsMode() === 'after_vote'): ?>
                                            <div class="alert alert-info alert-sm">
                                                <i class="bi bi-info-circle"></i>
                                                <small>Results will be shown after you vote.</small>
                                            </div>
                                        <?php elseif ($poll->getShowResultsMode() === 'after_close'): ?>
                                            <div class="alert alert-info alert-sm">
                                                <i class="bi bi-info-circle"></i>
                                                <small>Results will be shown after the poll ends<?= $poll->getEndDate() ? ' on ' . date('M j, Y', strtotime($poll->getEndDate())) : '' ?>.</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($poll->shouldShowResultsToUser($_SESSION['user_id']) && !($poll->canBeVotedOn() && ($poll->allowsMultipleVotes() || !$poll->hasUserVoted($_SESSION['user_id'])))): ?>
                                        <div class="card-footer bg-light">
                                            <h6 class="mb-2"><i class="bi bi-bar-chart"></i> Results:</h6>
                                            <?php 
                                            $totalVotes = $poll->getTotalVotes();
                                            foreach ($poll->getOptions() as $option): 
                                                $votes = $option->getVotes();
                                                $percentage = $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0;
                                            ?>
                                                <div class="mb-2">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="fw-medium"><?= htmlspecialchars($option->getText()) ?></small>
                                                        <small class="text-muted"><?= $votes ?> (<?= $percentage ?>%)</small>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <div class="text-muted small mt-2">
                                                <i class="bi bi-people"></i>
                                                Total votes: <?= $totalVotes ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle multiple selection checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const optionCheckboxes = document.querySelectorAll('.option-checkbox');
            
            optionCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const formId = this.getAttribute('data-form-id');
                    const maxOptions = parseInt(this.getAttribute('data-max-options'));
                    const form = document.getElementById(formId);
                    const checkedBoxes = form.querySelectorAll('.option-checkbox:checked');
                    
                    if (checkedBoxes.length > maxOptions) {
                        this.checked = false;
                        alert('You can only select up to ' + maxOptions + ' options.');
                    }
                });
            });
        });
    </script>
</body>
</html>
