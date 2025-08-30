<?php
/**
 * Inventory Management
 * Track transport-related inventory items such as fuel and spare parts
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

    // Fetch inventory items from the database
    $inventory_items = fetchAll("SELECT * FROM inventory");
    
    // Handle form submission for new inventory items
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate and sanitize input
        $item_name = htmlspecialchars($_POST['item_name']);
        $quantity = htmlspecialchars($_POST['quantity']);
        $condition = htmlspecialchars($_POST['condition']);
        
        // Insert inventory item into the database
        $result = insertRecord('inventory', [
            'name' => $item_name,
            'quantity' => $quantity,
            'condition' => $condition
        ]);
        
        if ($result) {
            $message = 'Inventory item added successfully!';
        } else {
            $message = 'Failed to add inventory item.';
        }
    }

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
