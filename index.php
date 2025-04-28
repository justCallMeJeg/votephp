<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect based on role
if ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
    exit;
} else {
    header('Location: voter.php');
    exit;
}

require_once 'classes/VotingSystem.php';

// Initialize the voting system
$votingSystem = new VotingSystem();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle vote submission
    if (isset($_POST['vote'])) {
        $pollId = $_POST['poll_id'];
        $optionId = $_POST['option_id'];
        $votingSystem->vote($pollId, $optionId);
    }
    
    // Handle new poll creation
    if (isset($_POST['create_poll'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $options = explode("\n", $_POST['options']);
        $options = array_map('trim', $options);
        $options = array_filter($options);
        
        $votingSystem->createPoll($title, $description, $options);
    }
}

// Get all polls
$polls = $votingSystem->getPolls();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Voting App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container py-4">
        <header class="pb-3 mb-4 border-bottom">
            <h1 class="display-5 fw-bold">PHP Voting Application</h1>
        </header>

        <div class="row">
            <div class="col-md-8">
                <div class="p-4 mb-4 bg-light rounded-3">
                    <h2>Active Polls</h2>
                    
                    <?php if (empty($polls)): ?>
                        <div class="alert alert-info">No polls available. Create one!</div>
                    <?php else: ?>
                        <div class="accordion" id="pollsAccordion">
                            <?php foreach ($polls as $index => $poll): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $index ?>">
                                        <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $index ?>">
                                            <?= htmlspecialchars($poll->getTitle()) ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#pollsAccordion">
                                        <div class="accordion-body">
                                            <p><?= htmlspecialchars($poll->getDescription()) ?></p>
                                            
                                            <form method="post" action="">
                                                <input type="hidden" name="poll_id" value="<?= $poll->getId() ?>">
                                                
                                                <?php foreach ($poll->getOptions() as $option): ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="radio" name="option_id" id="option<?= $option->getId() ?>" value="<?= $option->getId() ?>" required>
                                                        <label class="form-check-label" for="option<?= $option->getId() ?>">
                                                            <?= htmlspecialchars($option->getText()) ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                                
                                                <button type="submit" name="vote" class="btn btn-primary mt-2">Vote</button>
                                            </form>
                                            
                                            <div class="mt-4">
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
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="p-4 mb-4 bg-light rounded-3">
                    <h2>Create New Poll</h2>
                    <form method="post" action="">
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
                        <button type="submit" name="create_poll" class="btn btn-success">Create Poll</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
