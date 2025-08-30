<?php
/**
 * Communication System
 * Send messages and announcements to teachers and students
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

$user = Authentication::getCurrentUser();

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submission for sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient = sanitizeInput($_POST['recipient'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    // Validation
    if (empty($recipient) || empty($message)) {
        $error_message = 'All fields are required.';
    } else {
        try {
            // Insert message into the database
            $query = "INSERT INTO messages (recipient, message, sent_by, sent_at) VALUES (?, ?, ?, NOW())";
            $params = [$recipient, $message, $user['id']];
            executeQuery($query, $params);
            $success_message = 'Message sent successfully!';
        } catch (Exception $e) {
            $error_message = 'Error sending message: ' . $e->getMessage();
        }
    }
}

// Fetch existing messages
$messages = fetchAll("SELECT * FROM messages ORDER BY sent_at DESC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Communication System</h1>
                <p class="text-muted">Send messages and announcements to teachers and students.</p>
            </div>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <h5>Send Message</h5>
            <form method="POST">
                <div class="mb-3">
                    <label for="recipient" class="form-label">Recipient</label>
                    <input type="text" class="form-control" id="recipient" name="recipient" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>

    <h5>Sent Messages</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Recipient</th>
                <th>Message</th>
                <th>Sent By</th>
                <th>Sent At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr>
                <td><?= htmlspecialchars($msg['recipient']) ?></td>
                <td><?= htmlspecialchars($msg['message']) ?></td>
                <td><?= htmlspecialchars($msg['sent_by']) ?></td>
                <td><?= htmlspecialchars($msg['sent_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
