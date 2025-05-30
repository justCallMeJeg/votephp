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

// Filter polls by status
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$filteredPolls = [];

foreach ($polls as $poll) {
    switch ($statusFilter) {
        case 'active':
            if ($poll->isActive()) $filteredPolls[] = $poll;
            break;
        case 'ended':
            if ($poll->isEnded()) $filteredPolls[] = $poll;
            break;
        case 'voted':
            if ($poll->hasUserVoted($_SESSION['user_id'])) $filteredPolls[] = $poll;
            break;
        case 'not_voted':
            if (!$poll->hasUserVoted($_SESSION['user_id']) && $poll->isActive()) $filteredPolls[] = $poll;
            break;
        default:
            $filteredPolls[] = $poll;
            break;
    }
}
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
        <header class="pb-3 mb-4 border-bottom d-flex justify-content-between align-items-center p-4">
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

        <!-- Poll Filter Tabs -->
        <div class="poll-filter-tabs mb-4">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link <?= $statusFilter === 'all' ? 'active' : '' ?>" href="?status=all">
                        <i class="bi bi-list"></i> All Polls (<?= count($polls) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $statusFilter === 'active' ? 'active' : '' ?>" href="?status=active">
                        <i class="bi bi-play-circle"></i> Active (<?= count(array_filter($polls, function($poll) { return $poll->isActive(); })) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $statusFilter === 'ended' ? 'active' : '' ?>" href="?status=ended">
                        <i class="bi bi-stop-circle"></i> Ended (<?= count(array_filter($polls, function($poll) { return $poll->isEnded(); })) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $statusFilter === 'voted' ? 'active' : '' ?>" href="?status=voted">
                        <i class="bi bi-check-circle"></i> Voted (<?= count(array_filter($polls, function($poll) { return $poll->hasUserVoted($_SESSION['user_id']); })) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $statusFilter === 'not_voted' ? 'active' : '' ?>" href="?status=not_voted">
                        <i class="bi bi-circle"></i> Not Voted (<?= count(array_filter($polls, function($poll) { return !$poll->hasUserVoted($_SESSION['user_id']) && $poll->isActive(); })) ?>)
                    </a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-12">
                <?php if (empty($filteredPolls)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                            <h3 class="text-muted">No Polls Available</h3>
                            <p class="text-muted">
                                <?php if ($statusFilter === 'all'): ?>
                                    There are no polls available for you at the moment.
                                <?php else: ?>
                                    No polls match the selected filter.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($filteredPolls as $index => $poll): ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card h-100 poll-card" style="cursor: pointer;" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="<?= $poll->canBeVotedOn() && ($poll->allowsMultipleVotes() || !$poll->hasUserVoted($_SESSION['user_id'])) ? '#votingModal' : '#pollDetailsModal' ?>"
                                    data-poll-id="<?= $poll->getId() ?>"
                                    data-poll-title="<?= htmlspecialchars($poll->getTitle()) ?>"
                                    data-poll-description="<?= htmlspecialchars($poll->getDescription()) ?>"
                                    data-poll-type="<?= $poll->getPollType() ?>"
                                    data-poll-type-display="<?= htmlspecialchars($poll->getDisplayName()) ?>"
                                    data-poll-status="<?= $poll->getStatus() ?>"
                                    data-poll-can-vote="<?= $poll->canBeVotedOn() && ($poll->allowsMultipleVotes() || !$poll->hasUserVoted($_SESSION['user_id'])) ? '1' : '0' ?>"
                                    data-poll-has-voted="<?= $poll->hasUserVoted($_SESSION['user_id']) ? '1' : '0' ?>"
                                    data-poll-requires-vote="<?= $poll->requiresVote() ? '1' : '0' ?>"
                                    data-poll-multiple-selections="<?= $poll->allowsMultipleSelections() ? '1' : '0' ?>"
                                    data-poll-max-options="<?= $poll->getMaxSelectableOptions() ?>"
                                    data-poll-options="<?= htmlspecialchars(json_encode(array_map(function($option) {
                                        return ['id' => $option->getId(), 'text' => $option->getText(), 'votes' => $option->getVotes()];
                                    }, $poll->getOptions()))) ?>"
                                    data-poll-total-votes="<?= $poll->getTotalVotes() ?>"
                                    data-poll-show-results="<?= $poll->shouldShowResultsToUser($_SESSION['user_id']) ? '1' : '0' ?>"
                                    data-poll-end-date="<?= $poll->getEndDate() ? date('M j, Y g:i A', strtotime($poll->getEndDate())) : '' ?>">
                                    
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
                                        
                                        <?php if ($poll->getEndDate()): ?>
                                            <div class="alert alert-info alert-sm mb-3">
                                                <i class="bi bi-calendar"></i>
                                                <small><strong>Scheduled:</strong> <?= date('M j, Y g:i A', strtotime($poll->getEndDate())) ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($poll->canBeVotedOn() && ($poll->allowsMultipleVotes() || !$poll->hasUserVoted($_SESSION['user_id']))): ?>
                                            <div class="d-flex align-items-center text-primary">
                                                <i class="bi bi-hand-index me-2"></i>
                                                <small>Click to vote</small>
                                            </div>
                                        <?php elseif (!$poll->canBeVotedOn()): ?>
                                            <div class="d-flex align-items-center text-warning">
                                                <i class="bi bi-pause-circle me-2"></i>
                                                <small>Poll Closed</small>
                                            </div>
                                        <?php elseif (!$poll->allowsMultipleVotes() && $poll->hasUserVoted($_SESSION['user_id'])): ?>
                                            <div class="d-flex align-items-center text-success">
                                                <i class="bi bi-check-circle me-2"></i>
                                                <small>Already voted</small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($poll->shouldShowResultsToUser($_SESSION['user_id'])): ?>
                                            <div class="mt-3">
                                                <small class="text-muted">
                                                    <i class="bi bi-bar-chart"></i>
                                                    Total votes: <?= $poll->getTotalVotes() ?>
                                                </small>
                                            </div>
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

    <!-- Voting Modal -->
    <div class="modal fade" id="votingModal" tabindex="-1" aria-labelledby="votingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg voting-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="votingModalLabel">
                        <i class="bi bi-ballot"></i> Cast Your Vote
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="votingContent">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="submitVoteBtn">
                        <i class="bi bi-check-circle"></i> Submit Vote
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Poll Details Modal -->
    <div class="modal fade" id="pollDetailsModal" tabindex="-1" aria-labelledby="pollDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pollDetailsModalLabel">
                        <i class="bi bi-info-circle"></i> Poll Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="pollDetailsContent">
                        <!-- Content will be populated by JavaScript -->
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

    <!-- Vote Confirmation Modal -->
    <div class="modal fade" id="voteConfirmationModal" tabindex="-1" aria-labelledby="voteConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog confirmation-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voteConfirmationModalLabel">
                        <i class="bi bi-exclamation-triangle"></i> Confirm Your Vote
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="confirmationContent">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmVoteBtn">
                        <i class="bi bi-check-circle"></i> Confirm Vote
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPollData = null;
        let selectedOptions = [];

        // Handle voting modal
        const votingModal = document.getElementById('votingModal');
        if (votingModal) {
            votingModal.addEventListener('show.bs.modal', function (event) {
                const card = event.relatedTarget;
                currentPollData = {
                    id: card.getAttribute('data-poll-id'),
                    title: card.getAttribute('data-poll-title'),
                    description: card.getAttribute('data-poll-description'),
                    typeDisplay: card.getAttribute('data-poll-type-display'),
                    requiresVote: card.getAttribute('data-poll-requires-vote') === '1',
                    multipleSelections: card.getAttribute('data-poll-multiple-selections') === '1',
                    maxOptions: parseInt(card.getAttribute('data-poll-max-options')),
                    options: JSON.parse(card.getAttribute('data-poll-options'))
                };

                selectedOptions = [];
                populateVotingModal();
            });
        }

        // Handle poll details modal
        const pollDetailsModal = document.getElementById('pollDetailsModal');
        if (pollDetailsModal) {
            pollDetailsModal.addEventListener('show.bs.modal', function (event) {
                const card = event.relatedTarget;
                const pollData = {
                    title: card.getAttribute('data-poll-title'),
                    description: card.getAttribute('data-poll-description'),
                    typeDisplay: card.getAttribute('data-poll-type-display'),
                    status: card.getAttribute('data-poll-status'),
                    hasVoted: card.getAttribute('data-poll-has-voted') === '1',
                    showResults: card.getAttribute('data-poll-show-results') === '1',
                    options: JSON.parse(card.getAttribute('data-poll-options')),
                    totalVotes: parseInt(card.getAttribute('data-poll-total-votes')),
                    endDate: card.getAttribute('data-poll-end-date')
                };

                populatePollDetailsModal(pollData);
            });
        }

        function populateVotingModal() {
            const content = document.getElementById('votingContent');
            let html = `
                <div class="mb-4">
                    <h4>${currentPollData.title}</h4>
                    <p class="text-muted">${currentPollData.description}</p>
                    <div class="poll-type-indicator mb-3">
                        <i class="bi bi-${currentPollData.multipleSelections ? 'check2-all' : 'check2'}"></i>
                        ${currentPollData.typeDisplay}
                    </div>
                </div>
            `;

            if (!currentPollData.requiresVote) {
                html += `
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Note:</strong> Voting is optional for this poll.
                    </div>
                `;
            }

            if (currentPollData.multipleSelections) {
                html += `
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-check2-all"></i>
                        Select up to ${currentPollData.maxOptions} options
                    </div>
                `;
            }

            html += '<div class="voting-options">';
            currentPollData.options.forEach(option => {
                const inputType = currentPollData.multipleSelections ? 'checkbox' : 'radio';
                html += `
                    <div class="form-check mb-3 p-3 border rounded">
                        <input class="form-check-input voting-option" type="${inputType}" 
                               name="vote_option" id="option_${option.id}" value="${option.id}"
                               ${currentPollData.requiresVote && !currentPollData.multipleSelections ? 'required' : ''}>
                        <label class="form-check-label fw-medium" for="option_${option.id}">
                            ${option.text}
                        </label>
                    </div>
                `;
            });
            html += '</div>';

            content.innerHTML = html;

            // Add event listeners for option selection
            document.querySelectorAll('.voting-option').forEach(input => {
                input.addEventListener('change', handleOptionSelection);
            });
        }

        function populatePollDetailsModal(pollData) {
            const content = document.getElementById('pollDetailsContent');
            let html = `
                <div class="mb-4">
                    <h4>${pollData.title}</h4>
                    <p class="text-muted">${pollData.description}</p>
                    <div class="poll-type-indicator mb-3">
                        <i class="bi bi-${pollData.typeDisplay.includes('Multiple') ? 'check2-all' : 'check2'}"></i>
                        ${pollData.typeDisplay}
                    </div>
                </div>
            `;

            if (pollData.endDate) {
                html += `
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-calendar"></i>
                        <strong>Scheduled:</strong> ${pollData.endDate}
                    </div>
                `;
            }

            if (pollData.status === 'draft') {
                html += `
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-pause-circle"></i>
                        This poll is not yet active. Check back later to vote.
                    </div>
                `;
            } else if (pollData.hasVoted) {
                html += `
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle"></i>
                        You have already voted on this poll.
                    </div>
                `;
            }

            if (pollData.showResults) {
                html += '<h6><i class="bi bi-bar-chart"></i> Results:</h6>';
                pollData.options.forEach(option => {
                    const percentage = pollData.totalVotes > 0 ? Math.round((option.votes / pollData.totalVotes) * 100) : 0;
                    html += `
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
                html += `
                    <div class="text-muted small mt-3">
                        <i class="bi bi-people"></i>
                        Total votes: ${pollData.totalVotes}
                    </div>
                `;
            } else {
                html += `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Results will be shown after you vote or when the poll ends.
                    </div>
                `;
            }

            content.innerHTML = html;
        }

        function handleOptionSelection() {
            selectedOptions = [];
            document.querySelectorAll('.voting-option:checked').forEach(input => {
                selectedOptions.push({
                    id: input.value,
                    text: input.nextElementSibling.textContent.trim()
                });
            });

            // Validate multiple selection limit
            if (currentPollData.multipleSelections && selectedOptions.length > currentPollData.maxOptions) {
                this.checked = false;
                selectedOptions = selectedOptions.slice(0, currentPollData.maxOptions);
                alert(`You can only select up to ${currentPollData.maxOptions} options.`);
                return;
            }

            // Update submit button state
            const submitBtn = document.getElementById('submitVoteBtn');
            if (currentPollData.requiresVote) {
                submitBtn.disabled = selectedOptions.length === 0;
            } else {
                submitBtn.disabled = false;
            }
        }

        // Handle vote submission
        document.getElementById('submitVoteBtn').addEventListener('click', function() {
            if (currentPollData.requiresVote && selectedOptions.length === 0) {
                alert('Please select at least one option.');
                return;
            }

            showVoteConfirmation();
        });

        function showVoteConfirmation() {
            const modal = new bootstrap.Modal(document.getElementById('voteConfirmationModal'));
            const content = document.getElementById('confirmationContent');
            
            let html = `
                <h5>Are you sure you want to submit your vote?</h5>
                <p><strong>Poll:</strong> ${currentPollData.title}</p>
            `;

            if (selectedOptions.length > 0) {
                html += '<p><strong>Your selection:</strong></p><ul>';
                selectedOptions.forEach(option => {
                    html += `<li>${option.text}</li>`;
                });
                html += '</ul>';
            } else {
                html += '<p><strong>You are submitting without selecting any options.</strong></p>';
            }

            if (!currentPollData.requiresVote) {
                html += '<p class="text-muted"><small>Note: Voting is optional for this poll.</small></p>';
            }

            content.innerHTML = html;
            modal.show();
        }

        // Handle vote confirmation
        document.getElementById('confirmVoteBtn').addEventListener('click', function() {
            submitVote();
        });

        function submitVote() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const pollIdInput = document.createElement('input');
            pollIdInput.type = 'hidden';
            pollIdInput.name = 'poll_id';
            pollIdInput.value = currentPollData.id;
            form.appendChild(pollIdInput);

            if (currentPollData.multipleSelections) {
                selectedOptions.forEach(option => {
                    const optionInput = document.createElement('input');
                    optionInput.type = 'hidden';
                    optionInput.name = 'option_ids[]';
                    optionInput.value = option.id;
                    form.appendChild(optionInput);
                });
            } else if (selectedOptions.length > 0) {
                const optionInput = document.createElement('input');
                optionInput.type = 'hidden';
                optionInput.name = 'option_id';
                optionInput.value = selectedOptions[0].id;
                form.appendChild(optionInput);
            }

            const voteInput = document.createElement('input');
            voteInput.type = 'hidden';
            voteInput.name = 'vote';
            voteInput.value = '1';
            form.appendChild(voteInput);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
