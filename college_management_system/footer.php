<?php
/**
 * Common Footer File
 * Includes footer content and JavaScript files
 */

// Prevent direct access
if (!defined('CMS_ACCESS')) {
    die('Direct access not permitted');
}
?>
        </div> <!-- End container -->
    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white">
        <div class="container mx-auto p-4">
            <div class="footer-content flex justify-between">
                <div class="footer-info">
                    <div class="footer-logo">
                        <strong>College Management System</strong>
                    </div>
                    <div class="footer-description">
                        Comprehensive educational institution management solution
                    </div>
                </div>
                
                <div class="footer-links">
                    <ul class="footer-links">
                        <li><a href="/college_management_system/about.php">About</a></li>
                        <li><a href="/college_management_system/contact.php">Contact</a></li>
                        <li><a href="/college_management_system/help.php">Help</a></li>
                        <li><a href="/college_management_system/privacy.php">Privacy Policy</a></li>
                        <li><a href="/college_management_system/terms.php">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <div class="contact-info">
                        <div>Email: <?php echo ADMIN_EMAIL; ?></div>
                        <div>Academic Year: <?php echo getCurrentAcademicYear(); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> College Management System. All rights reserved.</p>
                </div>
                <div class="system-info">
                    <?php if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE): ?>
                        <small class="text-muted">
                            Development Mode | 
                            PHP <?php echo PHP_VERSION; ?> | 
                            Load Time: <?php echo number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2); ?>ms
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript Files -->
    <script src="/college_management_system/js/validations.js"></script>
    
    <!-- Additional JavaScript for specific pages -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($inline_js)): ?>
        <script>
            <?php echo $inline_js; ?>
        </script>
    <?php endif; ?>
    
    <!-- System Status Check -->
    <script>
        // Check system status periodically
        function checkSystemStatus() {
            fetch('/college_management_system/api/system_status.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.warn('System status check failed:', data.message);
                    }
                })
                .catch(error => {
                    console.error('System status check error:', error);
                });
        }
        
        // Check status every 5 minutes
        setInterval(checkSystemStatus, 300000);
        
        // Session timeout warning
        <?php if (isset($_SESSION['user_id'])): ?>
            let sessionTimeout = <?php echo SESSION_TIMEOUT; ?> * 1000; // Convert to milliseconds
            let warningTime = sessionTimeout - (5 * 60 * 1000); // 5 minutes before timeout
            
            setTimeout(function() {
                if (confirm('Your session will expire in 5 minutes. Do you want to extend it?')) {
                    // Make a request to extend session
                    fetch('/college_management_system/api/extend_session.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reset the timeout
                            location.reload();
                        }
                    });
                }
            }, warningTime);
        <?php endif; ?>
        
        // Auto-save form data (for long forms)
        function setupAutoSave() {
            const forms = document.querySelectorAll('form[data-autosave]');
            forms.forEach(form => {
                const formId = form.getAttribute('id') || 'form_' + Math.random().toString(36).substr(2, 9);
                
                // Load saved data
                const savedData = localStorage.getItem('autosave_' + formId);
                if (savedData) {
                    try {
                        const data = JSON.parse(savedData);
                        Object.keys(data).forEach(key => {
                            const field = form.querySelector(`[name="${key}"]`);
                            if (field && field.type !== 'password') {
                                field.value = data[key];
                            }
                        });
                    } catch (e) {
                        console.error('Error loading autosaved data:', e);
                    }
                }
                
                // Save data on input
                form.addEventListener('input', function() {
                    const formData = new FormData(form);
                    const data = {};
                    
                    for (let [key, value] of formData.entries()) {
                        if (key !== 'password' && key !== 'confirm_password' && key !== 'csrf_token') {
                            data[key] = value;
                        }
                    }
                    
                    localStorage.setItem('autosave_' + formId, JSON.stringify(data));
                });
                
                // Clear saved data on successful submit
                form.addEventListener('submit', function() {
                    setTimeout(() => {
                        localStorage.removeItem('autosave_' + formId);
                    }, 1000);
                });
            });
        }
        
        // Initialize auto-save
        document.addEventListener('DOMContentLoaded', setupAutoSave);
        
        // Print functionality
        function printPage() {
            window.print();
        }
        
        // Export functionality
        function exportTable(tableId, filename = 'export') {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }
            
            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        // Notification system
        function showNotification(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            
            document.body.appendChild(notification);
            
            if (duration > 0) {
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, duration);
            }
        }
        
        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            
            <?php if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE): ?>
                showNotification('JavaScript Error: ' + e.message, 'danger');
            <?php endif; ?>
        });
        
        // Handle AJAX errors globally
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled Promise Rejection:', e.reason);
            
            <?php if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE): ?>
                showNotification('Network Error: ' + e.reason, 'warning');
            <?php endif; ?>
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S to save form
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const form = document.querySelector('form');
                if (form) {
                    form.submit();
                }
            }
            
            // Ctrl+P to print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printPage();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal[style*="display: block"]');
                modals.forEach(modal => {
                    hideModal(modal);
                });
            }
        });
        
        // Mobile menu toggle
        function toggleMobileMenu() {
            const navMenu = document.querySelector('.nav-menu');
            if (navMenu) {
                navMenu.classList.toggle('mobile-open');
            }
        }
        
        // Responsive table handling
        function makeTablesResponsive() {
            const tables = document.querySelectorAll('table:not(.table-responsive table)');
            tables.forEach(table => {
                if (!table.parentElement.classList.contains('table-responsive')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-responsive';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });
        }
        
        // Initialize responsive tables
        document.addEventListener('DOMContentLoaded', makeTablesResponsive);
        
        // Lazy loading for images
        function setupLazyLoading() {
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        }
        
        // Initialize lazy loading if supported
        if ('IntersectionObserver' in window) {
            document.addEventListener('DOMContentLoaded', setupLazyLoading);
        }
        
        // Service Worker registration (for offline functionality)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/college_management_system/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>
    
    <!-- Analytics (if enabled) -->
    <?php if (defined('GOOGLE_ANALYTICS_ID') && GOOGLE_ANALYTICS_ID): ?>
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GOOGLE_ANALYTICS_ID; ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo GOOGLE_ANALYTICS_ID; ?>');
        </script>
    <?php endif; ?>
</body>
</html>
