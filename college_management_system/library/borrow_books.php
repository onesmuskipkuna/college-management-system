<?php
/**
 * Borrow Books
 * Allow students to borrow books from the library
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
        if (isset($_POST['action']) && $_POST['action'] === 'borrow_book') {
            $book_id = (int)$_POST['book_id'];
            $user_id = $user['id'];

            // Check if book is available
            $book = fetchOne("SELECT * FROM library_books WHERE id = ? AND available_copies > 0", [$book_id]);
            if (!$book) {
                throw new Exception("Book is not available for borrowing");
            }

            // Check if user already has this book
            $existing = fetchOne("SELECT * FROM library_borrowings WHERE book_id = ? AND user_id = ? AND status = 'borrowed'", [$book_id, $user_id]);
            if ($existing) {
                throw new Exception("You have already borrowed this book");
            }

            // Create borrowing record
            $borrowing_data = [
                'book_id' => $book_id,
                'user_id' => $user_id,
                'borrowed_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+14 days')),
                'status' => 'borrowed'
            ];

            insertRecord('library_borrowings', $borrowing_data);

            // Update available copies
            updateRecord('library_books', ['available_copies' => $book['available_copies'] - 1], 'id', $book_id);

            $message = "Book '{$book['title']}' has been successfully borrowed. Due date: " . date('M j, Y', strtotime('+14 days'));
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch available books for borrowing
$available_books = fetchAll("SELECT * FROM library_books WHERE available_copies > 0 ORDER BY title ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Borrow Books</h1>
                <p class="text-muted">Select a book to borrow from the library.</p>
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
            <h5>Select Book to Borrow</h5>
            <form method="POST">
                <div class="mb-3">
                    <label for="book_id" class="form-label">Book</label>
                    <select class="form-select" id="book_id" name="book_id" required>
                        <option value="">Select a book</option>
                        <?php foreach ($available_books as $book): ?>
                        <option value="<?= $book['id'] ?>"><?= htmlspecialchars($book['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" name="action" value="borrow_book">Borrow Book</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
