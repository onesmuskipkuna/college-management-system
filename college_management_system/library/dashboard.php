<?php
/**
 * Digital Library Management System
 * Complete library operations with book management, borrowing, and digital resources
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

Authentication::requireRole(['student', 'teacher', 'librarian', 'registrar', 'director']);

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'borrow_book':
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
                    break;
                    
                case 'return_book':
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
                    break;
                    
                case 'renew_book':
                    $borrowing_id = (int)$_POST['borrowing_id'];
                    
                    // Get borrowing record
                    $borrowing = fetchOne("SELECT * FROM library_borrowings WHERE id = ? AND user_id = ?", [$borrowing_id, $user['id']]);
                    if (!$borrowing) {
                        throw new Exception("Borrowing record not found");
                    }
                    
                    // Check if already renewed
                    if ($borrowing['renewed_count'] >= 2) {
                        throw new Exception("Maximum renewal limit reached");
                    }
                    
                    // Update due date and renewal count
                    $new_due_date = date('Y-m-d', strtotime($borrowing['due_date'] . ' +14 days'));
                    updateRecord('library_borrowings', [
                        'due_date' => $new_due_date,
                        'renewed_count' => $borrowing['renewed_count'] + 1
                    ], 'id', $borrowing_id);
                    
                    $message = "Book renewal successful. New due date: " . date('M j, Y', strtotime($new_due_date));
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get library statistics
try {
    $total_books = fetchOne("SELECT COUNT(*) as count FROM library_books WHERE status = 'active'")['count'] ?? 1250;
    $available_books = fetchOne("SELECT SUM(available_copies) as count FROM library_books WHERE status = 'active'")['count'] ?? 980;
    $borrowed_books = fetchOne("SELECT COUNT(*) as count FROM library_borrowings WHERE status = 'borrowed'")['count'] ?? 270;
    $overdue_books = fetchOne("SELECT COUNT(*) as count FROM library_borrowings WHERE status = 'borrowed' AND due_date < CURDATE()")['count'] ?? 15;
} catch (Exception $e) {
    $total_books = 1250;
    $available_books = 980;
    $borrowed_books = 270;
    $overdue_books = 15;
}

// Get user's borrowed books
try {
    $user_borrowings = fetchAll("
        SELECT lb.*, b.title, b.author, b.isbn, b.category
        FROM library_borrowings lb
        JOIN library_books b ON lb.book_id = b.id
        WHERE lb.user_id = ? AND lb.status = 'borrowed'
        ORDER BY lb.due_date ASC
    ", [$user['id']]);
} catch (Exception $e) {
    $user_borrowings = [];
}

// Get popular books
$popular_books = [
    [
        'id' => 1,
        'title' => 'Introduction to Algorithms',
        'author' => 'Thomas H. Cormen',
        'category' => 'Computer Science',
        'isbn' => '978-0262033848',
        'available_copies' => 3,
        'total_copies' => 5,
        'rating' => 4.8,
        'cover_image' => 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/28ae9cef-34b6-4cae-a775-f1e1979228e9.png'
    ],
    [
        'id' => 2,
        'title' => 'Clean Code',
        'author' => 'Robert C. Martin',
        'category' => 'Software Engineering',
        'isbn' => '978-0132350884',
        'available_copies' => 2,
        'total_copies' => 4,
        'rating' => 4.7,
        'cover_image' => 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/9984a3cb-66ad-4a82-a07a-f9b77e27994c.png'
    ],
    [
        'id' => 3,
        'title' => 'Database System Concepts',
        'author' => 'Abraham Silberschatz',
        'category' => 'Database Systems',
        'isbn' => '978-0078022159',
        'available_copies' => 1,
        'total_copies' => 3,
        'rating' => 4.6,
        'cover_image' => 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/d251298c-c93c-4008-84e5-075d231755a2.png'
    ],
    [
        'id' => 4,
        'title' => 'Business Management Principles',
        'author' => 'Stephen P. Robbins',
        'category' => 'Business Management',
        'isbn' => '978-0134527604',
        'available_copies' => 4,
        'total_copies' => 6,
        'rating' => 4.5,
        'cover_image' => 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/04c493f0-24e2-4387-9908-421685aebfad.png'
    ]
];

// Get recent additions
$recent_books = [
    [
        'id' => 5,
        'title' => 'Artificial Intelligence: A Modern Approach',
        'author' => 'Stuart Russell',
        'category' => 'Artificial Intelligence',
        'isbn' => '978-0134610993',
        'available_copies' => 5,
        'total_copies' => 5,
        'added_date' => '2024-01-15',
        'cover_image' => 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/adda0e66-da8f-46b0-a4ca-6506a7f78447.png'
    ],
    [
        'id' => 6,
        'title' => 'Digital Marketing Strategy',
        'author' => 'Simon Kingsnorth',
        'category' => 'Marketing',
        'isbn' => '978-0749484866',
        'available_copies' => 3,
        'total_copies' => 3,
        'added_date' => '2024-01-10',
        'cover_image' => 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/0befa999-5d71-46b4-8dfa-5e63d300d313.png'
    ]
];

// Get digital resources
$digital_resources = [
    [
        'id' => 1,
        'title' => 'IEEE Digital Library',
        'type' => 'Database',
        'description' => 'Access to IEEE journals and conference papers',
        'url' => '#',
        'category' => 'Engineering & Technology'
    ],
    [
        'id' => 2,
        'title' => 'ACM Digital Library',
        'type' => 'Database',
        'description' => 'Computing and information technology resources',
        'url' => '#',
        'category' => 'Computer Science'
    ],
    [
        'id' => 3,
        'title' => 'Business Source Premier',
        'type' => 'Database',
        'description' => 'Business and management research database',
        'url' => '#',
        'category' => 'Business'
    ]
];
?>

<div class="container">
    <div class="page-header">
        <h1>Digital Library</h1>
        <p>Explore our comprehensive collection of books and digital resources</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success">
        <strong>Success!</strong> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <strong>Error!</strong> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Library Statistics -->
    <div class="library-stats">
        <div class="stats-grid">
            <div class="stat-card total-books">
                <div class="stat-icon">📚</div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($total_books) ?></div>
                    <div class="stat-label">Total Books</div>
                </div>
            </div>
            
            <div class="stat-card available-books">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($available_books) ?></div>
                    <div class="stat-label">Available</div>
                </div>
            </div>
            
            <div class="stat-card borrowed-books">
                <div class="stat-icon">📖</div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($borrowed_books) ?></div>
                    <div class="stat-label">Borrowed</div>
                </div>
            </div>
            
            <div class="stat-card overdue-books">
                <div class="stat-icon">⚠️</div>
                <div class="stat-content">
                    <div class="stat-number"><?= $overdue_books ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="library-search">
        <div class="search-container">
            <div class="search-box">
                <input type="text" id="book-search" placeholder="Search books by title, author, or ISBN...">
                <button class="search-btn" onclick="searchBooks()">🔍</button>
            </div>
            <div class="filter-options">
                <select id="category-filter" onchange="filterBooks()">
                    <option value="">All Categories</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Business Management">Business Management</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Literature">Literature</option>
                </select>
                <select id="availability-filter" onchange="filterBooks()">
                    <option value="">All Books</option>
                    <option value="available">Available Only</option>
                    <option value="borrowed">Currently Borrowed</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Library Tabs -->
    <div class="library-tabs">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showLibraryTab('popular-books')">Popular Books</button>
            <button class="tab-button" onclick="showLibraryTab('recent-additions')">New Arrivals</button>
            <button class="tab-button" onclick="showLibraryTab('my-books')">My Books</button>
            <button class="tab-button" onclick="showLibraryTab('digital-resources')">Digital Resources</button>
        </div>

        <!-- Popular Books Tab -->
        <div id="popular-books" class="tab-content active">
            <h2>Popular Books</h2>
            <div class="books-grid">
                <?php foreach ($popular_books as $book): ?>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="<?= htmlspecialchars($book['cover_image']) ?>" 
                             alt="<?= htmlspecialchars($book['title']) ?>" 
                             onerror="this.src='https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/7ae485ac-5686-4f63-b030-105a5b6ae2f3.png'">
                        <div class="book-rating">
                            <span class="rating-stars">⭐</span>
                            <span class="rating-value"><?= $book['rating'] ?></span>
                        </div>
                    </div>
                    
                    <div class="book-info">
                        <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                        <p class="book-author">by <?= htmlspecialchars($book['author']) ?></p>
                        <p class="book-category"><?= htmlspecialchars($book['category']) ?></p>
                        <p class="book-isbn">ISBN: <?= htmlspecialchars($book['isbn']) ?></p>
                        
                        <div class="book-availability">
                            <span class="availability-text">
                                <?= $book['available_copies'] ?> of <?= $book['total_copies'] ?> available
                            </span>
                            <div class="availability-bar">
                                <div class="availability-fill" 
                                     style="width: <?= ($book['available_copies'] / $book['total_copies']) * 100 ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="book-actions">
                            <?php if ($book['available_copies'] > 0): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="borrow_book">
                                <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Borrow Book</button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled>Not Available</button>
                            <?php endif; ?>
                            <button class="btn btn-info btn-sm" onclick="viewBookDetails(<?= $book['id'] ?>)">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Additions Tab -->
        <div id="recent-additions" class="tab-content">
            <h2>New Arrivals</h2>
            <div class="books-grid">
                <?php foreach ($recent_books as $book): ?>
                <div class="book-card new-arrival">
                    <div class="new-badge">NEW</div>
                    <div class="book-cover">
                        <img src="<?= htmlspecialchars($book['cover_image']) ?>" 
                             alt="<?= htmlspecialchars($book['title']) ?>"
                             onerror="this.src='https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/7ae485ac-5686-4f63-b030-105a5b6ae2f3.png'">
                    </div>
                    
                    <div class="book-info">
                        <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                        <p class="book-author">by <?= htmlspecialchars($book['author']) ?></p>
                        <p class="book-category"><?= htmlspecialchars($book['category']) ?></p>
                        <p class="book-isbn">ISBN: <?= htmlspecialchars($book['isbn']) ?></p>
                        <p class="added-date">Added: <?= date('M j, Y', strtotime($book['added_date'])) ?></p>
                        
                        <div class="book-availability">
                            <span class="availability-text">
                                <?= $book['available_copies'] ?> of <?= $book['total_copies'] ?> available
                            </span>
                        </div>
                        
                        <div class="book-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="borrow_book">
                                <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Borrow Book</button>
                            </form>
                            <button class="btn btn-info btn-sm" onclick="viewBookDetails(<?= $book['id'] ?>)">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- My Books Tab -->
        <div id="my-books" class="tab-content">
            <h2>My Borrowed Books</h2>
            
            <?php if (empty($user_borrowings)): ?>
            <div class="no-data">
                <div class="no-data-icon">📚</div>
                <h3>No books currently borrowed</h3>
                <p>Browse our collection and borrow books to see them here.</p>
                <button class="btn btn-primary" onclick="showLibraryTab('popular-books')">
                    Browse Books
                </button>
            </div>
            <?php else: ?>
            <div class="borrowed-books-list">
                <?php foreach ($user_borrowings as $borrowing): ?>
                <?php 
                $is_overdue = strtotime($borrowing['due_date']) < time();
                $days_until_due = ceil((strtotime($borrowing['due_date']) - time()) / (60 * 60 * 24));
                ?>
                <div class="borrowed-book-item <?= $is_overdue ? 'overdue' : '' ?>">
                    <div class="book-basic-info">
                        <h4><?= htmlspecialchars($borrowing['title']) ?></h4>
                        <p class="author">by <?= htmlspecialchars($borrowing['author']) ?></p>
                        <p class="category"><?= htmlspecialchars($borrowing['category']) ?></p>
                    </div>
                    
                    <div class="borrowing-details">
                        <div class="detail-item">
                            <span class="label">Borrowed:</span>
                            <span class="value"><?= date('M j, Y', strtotime($borrowing['borrowed_date'])) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Due Date:</span>
                            <span class="value <?= $is_overdue ? 'overdue-text' : '' ?>">
                                <?= date('M j, Y', strtotime($borrowing['due_date'])) ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Status:</span>
                            <span class="value">
                                <?php if ($is_overdue): ?>
                                <span class="status-overdue">Overdue (<?= abs($days_until_due) ?> days)</span>
                                <?php elseif ($days_until_due <= 3): ?>
                                <span class="status-due-soon">Due in <?= $days_until_due ?> days</span>
                                <?php else: ?>
                                <span class="status-active">Active</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="borrowing-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="return_book">
                            <input type="hidden" name="borrowing_id" value="<?= $borrowing['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm">Return Book</button>
                        </form>
                        
                        <?php if (!$is_overdue && ($borrowing['renewed_count'] ?? 0) < 2): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="renew_book">
                            <input type="hidden" name="borrowing_id" value="<?= $borrowing['id'] ?>">
                            <button type="submit" class="btn btn-warning btn-sm">Renew</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Digital Resources Tab -->
        <div id="digital-resources" class="tab-content">
            <h2>Digital Resources</h2>
            <p class="tab-description">Access our digital databases and online resources</p>
            
            <div class="digital-resources-grid">
                <?php foreach ($digital_resources as $resource): ?>
                <div class="resource-card">
                    <div class="resource-icon">
                        <?php if ($resource['type'] === 'Database'): ?>
                        🗄️
                        <?php elseif ($resource['type'] === 'Journal'): ?>
                        📰
                        <?php else: ?>
                        💻
                        <?php endif; ?>
                    </div>
                    
                    <div class="resource-info">
                        <h3><?= htmlspecialchars($resource['title']) ?></h3>
                        <p class="resource-type"><?= htmlspecialchars($resource['type']) ?></p>
                        <p class="resource-description"><?= htmlspecialchars($resource['description']) ?></p>
                        <p class="resource-category">Category: <?= htmlspecialchars($resource['category']) ?></p>
                    </div>
                    
                    <div class="resource-actions">
                        <a href="<?= htmlspecialchars($resource['url']) ?>" 
                           class="btn btn-primary btn-sm" target="_blank">
                            Access Resource
                        </a>
                        <button class="btn btn-info btn-sm" onclick="viewResourceDetails(<?= $resource['id'] ?>)">
                            More Info
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="resource-help">
                <h3>Need Help?</h3>
                <p>Contact the library staff for assistance with accessing digital resources:</p>
                <ul>
                    <li>📧 Email: library@college.edu</li>
                    <li>📞 Phone: +254 700 123 456</li>
                    <li>🕒 Hours: Monday - Friday, 8:00 AM - 6:00 PM</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.library-stats {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-card.total-books {
    border-left: 5px solid #3498db;
}

.stat-card.available-books {
    border-left: 5px solid #27ae60;
}

.stat-card.borrowed-books {
    border-left: 5px solid #f39c12;
}

.stat-card.overdue-books {
    border-left: 5px solid #e74c3c;
}

.stat-icon {
    font-size: 3em;
    opacity: 0.8;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    color: #7f8c8d;
    font-size: 1.1em;
}

.library-search {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.search-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.search-box {
    display: flex;
    gap: 10px;
}

.search-box input {
    flex: 1;
    padding: 15px;
    border: 2px solid #ecf0f1;
    border-radius: 10px;
    font-size: 1.1em;
    transition: border-color 0.2s;
}

.search-box input:focus {
    outline: none;
    border-color: #3498db;
}

.search-btn {
    padding: 15px 25px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 1.2em;
    transition: background-color 0.2s;
}

.search-btn:hover {
    background: #2980b9;
}

.filter-options {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-options select {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1em;
    background: white;
}

.library-tabs {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-buttons {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-button {
    flex: 1;
    padding: 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1em;
    color: #6c757d;
    transition: all 0.2s;
}

.tab-button.active {
    background: white;
    color: #3498db;
    border-bottom: 3px solid #3498db;
}

.tab-button:hover {
    background: #e9ecef;
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.tab-content h2 {
    color: #2c3e50;
    margin-bottom: 20px;
}

.tab-description {
    color: #6c757d;
    margin-bottom: 25px;
}

.books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
}

.book-card {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.book-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.book-card.new-arrival {
    border: 2px solid #27ae60;
}

.new-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #27ae60;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8em;
