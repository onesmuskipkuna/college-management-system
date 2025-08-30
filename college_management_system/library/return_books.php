<?php
/**
 * Return Books
 * Allow students to return borrowed books to the library
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require student role
Authentication::requireRole('student');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'return_book') {
            $borrowing_id = (int)$_POST['borrowing_id'];

            // Get borrowing record
            $borrowing = fetchOne("SELECT * FROM library_borrowings WHERE id = ? AND user_id = ?", [$borrowing_id, $user['id']]);
            if (!$borrowing) {
                throw new Exception("Borrowing record not found");
            }

            // Update borrowing record
            updateRecord('library_borrowings', [
                'status' => 'returned',
                'returned_date' => date('Y-m-d')
            ], 'id', $borrowing_id);

            // Update available copies
            $book = fetchOne("SELECT * FROM library_books WHERE id = ?", [$borrowing['book_id']]);
            updateRecord('library_books', ['available_copies' => $book['available_copies'] + 1], 'id', $borrowing['book_id']);

            $message = "Book has been successfully returned.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch user's borrowed books
$user_borrowings = fetchAll("
    SELECT lb.*, b.title
    FROM library_borrowings lb
    JOIN library_books b ON lb.book_id = b.id
    WHERE lb.user_id = ? AND lb.status = 'borrowed'
", [$user['id']]);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Return Books</h1>
                <p class="text-muted">Select a book to return to the library.</p>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h5>Your Borrowed Books</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Book Title</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($user_borrowings as $borrowing): ?>
            <tr>
                <td><?= htmlspecialchars($borrowing['title']) ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="return_book">
                        <input type="hidden" name="borrowing_id" value="<?= $borrowing['id'] ?>">
                        <button type="submit" class="btn btn-success btn-sm">Return Book</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
