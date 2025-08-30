<?php
include '../db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO training_programs (title, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $description, $start_date, $end_date, $status);

    if ($stmt->execute()) {
        echo "Training program added successfully.";
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
    <title>Training Management</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <h1>Training Management</h1>
    <form method="POST" action="">
        <label for="title">Training Title:</label>
        <input type="text" id="title" name="title" required>
        
        <label for="description">Description:</label>
        <textarea id="description" name="description" required></textarea>
        
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" required>
        
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" required>
        
        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        
        <button type="submit">Add Training Program</button>
    </form>
</body>
</html>
