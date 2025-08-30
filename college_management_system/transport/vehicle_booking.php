<?php
/**
 * Vehicle Booking
 * Facilitate the booking and reservation of vehicles
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

    // Fetch available vehicles from the database
    $available_vehicles = fetchAll("SELECT * FROM vehicles WHERE available = 1");
    
    // Handle form submission for vehicle booking
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate and sanitize input
        $student_name = htmlspecialchars($_POST['student_name']);
        $booking_date = htmlspecialchars($_POST['booking_date']);
        $vehicle_id = htmlspecialchars($_POST['vehicle_id']);
        
        // Insert vehicle booking into the database
        $result = insertRecord('vehicle_bookings', [
            'student_name' => $student_name,
            'booking_date' => $booking_date,
            'vehicle_id' => $vehicle_id
        ]);
        
        if ($result) {
            $message = 'Vehicle booked successfully!';
        } else {
            $message = 'Failed to book vehicle.';
        }
    }

?>

<div class="dashboard-container">
    <h1>Vehicle Booking</h1>
    <form method="POST">
        <input type="text" name="student_name" placeholder="Student Name" required>
        <input type="date" name="booking_date" required>
        <select name="vehicle_id" required>
            <option value="">Select Vehicle</option>
            <!-- Populate vehicle options from the database -->
        </select>
        <button type="submit">Book Vehicle</button>
    </form>

    <h2>Available Vehicles</h2>
    <ul>
        <?php foreach ($available_vehicles as $vehicle): ?>
            <li><?= htmlspecialchars($vehicle['vehicle_number']) ?> - <?= htmlspecialchars($vehicle['vehicle_type']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
