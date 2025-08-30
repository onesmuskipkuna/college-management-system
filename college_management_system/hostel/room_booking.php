<?php
/**
 * Room Booking
 * Facilitate the booking and reservation of hostel rooms
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

// Fetch available rooms (pseudo code)
// $available_rooms = fetchAvailableRooms();

?>

<div class="dashboard-container">
    <h1>Room Booking</h1>
    <form method="POST">
        <input type="text" name="student_name" placeholder="Student Name" required>
        <input type="date" name="check_in_date" required>
        <input type="date" name="check_out_date" required>
        <select name="room_type" required>
            <option value="">Select Room Type</option>
            <!-- Populate room types from the database -->
        </select>
        <button type="submit">Book Room</button>
    </form>

    <h2>Available Rooms</h2>
    <ul>
        <?php foreach ($available_rooms as $room): ?>
            <li><?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['room_type']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
