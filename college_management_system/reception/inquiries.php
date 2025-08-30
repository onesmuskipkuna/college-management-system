<?php
/**
 * Inquiries Management
 * Handles student inquiries and complaints submitted at reception
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require reception role
Authentication::requireRole('reception');

// Initialize variables for inquiries management
$message = '';

// Handle form submission for new inquiries
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $inquiry_subject = htmlspecialchars($_POST['inquiry_subject']);
    $inquiry_message = htmlspecialchars($_POST['inquiry_message']);

    // Insert inquiry into the database
    $result = insertRecord('inquiries', [
        'subject' => $inquiry_subject,
        'message' => $inquiry_message
    ]);
    if ($result) {
        $message = 'Inquiry submitted successfully!';
    } else {
        $message = 'Failed to submit inquiry.';
    }

    // Fetch existing inquiries from the database
    $existing_inquiries = fetchAll("SELECT * FROM inquiries");
}

// Fetch existing inquiries from the database (pseudo code)
// $existing_inquiries = fetchExistingInquiries();

?>

<div class="dashboard-container">
    <h1>Inquiries Management</h1>
    <p><?= $message ?></p>

    <form method="POST">
        <input type="text" name="inquiry_subject" placeholder="Inquiry Subject" required>
        <textarea name="inquiry_message" placeholder="Describe your inquiry" required></textarea>
        <button type="submit">Submit Inquiry</button>
    </form>

    <h2>Existing Inquiries</h2>
    <ul>
        <?php // foreach ($existing_inquiries as $inquiry): ?>
            <li>
                <?= htmlspecialchars($inquiry['subject']) ?> - <?= htmlspecialchars($inquiry['message']) ?>
                <button>Resolve</button>
                <button>Delete</button>
            </li>
        <?php // endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
