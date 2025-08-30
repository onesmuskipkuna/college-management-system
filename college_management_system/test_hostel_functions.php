<?php
/**
 * Test Script for Hostel Management Functions
 * This script tests the new functions added for hostel management.
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

// Test Get Room Statistics
$room_stats = getRoomStatistics();
echo "Room Statistics:\n";
print_r($room_stats);

// Test Allocate Room
$student_id = 1; // Example student ID
$room_id = 1; // Example room ID
$allocation_result = allocateRoom($student_id, $room_id);
echo "Room Allocation Result:\n";
print_r($allocation_result);

// Test Update Room Status
$update_result = updateRoomStatus($room_id, 'maintenance');
echo "Update Room Status Result:\n";
print_r($update_result);
?>
