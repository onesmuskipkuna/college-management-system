<?php
/**
 * Notifications Management
 * Manages notifications related to student services and reception activities
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require reception role
Authentication::requireRole('reception');

// Initialize variables for notifications management
$message = '';

// Handle form submission for new notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $notification_title = htmlspecialchars($_POST['notification_title']);
    $notification_message = htmlspecialchars($_POST['notification_message']);

    // Insert notification into the database
    $result = insertRecord('notifications', [
        'title' => $notification_title,
        'message' => $notification_message
    ]);
    if ($result) {
        $message = 'Notification added successfully!';
    } else {
        $message = 'Failed to add notification.';
    }

    // Fetch existing notifications from the database
    $existing_notifications = fetchAll("SELECT * FROM notifications");
}

// Fetch existing notifications from the database (pseudo code)
// $existing_notifications = fetchExistingNotifications();

?>

<div class="dashboard-container">
    <h1>Notifications Management</h1>
    <p><?= $message ?></p>

    <form method="POST">
        <input type="text" name="notification_title" placeholder="Notification Title" required>
        <textarea name="notification_message" placeholder="Notification details" required></textarea>
        <button type="submit">Add Notification</button>
    </form>

    <h2>Existing Notifications</h2>
    <ul>
        <?php // foreach ($existing_notifications as $notification): ?>
            <li>
                <?= htmlspecialchars($notification['title']) ?> - <?= htmlspecialchars($notification['message']) ?>
                <button>Delete</button>
            </li>
        <?php // endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
