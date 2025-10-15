<?php
session_start();

$pageTitle = 'GAKUMON — Admin Dashboard';
$pageCSS = 'CSS/desktop/kanriDBStyle.css';
$pageJS = 'JS/desktop/adminManagementScript.js';
$pageJS2 = 'JS/desktop/kanriDashboard.js';
$kanriCSS = 'CSS/desktop/kanriStyle.css';
$kanriCSS3 = 'CSS/desktop/kanriDashboardStyle.css';
$kanriCSS2 = 'CSS/desktop/adminManagementStyle.css';
include 'include/header.php';
require_once 'config/config.php';

// Session and role validation
if (!isset($_SESSION['sUser']) || $_SESSION['sRole'] !== 'Kanri') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['sUser'];

// Get UserID from database
$stmt = $connection->prepare("SELECT user_id, email_address FROM tbl_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $userID = $row['user_id'];
    $userEmail = $row['email_address'];
} else {
    echo "User not found.";
    exit;
}

// Get statistics data
$stats = [];
$recentActivity = [];

// Total Users
$result = $connection->query("
    SELECT COUNT(*) as total_users, 
           SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_30d 
    FROM tbl_user
");
$stats['users'] = $result->fetch_assoc();

// Total Lessons
$result = $connection->query("
    SELECT COUNT(*) as total_lessons, 
           SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_lessons_30d 
    FROM tbl_lesson
");
$stats['lessons'] = $result->fetch_assoc();

// Total Quizzes
$result = $connection->query("SELECT COUNT(*) as total_quizzes FROM tbl_quizzes");
$stats['quizzes'] = $result->fetch_assoc();

// Total Gakucoins
$result = $connection->query("SELECT SUM(gakucoins) as total_coins FROM tbl_user");
$stats['coins'] = $result->fetch_assoc();

// Recent Activity
$result = $connection->query("
    SELECT log_id, user_id, action, target_type, target_id, created_at 
    FROM tbl_admin_audit_logs 
    ORDER BY created_at DESC 
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $recentActivity[] = $row;
}

// Top Lessons by Enrollment
$result = $connection->query("
    SELECT l.lesson_id, l.title, COUNT(e.user_id) as enrollment_count 
    FROM tbl_lesson l 
    LEFT JOIN tbl_user_enrollments e ON l.lesson_id = e.lesson_id 
    GROUP BY l.lesson_id, l.title 
    ORDER BY enrollment_count DESC 
    LIMIT 10
");
$stats['top_lessons'] = $result->fetch_all(MYSQLI_ASSOC);

// User Growth Data (last 30 days)
$result = $connection->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM tbl_user 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY date
");
$stats['user_growth'] = $result->fetch_all(MYSQLI_ASSOC);

include 'include/kanriNav.php';
?>

<!-- Mobile Warning Modal -->
<div class="mobile-warning-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🚫 Mobile Device Detected</h3>
        </div>
        <div class="modal-body">
            <p>This admin dashboard is optimized for desktop viewing. Please switch to a desktop device for the best experience.</p>
        </div>
        <div class="modal-footer">
            <button onclick="redirectToIndex()" class="btn-understand">OK, I understand</button>
        </div>
    </div>
</div>

<!-- Main layout -->
<div class="main-layout">
    <!-- Content area -->
    <div class="content-area">
        <div class="container-fluid page-content">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($username); ?>! Here's an overview of your platform.</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['users']['total_users']; ?></h3>
                        <p>Total Users</p>
                        <small>+<?php echo $stats['users']['new_users_30d']; ?> this month</small>
                    </div>
                    <a href="#user-management" class="stat-action">Manage</a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon lessons">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['lessons']['total_lessons']; ?></h3>
                        <p>Total Lessons</p>
                        <small>+<?php echo $stats['lessons']['new_lessons_30d']; ?> this month</small>
                    </div>
                    <a href="#lesson-management" class="stat-action">Manage</a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon quizzes">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['quizzes']['total_quizzes']; ?></h3>
                        <p>Total Quizzes</p>
                        <small>Active assessments</small>
                    </div>
                    <a href="#quiz-management" class="stat-action">Manage</a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['coins']['total_coins'] ?? 0; ?></h3>
                        <p>Total Gakucoins</p>
                        <small>In circulation</small>
                    </div>
                    <a href="#shop-management" class="stat-action">Manage</a>
                </div>
            </div>

            <!-- Charts and Analytics Section -->
            <div class="analytics-section">
                <div class="section-header">
                    <h2>Platform Analytics</h2>
                    <div class="time-filter">
                        <select id="analyticsPeriod">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                </div>

                <div class="charts-container">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>User Growth</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Top Lessons by Enrollment</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="topLessonsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <div class="activity-header">
                    <h3>Recent Admin Activity</h3>
                    <a href="#audit-logs" class="view-all">View All</a>
                </div>
                <div class="activity-list">
                    <?php if (empty($recentActivity)): ?>
                        <div class="activity-item">
                            <div class="activity-details">
                                <p>No recent activity</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="activity-details">
                                    <p><strong><?php echo htmlspecialchars($activity['action']); ?></strong></p>
                                    <p><?php echo ucfirst($activity['target_type']); ?> ID: <?php echo $activity['target_id']; ?></p>
                                    <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Management Sections -->
            <?php include 'kanri/user_management.php'; ?>
            <?php include 'kanri/lesson_management.php'; ?>
            <?php include 'kanri/quiz_management.php'; ?>
            <?php include 'kanri/creator_management.php'; ?>
            <?php include 'kanri/shop_management.php'; ?>
            <?php include 'kanri/system_management.php'; ?>

        </div>
    </div>
</div>

<?php include 'include/kanriFooter.php'; ?>