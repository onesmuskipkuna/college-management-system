<?php
/**
 * Student Performance Analytics
 * Display analytics and insights into student performance metrics
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

$user = Authentication::getCurrentUser();

// Fetch performance metrics
$performance_data = fetchAll("
    SELECT s.first_name, s.last_name, AVG(g.marks) as average_grade, COUNT(a.id) as attendance_count
    FROM students s
    LEFT JOIN grades g ON s.id = g.student_id
    LEFT JOIN attendance a ON s.id = a.student_id
    GROUP BY s.id
    ORDER BY average_grade DESC
");

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Student Performance Analytics</h1>
                <p class="text-muted">Insights into student performance metrics.</p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Performance Overview</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Average Grade</th>
                                <th>Attendance Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($performance_data as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                <td><?= number_format($student['average_grade'], 2) ?></td>
                                <td><?= $student['attendance_count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
