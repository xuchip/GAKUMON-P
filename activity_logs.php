<?php
session_start();
require_once 'config/config.php';

// Session and role validation
if (!isset($_SESSION['sUser']) || $_SESSION['sRole'] !== 'Kanri') {
    header("Location: login.php");
    exit;
}

$pageTitle = 'GAKUMON — Activity Logs';
$pageCSS = 'CSS/desktop/activity_logsStyle.css';
$pageJS = 'JS/desktop/activity_logsScript.js'; 
include 'include/header.php';
include 'include/desktopKanriNav.php';

// Fetch logs from database
$query = "
    SELECT l.log_id, l.action, l.target_type, l.target_id, l.created_at,
           u.username, u.first_name, u.last_name
    FROM tbl_admin_audit_logs l
    JOIN tbl_user u ON l.user_id = u.user_id
    ORDER BY l.created_at DESC
";
$result = mysqli_query($connection, $query);
?>

<div class="main-layout">
    <!-- Main Content Area -->
    <div class="content-area">
        <div class="page-content">
            <div class="account-card">
                <div class="card-header">
                    <h2>Administrator Activity Logs</h2>
                </div>

                <div class="table-responsive">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Target Type</th>
                                <th>Target ID</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td data-label="ID"><?= htmlspecialchars($row['log_id']); ?></td>
                                        <td data-label="Admin"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td data-label="Action"><?= htmlspecialchars($row['action']); ?></td>
                                        <td data-label="Target Type">
                                            <span class="status-badge 
                                                <?= $row['target_type'] === 'user' ? 'status-approved' : 
                                                    ($row['target_type'] === 'item' ? 'status-pending' :
                                                    ($row['target_type'] === 'lesson' ? 'status-public' : 'status-private')) ?>">
                                                <?= ucfirst($row['target_type']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Target ID"><?= htmlspecialchars($row['target_id']); ?></td>
                                        <td data-label="Timestamp"><?= htmlspecialchars($row['created_at']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:1rem; color:#fff;">
                                        No activities found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>
