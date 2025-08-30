<?php
include '../db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $salary = $_POST['salary'];
    $working_days = $_POST['working_days'];
    $leave_days = $_POST['leave_days'];
    $deductions = $_POST['deductions'];

    // Calculate net salary
    $net_salary = $salary - ($deductions + ($leave_days * ($salary / $working_days)));

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO payroll (employee_id, salary, working_days, leave_days, deductions, net_salary) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidddd", $employee_id, $salary, $working_days, $leave_days, $deductions, $net_salary);

    if ($stmt->execute()) {
        echo "Payroll record added successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch employees for dropdown
$employees = $conn->query("SELECT id, name FROM employees");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <h1>Payroll Management</h1>
    <form method="POST" action="">
        <label for="employee_id">Employee:</label>
        <select id="employee_id" name="employee_id" required>
            <option value="">Select Employee</option>
            <?php while ($row = $employees->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select>
        
        <label for="salary">Salary:</label>
        <input type="number" id="salary" name="salary" required>
        
        <label for="working_days">Working Days:</label>
        <input type="number" id="working_days" name="working_days" required>
        
        <label for="leave_days">Leave Days:</label>
        <input type="number" id="leave_days" name="leave_days" required>
        
        <label for="deductions">Deductions:</label>
        <input type="number" id="deductions" name="deductions" required>
        
        <button type="submit">Add Payroll Record</button>
    </form>
</body>
</html>
