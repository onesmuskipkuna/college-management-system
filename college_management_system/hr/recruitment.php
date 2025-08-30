<?php
include '../db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $posting_date = $_POST['posting_date'];
    $status = $_POST['status'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO job_postings (title, description, posting_date, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $description, $posting_date, $status);

    if ($stmt->execute()) {
        echo "Job posting added successfully.";
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
    <title>Recruitment Management</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <h1>Recruitment Management</h1>
    <form method="POST" action="">
        <label for="title">Job Title:</label>
        <input type="text" id="title" name="title" required>
        
        <label for="description">Job Description:</label>
        <textarea id="description" name="description" required></textarea>
        
        <label for="posting_date">Posting Date:</label>
        <input type="date" id="posting_date" name="posting_date" required>
        
        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        
        <button type="submit">Add Job Posting</button>
    </form>
</body>
</html>
