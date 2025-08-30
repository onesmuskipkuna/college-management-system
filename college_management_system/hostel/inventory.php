<?php
/**
 * Inventory Management
 * Track hostel inventory items such as furniture and supplies
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

// Fetch inventory items (pseudo code)
// $inventory_items = fetchInventoryItems();

?>

<div class="dashboard-container">
    <h1>Inventory Management</h1>
    <form method="POST">
        <input type="text" name="item_name" placeholder="Item Name" required>
        <input type="number" name="quantity" placeholder="Quantity" required min="1">
        <input type="text" name="condition" placeholder="Condition" required>
        <button type="submit">Add Item</button>
    </form>

    <h2>Current Inventory</h2>
    <ul>
        <?php foreach ($inventory_items as $item): ?>
            <li><?= htmlspecialchars($item['name']) ?> - Quantity: <?= htmlspecialchars($item['quantity']) ?> - Condition: <?= htmlspecialchars($item['condition']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
