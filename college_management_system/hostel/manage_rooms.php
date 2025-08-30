<?php
/**
 * Manage Hostel Rooms
 * Add, edit, and delete hostel room records
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require hostel role
Authentication::requireRole('hostel');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            $room_id = (int)$_POST['room_id'] ?? 0;
            $room_number = sanitizeInput($_POST['room_number'] ?? '');
            $capacity = (int)$_POST['capacity'] ?? 0;
            $status = sanitizeInput($_POST['status'] ?? 'available');

            if (empty($room_number) || $capacity <= 0) {
                throw new Exception("All fields are required and must be valid.");
            }

            if ($room_id) {
                // Update existing room
                $query = "UPDATE hostel_rooms SET room_number = ?, capacity = ?, status = ? WHERE id = ?";
                $params = [$room_number, $capacity, $status, $room_id];
            } else {
                // Insert new room
                $query = "INSERT INTO hostel_rooms (room_number, capacity, status) VALUES (?, ?, ?)";
                $params = [$room_number, $capacity, $status];
            }

            executeQuery($query, $params);
            $message = "Room has been successfully saved.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch existing rooms
$rooms = fetchAll("SELECT * FROM hostel_rooms ORDER BY room_number ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Manage Hostel Rooms</h1>
                <p class="text-muted">Add, edit, or remove hostel room records.</p>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row mb-4" style="margin-bottom: 20px;">
        <div class="col-md-6">
            <h5>Add/Edit Room</h5>
            <form method="POST">
                <input type="hidden" name="room_id" value="<?= htmlspecialchars($room_id ?? '') ?>">
                <div class="mb-3">
                    <label for="room_number" class="form-label">Room Number</label>
                    <input type="text" class="form-control" id="room_number" name="room_number" value="<?= htmlspecialchars($room_number ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" value="<?= htmlspecialchars($capacity ?? 0) ?>" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="available" <?= ($status ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="occupied" <?= ($status ?? '') === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                        <option value="maintenance" <?= ($status ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Room</button>
            </form>
        </div>
    </div>

    <h5>Existing Rooms</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Room Number</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?= htmlspecialchars($room['room_number']) ?></td>
                <td><?= htmlspecialchars($room['capacity']) ?></td>
                <td><?= htmlspecialchars($room['status']) ?></td>
                <td>
                    <a href="?room_id=<?= $room['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_room.php?id=<?= $room['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
