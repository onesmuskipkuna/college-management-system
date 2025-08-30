<?php
/**
 * Test Script for Hostel Room Allocation
 * This script simulates the allocation of a room and meal plan to a student.
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/authentication.php';
require_once __DIR__ . '/includes/functions.php';

// Simulated input data
$student_id = 'STU001'; // Example student ID
$selected_room = 'A-101'; // Example room number
$selected_meal_plan = 1; // Example meal plan ID

// Attempt to allocate room and meal plan
if (allocateRoomToStudent($student_id, $selected_room, $selected_meal_plan)) {
    echo "Room and meal plan allocated successfully!";
} else {
    echo "Failed to allocate room and meal plan.";
}
?>
