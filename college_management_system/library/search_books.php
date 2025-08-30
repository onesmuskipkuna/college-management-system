<?php
/**
 * Search Books
 * Allow users to search for books in the library
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle search form submission
$search_query = $_GET['search'] ?? '';

// Fetch books based on search query
$books = fetchAll("SELECT * FROM library_books WHERE title LIKE ? OR author LIKE ?", ["%$search_query%", "%$search_query%"]);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Search Books</h1>
                <p class="text-muted">Find books by title or author.</p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET">
                <div class="mb-3">
                    <input type="text" class="form-control" name="search" placeholder="Search by title or author..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <h5>Search Results</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Available Copies</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($books)): ?>
            <tr>
                <td colspan="4" class="text-center">No books found.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($books as $book): ?>
            <tr>
                <td><?= htmlspecialchars($book['title']) ?></td>
                <td><?= htmlspecialchars($book['author']) ?></td>
                <td><?= htmlspecialchars($book['available_copies']) ?></td>
                <td><?= htmlspecialchars($book['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
