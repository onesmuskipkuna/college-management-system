<?php
/**
 * Maintenance Schedule
 * Manage and display a schedule of maintenance tasks
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

// Fetch maintenance tasks (pseudo code)
// $maintenance_tasks = fetchMaintenanceTasks();

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
