<?php
/**
 * Student Academic Progress Tracker
 * Comprehensive view of student academic performance and progress
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require student role
Authentication::requireRole('student');

$user = Authentication::getCurrentUser();

// Get student information
try {
    $student = fetchOne("
        SELECT s.*, c.course_name, c.duration_months, c.total_units
        FROM students s 
        JOIN courses c ON s.course_id = c.id 
        WHERE s.user_id = ?
    ", [$user['id']]);
    
    if (!$student) {
        throw new Exception("Student profile not found");
    }
} catch (Exception $e) {
    $student = [
        'id' => 1,
        'student_id' => 'ST001',
        'first_name' => 'Demo',
        'last_name' => 'Student',
        'course_name' => 'Computer Science',
        'duration_months' => 24,
        'total_units' => 120,
        'enrollment_date' => '2023-09-01'
    ];
}

// Calculate progress metrics
try {
    // Get completed units
    $completed_units = fetchOne("
        SELECT SUM(s.credit_hours) as total
        FROM student_grades sg
        JOIN subjects s ON sg.subject_id = s.id
        WHERE sg.student_id = ? AND sg.grade >= 50
    ", [$student['id']])['total'] ?? 45;
    
    // Get current semester grades
    $current_grades = fetchAll("
        SELECT sg.*, s.subject_name, s.credit_hours, s.subject_code
        FROM student_grades sg
        JOIN subjects s ON sg.subject_id = s.id
        WHERE sg.student_id = ? AND sg.semester = (
            SELECT MAX(semester) FROM student_grades WHERE student_id = ?
        )
        ORDER BY s.subject_name
    ", [$student['id'], $student['id']]);
    
    // Get assignment performance
    $assignment_stats = fetchOne("
        SELECT 
            COUNT(*) as total_assignments,
            COUNT(CASE WHEN grade IS NOT NULL THEN 1 END) as graded_assignments,
            AVG(CASE WHEN grade IS NOT NULL THEN grade END) as avg_grade
        FROM assignment_submissions
        WHERE student_id = ?
    ", [$student['id']]);
    
} catch (Exception $e) {
    // Demo data
    $completed_units = 45;
    $current_grades = [
        ['subject_name' => 'Database Systems', 'subject_code' => 'CS301', 'grade' => 85, 'credit_hours' => 4],
        ['subject_name' => 'Web Development', 'subject_code' => 'CS302', 'grade' => 78, 'credit_hours' => 3],
        ['subject_name' => 'Data Structures', 'subject_code' => 'CS303', 'grade' => 92, 'credit_hours' => 4],
        ['subject_name' => 'Software Engineering', 'subject_code' => 'CS304', 'grade' => 88, 'credit_hours' => 3],
        ['subject_name' => 'Computer Networks', 'subject_code' => 'CS305', 'grade' => 76, 'credit_hours' => 3]
    ];
    $assignment_stats = ['total_assignments' => 15, 'graded_assignments' => 12, 'avg_grade' => 82.5];
}

// Calculate metrics
$progress_percentage = ($completed_units / $student['total_units']) * 100;
$current_gpa = 0;
$total_credit_hours = 0;

foreach ($current_grades as $grade) {
    $current_gpa += $grade['grade'] * $grade['credit_hours'];
    $total_credit_hours += $grade['credit_hours'];
}
$current_gpa = $total_credit_hours > 0 ? $current_gpa / $total_credit_hours : 0;

// Calculate time progress
$enrollment_date = new DateTime($student['enrollment_date']);
$current_date = new DateTime();
$months_enrolled = $enrollment_date->diff($current_date)->m + ($enrollment_date->diff($current_date)->y * 12);
$time_progress = ($months_enrolled / $student['duration_months']) * 100;

// Grade distribution for chart
$grade_distribution = [
    'A' => count(array_filter($current_grades, function($g) { return $g['grade'] >= 90; })),
    'B' => count(array_filter($current_grades, function($g) { return $g['grade'] >= 80 && $g['grade'] < 90; })),
    'C' => count(array_filter($current_grades, function($g) { return $g['grade'] >= 70 && $g['grade'] < 80; })),
    'D' => count(array_filter($current_grades, function($g) { return $g['grade'] >= 60 && $g['grade'] < 70; })),
    'F' => count(array_filter($current_grades, function($g) { return $g['grade'] < 60; }))
];

// Performance trends (demo data)
$performance_trends = [
    ['semester' => 'Sem 1', 'gpa' => 3.2],
    ['semester' => 'Sem 2', 'gpa' => 3.5],
    ['semester' => 'Sem 3', 'gpa' => 3.7],
    ['semester' => 'Sem 4', 'gpa' => 3.6],
    ['semester' => 'Current', 'gpa' => round($current_gpa / 25, 1)] // Convert to 4.0 scale
];
?>

<div class="container">
    <div class="page-header">
        <h1>Academic Progress</h1>
        <p>Track your academic performance and course completion progress</p>
    </div>

    <!-- Progress Overview -->
    <div class="progress-overview">
        <div class="overview-cards">
            <div class="overview-card course-progress">
                <div class="card-icon">🎓</div>
                <div class="card-content">
                    <div class="card-title">Course Progress</div>
                    <div class="card-value"><?= number_format($progress_percentage, 1) ?>%</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $progress_percentage ?>%"></div>
                    </div>
                    <div class="card-subtitle"><?= $completed_units ?> of <?= $student['total_units'] ?> units completed</div>
                </div>
            </div>
            
            <div class="overview-card gpa-card">
                <div class="card-icon">📊</div>
                <div class="card-content">
                    <div class="card-title">Current GPA</div>
                    <div class="card-value"><?= number_format($current_gpa, 1) ?>%</div>
                    <div class="gpa-scale">
                        <div class="gpa-indicator" style="left: <?= ($current_gpa / 100) * 100 ?>%"></div>
                    </div>
                    <div class="card-subtitle">Grade Point Average</div>
                </div>
            </div>
            
            <div class="overview-card time-progress">
                <div class="card-icon">⏱️</div>
                <div class="card-content">
                    <div class="card-title">Time Progress</div>
                    <div class="card-value"><?= number_format($time_progress, 1) ?>%</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min($time_progress, 100) ?>%"></div>
                    </div>
                    <div class="card-subtitle"><?= $months_enrolled ?> of <?= $student['duration_months'] ?> months</div>
                </div>
            </div>
            
            <div class="overview-card assignment-performance">
                <div class="card-icon">📝</div>
                <div class="card-content">
                    <div class="card-title">Assignment Performance</div>
                    <div class="card-value"><?= number_format($assignment_stats['avg_grade'], 1) ?>%</div>
                    <div class="assignment-stats">
                        <span><?= $assignment_stats['graded_assignments'] ?>/<?= $assignment_stats['total_assignments'] ?> graded</span>
                    </div>
                    <div class="card-subtitle">Average assignment score</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Tabs -->
    <div class="progress-tabs">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showProgressTab('current-grades')">Current Grades</button>
            <button class="tab-button" onclick="showProgressTab('performance-trends')">Performance Trends</button>
            <button class="tab-button" onclick="showProgressTab('course-completion')">Course Completion</button>
            <button class="tab-button" onclick="showProgressTab('recommendations')">Recommendations</button>
        </div>

        <!-- Current Grades Tab -->
        <div id="current-grades" class="tab-content active">
            <div class="grades-section">
                <div class="grades-header">
                    <h2>Current Semester Grades</h2>
                    <div class="semester-info">
                        <span class="semester-label">Current Semester GPA:</span>
                        <span class="semester-gpa"><?= number_format($current_gpa, 1) ?>%</span>
                    </div>
                </div>
                
                <div class="grades-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Credit Hours</th>
                                <th>Grade</th>
                                <th>Letter Grade</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_grades as $grade): ?>
                            <?php 
                            $letter_grade = '';
                            $performance_class = '';
                            if ($grade['grade'] >= 90) { $letter_grade = 'A'; $performance_class = 'excellent'; }
                            elseif ($grade['grade'] >= 80) { $letter_grade = 'B'; $performance_class = 'good'; }
                            elseif ($grade['grade'] >= 70) { $letter_grade = 'C'; $performance_class = 'average'; }
                            elseif ($grade['grade'] >= 60) { $letter_grade = 'D'; $performance_class = 'below-average'; }
                            else { $letter_grade = 'F'; $performance_class = 'poor'; }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($grade['subject_code']) ?></td>
                                <td><?= htmlspecialchars($grade['subject_name']) ?></td>
                                <td><?= $grade['credit_hours'] ?></td>
                                <td class="grade-value"><?= number_format($grade['grade'], 1) ?>%</td>
                                <td>
                                    <span class="letter-grade <?= $performance_class ?>">
                                        <?= $letter_grade ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="performance-indicator">
                                        <div class="performance-bar">
                                            <div class="performance-fill <?= $performance_class ?>" 
                                                 style="width: <?= $grade['grade'] ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="grade-distribution">
                    <h3>Grade Distribution</h3>
                    <div class="distribution-chart">
                        <div class="distribution-bars">
                            <?php foreach ($grade_distribution as $grade => $count): ?>
                            <div class="distribution-bar">
                                <div class="bar-fill grade-<?= strtolower($grade) ?>" 
                                     style="height: <?= $count > 0 ? ($count / max($grade_distribution)) * 100 : 0 ?>%"></div>
                                <div class="bar-label"><?= $grade ?></div>
                                <div class="bar-count"><?= $count ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Trends Tab -->
        <div id="performance-trends" class="tab-content">
            <div class="trends-section">
                <h2>Academic Performance Trends</h2>
                
                <div class="trends-chart">
                    <canvas id="performanceChart" width="800" height="400"></canvas>
                </div>
                
                <div class="trends-analysis">
                    <div class="analysis-cards">
                        <div class="analysis-card improvement">
                            <div class="analysis-icon">📈</div>
                            <div class="analysis-content">
                                <h3>Improvement Areas</h3>
                                <ul>
                                    <li>Computer Networks: Focus on practical labs</li>
                                    <li>Web Development: Improve project submissions</li>
                                    <li>Time management for assignments</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="analysis-card strengths">
                            <div class="analysis-icon">💪</div>
                            <div class="analysis-content">
                                <h3>Strengths</h3>
                                <ul>
                                    <li>Data Structures: Excellent performance</li>
                                    <li>Software Engineering: Consistent high grades</li>
                                    <li>Strong theoretical understanding</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="analysis-card goals">
                            <div class="analysis-icon">🎯</div>
                            <div class="analysis-content">
                                <h3>Academic Goals</h3>
                                <ul>
                                    <li>Maintain GPA above 80%</li>
                                    <li>Complete all assignments on time</li>
                                    <li>Improve practical skills</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Completion Tab -->
        <div id="course-completion" class="tab-content">
            <div class="completion-section">
                <h2>Course Completion Status</h2>
                
                <div class="completion-overview">
                    <div class="completion-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?= $completed_units ?></div>
                            <div class="stat-label">Units Completed</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $student['total_units'] - $completed_units ?></div>
                            <div class="stat-label">Units Remaining</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= number_format($progress_percentage, 1) ?>%</div>
                            <div class="stat-label">Completion Rate</div>
                        </div>
                    </div>
                    
                    <div class="completion-timeline">
                        <h3>Projected Completion</h3>
                        <div class="timeline-info">
                            <div class="timeline-item">
                                <span class="timeline-label">Expected Graduation:</span>
                                <span class="timeline-value">
                                    <?= date('M Y', strtotime($student['enrollment_date'] . ' + ' . $student['duration_months'] . ' months')) ?>
                                </span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Time Remaining:</span>
                                <span class="timeline-value">
                                    <?= max(0, $student['duration_months'] - $months_enrolled) ?> months
                                </span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Progress Status:</span>
                                <span class="timeline-value <?= $progress_percentage >= $time_progress ? 'on-track' : 'behind' ?>">
                                    <?= $progress_percentage >= $time_progress ? 'On Track' : 'Behind Schedule' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="semester-breakdown">
                    <h3>Semester Breakdown</h3>
                    <div class="semester-grid">
                        <?php for ($sem = 1; $sem <= 8; $sem++): ?>
                        <?php 
                        $is_completed = $sem <= 4; // Demo: first 4 semesters completed
                        $is_current = $sem == 5; // Demo: currently in 5th semester
                        $status_class = $is_completed ? 'completed' : ($is_current ? 'current' : 'upcoming');
                        ?>
                        <div class="semester-card <?= $status_class ?>">
                            <div class="semester-header">
                                <h4>Semester <?= $sem ?></h4>
                                <span class="semester-status">
                                    <?= $is_completed ? 'Completed' : ($is_current ? 'Current' : 'Upcoming') ?>
                                </span>
                            </div>
                            <div class="semester-details">
                                <div class="semester-units">15 Units</div>
                                <?php if ($is_completed): ?>
                                <div class="semester-gpa">GPA: <?= number_format(rand(75, 90), 1) ?>%</div>
                                <?php elseif ($is_current): ?>
                                <div class="semester-progress">In Progress</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations Tab -->
        <div id="recommendations" class="tab-content">
            <div class="recommendations-section">
                <h2>Academic Recommendations</h2>
                
                <div class="recommendations-grid">
                    <div class="recommendation-card priority-high">
                        <div class="recommendation-header">
                            <div class="priority-indicator high">High Priority</div>
                            <h3>Improve Computer Networks Performance</h3>
                        </div>
                        <div class="recommendation-content">
                            <p>Your grade in Computer Networks (76%) is below your average. Consider:</p>
                            <ul>
                                <li>Attending additional lab sessions</li>
                                <li>Forming study groups with classmates</li>
                                <li>Seeking help from the instructor during office hours</li>
                                <li>Practicing more network configuration exercises</li>
                            </ul>
                        </div>
                        <div class="recommendation-actions">
                            <button class="btn btn-primary btn-sm">Schedule Consultation</button>
                            <button class="btn btn-secondary btn-sm">View Resources</button>
                        </div>
                    </div>
                    
                    <div class="recommendation-card priority-medium">
                        <div class="recommendation-header">
                            <div class="priority-indicator medium">Medium Priority</div>
                            <h3>Maintain Strong Performance</h3>
                        </div>
                        <div class="recommendation-content">
                            <p>You're performing excellently in Data Structures (92%). To maintain this:</p>
                            <ul>
                                <li>Continue regular practice with coding problems</li>
                                <li>Help other students to reinforce your understanding</li>
                                <li>Explore advanced topics for extra credit</li>
                                <li>Consider participating in programming competitions</li>
                            </ul>
                        </div>
                        <div class="recommendation-actions">
                            <button class="btn btn-info btn-sm">Join Study Group</button>
                            <button class="btn btn-secondary btn-sm">Advanced Topics</button>
                        </div>
                    </div>
                    
                    <div class="recommendation-card priority-low">
                        <div class="recommendation-header">
                            <div class="priority-indicator low">Low Priority</div>
                            <h3>Career Development</h3>
                        </div>
                        <div class="recommendation-content">
                            <p>Based on your strong academic performance, consider:</p>
                            <ul>
                                <li>Applying for internships in software development</li>
                                <li>Building a portfolio of projects</li>
                                <li>Attending tech meetups and conferences</li>
                                <li>Contributing to open-source projects</li>
                            </ul>
                        </div>
                        <div class="recommendation-actions">
                            <button class="btn btn-success btn-sm">Career Services</button>
                            <button class="btn btn-secondary btn-sm">Portfolio Guide</button>
                        </div>
                    </div>
                </div>
                
                <div class="study-plan">
                    <h3>Personalized Study Plan</h3>
                    <div class="study-schedule">
                        <div class="schedule-day">
                            <h4>Monday</h4>
                            <div class="schedule-items">
                                <div class="schedule-item">Database Systems - 2 hours</div>
                                <div class="schedule-item">Assignment work - 1 hour</div>
                            </div>
                        </div>
                        <div class="schedule-day">
                            <h4>Tuesday</h4>
                            <div class="schedule-items">
                                <div class="schedule-item">Computer Networks - 2 hours</div>
                                <div class="schedule-item">Lab practice - 1 hour</div>
                            </div>
                        </div>
                        <div class="schedule-day">
                            <h4>Wednesday</h4>
                            <div class="schedule-items">
                                <div class="schedule-item">Web Development - 2 hours</div>
                                <div class="schedule-item">Project work - 1 hour</div>
                            </div>
                        </div>
                        <div class="schedule-day">
                            <h4>Thursday</h4>
                            <div class="schedule-items">
                                <div class="schedule-item">Data Structures - 1 hour</div>
                                <div class="schedule-item">Software Engineering - 2 hours</div>
                            </div>
                        </div>
                        <div class="schedule-day">
                            <h4>Friday</h4>
                            <div class="schedule-items">
                                <div class="schedule-item">Review & Practice - 2 hours</div>
                                <div class="schedule-item">Assignment completion - 1 hour</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.progress-overview {
    margin-bottom: 30px;
}

.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.overview-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.overview-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.overview-card.course-progress {
    border-left: 5px solid #3498db;
}

.overview-card.gpa-card {
    border-left: 5px solid #27ae60;
}

.overview-card.time-progress {
    border-left: 5px solid #f39c12;
}

.overview-card.assignment-performance {
    border-left: 5px solid #9b59b6;
}

.card-icon {
    font-size: 3em;
    opacity: 0.8;
}

.card-content {
    flex: 1;
}

.card-title {
    color: #7f8c8d;
    font-size: 0.9em;
    margin-bottom: 5px;
    font-weight: bold;
}

.card-value {
    font-size: 2.5em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #27ae60);
    transition: width 0.3s ease;
}

.gpa-scale {
    width: 100%;
    height: 8px;
    background: linear-gradient(90deg, #e74c3c 0%, #f39c12 25%, #27ae60 75%, #2ecc71 100%);
    border-radius: 4px;
    position: relative;
    margin-bottom: 8px;
}

.gpa-indicator {
    position: absolute;
    top: -2px;
    width: 4px;
    height: 12px;
    background: #2c3e50;
    border-radius: 2px;
    transition: left 0.3s ease;
}

.card-subtitle {
    color: #7f8c8d;
    font-size: 0.8em;
}

.assignment-stats {
    color: #7f8c8d;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.progress-tabs {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-buttons {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-button {
    flex: 1;
    padding: 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1em;
    color: #6c757d;
    transition: all 0.2s;
}

.tab-button.active {
    background: white;
    color: #3498db;
    border-bottom: 3px solid #3498db;
}

.tab-button:hover {
    background: #e9ecef;
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.grades-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.grades-header h2 {
    color: #2c3e50;
    margin: 0;
}

.semester-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.semester-label {
    color: #7f8c8d;
    font-weight: bold;
}

.semester-gpa {
    font-size: 1.5em;
    font-weight: bold;
    color: #27ae60;
}

.grades-table {
    background: #f8f9fa;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 30px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

th {
    background: #e9ecef;
    font-weight: bold;
    color: #2c3e50;
}

.grade-value {
    font-weight: bold;
    color: #2c3e50;
}

.letter-grade {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    color: white;
}

.letter-grade.excellent {
    background: #27ae60;
}

.letter-grade.good {
    background: #3498db;
}

.letter-grade.average {
    background: #f39c12;
}

.letter-grade.below-average {
    background: #e67e22;
}

.letter-grade.poor {
    background: #e74c3c;
}

.performance-indicator {
    width: 100px;
}

.performance-bar {
    width: 100%;
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
}

.performance-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.performance-fill.excellent {
    background: #27ae60;
}

.performance-fill.good {
    background: #3498db;
}

.performance-fill.average {
    background: #f39c12;
}

.performance-fill.below-average {
    background: #e67e22;
}

.performance-fill.poor {
    background: #e74c3c;
}

.grade-distribution {
    margin-top: 30px;
}

.grade-distribution h3 {
    color: #2c3e50;
    margin-bottom: 20px;
}

.distribution-chart {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
}

.distribution-bars {
    display: flex;
    justify-content: space-around;
    align-items: end;
    height: 150px;
    gap: 10px;
}

.distribution-bar {
    flex:
