<?php
/**
 * School Calendar
 * Allows headteachers to manage the academic calendar and events
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

// Initialize variables for calendar management
$events = []; // Initialize events array
$message = '';

// Fetch events from the database
try {
    $sql = "SELECT * FROM events"; // Example query
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $message = 'Error fetching events: ' . $e->getMessage();
}

// Handle form submission for adding a new event
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $event_name = htmlspecialchars($_POST['event_name']);
    $event_date = htmlspecialchars($_POST['event_date']);
    $event_description = htmlspecialchars($_POST['event_description']);

    // Insert event into the database
    try {
        $sql = "INSERT INTO events (event_name, event_date, event_description) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sss", $event_name, $event_date, $event_description);
        $stmt->execute();
        $message = 'Event added successfully!';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

?>

<div class="dashboard-container">
    <h1>School Calendar</h1>
    <p><?= $message ?></p>

    <form method="POST">
        <input type="text" name="event_name" placeholder="Event Name" required>
        <input type="date" name="event_date" required>
        <textarea name="event_description" placeholder="Event Description" required></textarea>
        <button type="submit">Add Event</button>
    </form>

    <h2>Upcoming Events</h2>
    <ul>
        <?php foreach ($events as $event): ?>
            <li><?= htmlspecialchars($event['event_name']) ?> on <?= htmlspecialchars($event['event_date']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
