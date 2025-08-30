<?php
/**
 * Maintenance Schedule
 * Manage and display a schedule of maintenance tasks for vehicles
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

    // Fetch maintenance tasks from the database
    $maintenance_tasks = fetchAll("SELECT * FROM maintenance_tasks");
    
    // Handle form submission for new maintenance tasks
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate and sanitize input
        $task_description = htmlspecialchars($_POST['task_description']);
        $task_date = htmlspecialchars($_POST['task_date']);
        
        // Insert maintenance task into the database
        $result = insertRecord('maintenance_tasks', [
            'description' => $task_description,
            'date' => $task_date
        ]);
        
        if ($result) {
            $message = 'Maintenance task added successfully!';
        } else {
            $message = 'Failed to add maintenance task.';
        }
    }

?>

<div class="dashboard-container">
    <h1>Maintenance Schedule</h1>
    <form method="POST">
        <input type="text" name="task_description" placeholder="Task Description" required>
        <input type="date" name="task_date" required>
        <button type="submit">Add Task</button>
    </form>

    <h2>Upcoming Maintenance Tasks</h2>
    <ul>
        <?php foreach ($maintenance_tasks as $task): ?>
            <li><?= htmlspecialchars($task['description']) ?> - Due on <?= htmlspecialchars($task['date']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
