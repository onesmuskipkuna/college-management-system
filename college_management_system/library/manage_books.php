<?php
/**
 * Manage Books
 * Add, edit, and delete books in the library
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require librarian role
Authentication::requireRole('librarian');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            $book_id = (int)$_POST['book_id'] ?? 0;
            $title = sanitizeInput($_POST['title'] ?? '');
            $author = sanitizeInput($_POST['author'] ?? '');
            $isbn = sanitizeInput($_POST['isbn'] ?? '');
            $category = sanitizeInput($_POST['category'] ?? '');
            $available_copies = (int)$_POST['available_copies'] ?? 0;
            $total_copies = (int)$_POST['total_copies'] ?? 0;
            $status = sanitizeInput($_POST['status'] ?? 'active');

            if (empty($title) || empty($author) || empty($isbn) || $available_copies < 0 || $total_copies < 0) {
                throw new Exception("All fields are required and must be valid.");
            }

            if ($book_id) {
                // Update existing book
                $query = "UPDATE library_books SET title = ?, author = ?, isbn = ?, category = ?, available_copies = ?, total_copies = ?, status = ? WHERE id = ?";
                $params = [$title, $author, $isbn, $category, $available_copies, $total_copies, $status, $book_id];
            } else {
                // Insert new book
                $query = "INSERT INTO library_books (title, author, isbn, category, available_copies, total_copies, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = [$title, $author, $isbn, $category, $available_copies, $total_copies, $status];
            }

            executeQuery($query, $params);
            $message = "Book has been successfully saved.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch existing books
$books = fetchAll("SELECT * FROM library_books ORDER BY title ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Manage Books</h1>
                <p class="text-muted">Add, edit, or remove books in the library.</p>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <h5>Add/Edit Book</h5>
            <form method="POST">
                <input type="hidden" name="book_id" value="<?= htmlspecialchars($book_id ?? '') ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($title ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="author" class="form-label">Author</label>
                    <input type="text" class="form-control" id="author" name="author" value="<?= htmlspecialchars($author ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="isbn" class="form-label">ISBN</label>
                    <input type="text" class="form-control" id="isbn" name="isbn" value="<?= htmlspecialchars($isbn ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <input type="text" class="form-control" id="category" name="category" value="<?= htmlspecialchars($category ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="available_copies" class="form-label">Available Copies</label>
                    <input type="number" class="form-control" id="available_copies" name="available_copies" value="<?= htmlspecialchars($available_copies ?? 0) ?>" min="0" required>
                </div>
                <div class="mb-3">
                    <label for="total_copies" class="form-label">Total Copies</label>
                    <input type="number" class="form-control" id="total_copies" name="total_copies" value="<?= htmlspecialchars($total_copies ?? 0) ?>" min="0" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Book</button>
            </form>
        </div>
    </div>

    <h5>Existing Books</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>ISBN</th>
                <th>Available Copies</th>
                <th>Total Copies</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
            <tr>
                <td><?= htmlspecialchars($book['title']) ?></td>
                <td><?= htmlspecialchars($book['author']) ?></td>
                <td><?= htmlspecialchars($book['isbn']) ?></td>
                <td><?= htmlspecialchars($book['available_copies']) ?></td>
                <td><?= htmlspecialchars($book['total_copies']) ?></td>
                <td><?= htmlspecialchars($book['status']) ?></td>
                <td>
                    <a href="?book_id=<?= $book['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_book.php?id=<?= $book['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
