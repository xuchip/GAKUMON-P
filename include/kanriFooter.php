<?php
// This file contains the footer for the admin dashboard.
?>
    </div>

    <?php if(isset($pageJS)): ?>
      <script src="<?= htmlspecialchars($pageJS) ?>"></script>
    <?php endif; ?>
        <?php if(isset($pageJS2)): ?>
      <script src="<?= htmlspecialchars($pageJS) ?>"></script>
    <?php endif; ?>
        <?php if(isset($pageJS3)): ?>
      <script src="<?= htmlspecialchars($pageJS) ?>"></script>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
<footer class="admin-footer">
    <div class="container-fluid">
        <div class="footer-content">
            <div class="footer-section">
                <h4>GAKUMON Admin</h4>
                <p>Comprehensive learning platform management system</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#dashboard">Dashboard</a></li>
                    <li><a href="#user-management">User Management</a></li>
                    <li><a href="#lesson-management">Lesson Management</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>System</h4>
                <ul>
                    <li><a href="#system-management">System Settings</a></li>
                    <li><a href="#audit-logs">Audit Logs</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">Help Documentation</a></li>
                    <li><a href="#">Contact Admin</a></li>
                    <li><a href="#">System Status</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> GAKUMON. All rights reserved. | Admin Dashboard v1.0</p>
            <div class="system-info">
                <span>PHP: <?php echo phpversion(); ?> | MySQL: <?php echo $connection->server_info; ?></span>
            </div>
        </div>
    </div>
</footer>

<style>
.admin-footer {
    background: #811212;
    color: white;
    padding: 2rem 0 1rem;
    margin-left: 500px;
    font-family: 'SFpro_regular', sans-serif;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    padding: 0 1.5rem;
    margin-bottom: 2rem;
}

.footer-section h4 {
    font-family: 'SFpro_bold', sans-serif;
    margin-bottom: 1rem;
    color: white;
    font-size: 1.1rem;
}

.footer-section p {
    line-height: 1.6;
    opacity: 0.9;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 0.5rem;
}

.footer-section ul li a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-section ul li a:hover {
    color: white;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding: 1rem 1.5rem 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-bottom p {
    margin: 0;
    opacity: 0.9;
}

.system-info {
    font-size: 0.8rem;
    opacity: 0.7;
}

@media (max-width: 768px) {
    .admin-footer {
        margin-left: 0;
    }
    
    .footer-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
}
</style>