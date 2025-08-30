<?php
include '../db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $role = $_POST['role'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO employees (name, email, phone, department, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $department, $role);

    if ($stmt->execute()) {
        echo "New employee added successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <h1>Add New Employee</h1>
    <form method="POST" action="">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" required>
        
        <label for="department">Department:</label>
        <input type="text" id="department" name="department" required>
        
        <label for="role">Role:</label>
        <input type="text" id="role" name="role" required>
        
        <button type="submit">Add Employee</button>
    </form>
</body>
</html>
