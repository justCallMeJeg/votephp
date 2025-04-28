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
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container-fluid py-4">
        <header class="pb-3 mb-4 border-bottom d-flex justify-content-between align-items-center">
            <h1 class="display-5 fw-bold">Voter Dashboard</h1>
            <div>
                <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </header>

        <div class="row">
            <div class="col-12">
                <div class="p-4 mb-4 bg-light rounded-3">
                    <h2>Active Polls</h2>
                    
                    <?php if (empty($polls)): ?>
                        <div class="alert alert-info">No polls available for you.</div>
                    <?php else: ?>
                        <div class="accordion" id="pollsAccordion">
                            <?php foreach ($polls as $index => $poll): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $index ?>">
                                        <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $index ?>">
                                            <?= htmlspecialchars($poll->getTitle()) ?>
                                            <?php if ($poll->isClosed()): ?>
                                                <span class="badge bg-danger ms-2">Closed</span>
                                            <?php elseif (!$poll->allowsMultipleVotes() && $poll->hasUserVoted($_SESSION['user_id'])): ?>
                                                <span class="badge bg-secondary ms-2">Already Voted</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($poll->allowsMultipleSelections()): ?>
                                                <span class="badge bg-primary ms-2">Select up to <?= $poll->getMaxSelectableOptions() ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if (!$poll->requiresVote()): ?>
                                                <span class="badge bg-success ms-2">Voting Optional</span>
                                            <?php endif; ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#pollsAccordion">
                                        <div class="accordion-body">
                                            <p><?= htmlspecialchars($poll->getDescription()) ?></p>
                                            
                                            <?php if (!$poll->requiresVote() && ($poll->allowsMultipleVotes() || !$poll->hasUserVoted($_SESSION['user_id']))): ?>
                                                <form method="post" action="" id="voteForm<?= $index ?>">
                                                    <input type="hidden" name="poll_id" value="<?= $poll->getId() ?>">
                                                    
                                                    <?php if (!$poll->requiresVote()): ?>
                                                        <div class="alert alert-info mb-3">
                                                            <strong>Note:</strong> Voting on this poll is optional. You can <a href="#results<?= $index ?>" class="alert-link">view the results</a> without voting.
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($poll->allowsMultipleSelections()): ?>
                                                        <div class="alert alert-info">
                                                            Select up to <?= $poll->getMaxSelectableOptions() ?> options
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
                                                    
                                                    <button type="submit" name="vote" class="btn btn-primary mt-2">Vote</button>
                                                    
                                                    <?php if (!$poll->requiresVote() && $poll->shouldShowResultsToUser($_SESSION['user_id'])): ?>
                                                        <a href="#results<?= $index ?>" class="btn btn-outline-primary mt-2 ms-2">View Results Without Voting</a>
                                                    <?php endif; ?>
                                                </form>
                                            <?php elseif (!$poll->allowsMultipleVotes() && $poll->hasUserVoted($_SESSION['user_id'])): ?>
                                                <div class="alert alert-info">You have already voted on this poll.</div>
                                            <?php elseif ($poll->isClosed()): ?>
                                                <div class="alert alert-warning">This poll is closed.</div>
                                            <?php endif; ?>
                                            
                                            <?php if ($poll->shouldShowResultsToUser($_SESSION['user_id'])): ?>
                                                <div class="mt-4" id="results<?= $index ?>">
                                                    <h5>Results:</h5>
                                                    <?php 
                                                    $totalVotes = $poll->getTotalVotes();
                                                    foreach ($poll->getOptions() as $option): 
                                                        $votes = $option->getVotes();
                                                        $percentage = $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0;
                                                    ?>
                                                        <div class="mb-2">
                                                            <div><?= htmlspecialchars($option->getText()) ?> (<?= $votes ?> votes, <?= $percentage ?>%)</div>
                                                            <div class="progress">
                                                                <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <div class="text-muted small">Total votes: <?= $totalVotes ?></div>
                                                </div>
                                            <?php elseif ($poll->getShowResultsMode() === 'after_vote'): ?>
                                                <div class="alert alert-info mt-3">Results will be shown after you vote.</div>
                                            <?php elseif ($poll->getShowResultsMode() === 'after_close'): ?>
                                                <div class="alert alert-info mt-3">Results will be shown after the poll closes<?= $poll->getEndDate() ? ' on ' . date('m/d/Y', strtotime($poll->getEndDate())) : '' ?>.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
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
