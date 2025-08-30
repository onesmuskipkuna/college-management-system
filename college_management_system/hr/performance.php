<?php
include '../db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $review_date = $_POST['review_date'];
    $comments = $_POST['comments'];
    $rating = $_POST['rating'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO performance_reviews (employee_id, review_date, comments, rating) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $employee_id, $review_date, $comments, $rating);

    if ($stmt->execute()) {
        echo "Performance review added successfully.";
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
    <title>Performance Management</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <h1>Performance Management</h1>
    <form method="POST" action="">
        <label for="employee_id">Employee:</label>
        <select id="employee_id" name="employee_id" required>
            <option value="">Select Employee</option>
            <?php while ($row = $employees->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select>
        
        <label for="review_date">Review Date:</label>
        <input type="date" id="review_date" name="review_date" required>
        
        <label for="comments">Comments:</label>
        <textarea id="comments" name="comments" required></textarea>
        
        <label for="rating">Rating:</label>
        <input type="number" id="rating" name="rating" min="1" max="5" required>
        
        <button type="submit">Add Performance Review</button>
    </form>
</body>
</html>
