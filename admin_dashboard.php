<?php
session_start();

$pageTitle = 'GAKUMON — Admin Dashboard';
$pageCSS = 'CSS/desktop/kanri-merged.css';
$pageJS = 'JS/desktop/merged-admin_all.js';

include 'include/header.php';
require_once 'config/config.php';

// Check if user is logged in and has Kanri role
if (!isset($_SESSION['sUser'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['sUser'];
$stmt = $connection->prepare("SELECT user_id, role FROM tbl_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $userID = $row['user_id'];
    if ($row['role'] !== 'Kanri') {
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}

// Get comprehensive statistics
$stats = [];

// Total Users with growth
$result = $connection->query("
    SELECT COUNT(*) as total_users, 
           SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_30d,
           SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as dau
    FROM tbl_user
");
$stats['users'] = $result->fetch_assoc();

// Total Lessons with growth
$result = $connection->query("
    SELECT COUNT(*) as total_lessons, 
           SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_lessons_30d
    FROM tbl_lesson
");
$stats['lessons'] = $result->fetch_assoc();

// Revenue/Payouts
$result = $connection->query("
    SELECT COALESCE(SUM(earned_amount), 0) as total_earnings,
           COALESCE(SUM(CASE WHEN recorded_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN earned_amount ELSE 0 END), 0) as earnings_30d
    FROM tbl_creator_earnings
");
$stats['revenue'] = $result->fetch_assoc();

// Gakucoins Economy
$result = $connection->query("SELECT COALESCE(SUM(gakucoins), 0) as total_coins FROM tbl_user");
$stats['coins'] = $result->fetch_assoc();

// User Growth Chart Data
$result = $connection->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM tbl_user 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY date
");
$user_growth_data = $result->fetch_all(MYSQLI_ASSOC);

// Top Lessons by Enrollment
$result = $connection->query("
    SELECT l.lesson_id, l.title, COUNT(e.user_id) as enrollment_count 
    FROM tbl_lesson l 
    LEFT JOIN tbl_user_enrollments e ON l.lesson_id = e.lesson_id 
    GROUP BY l.lesson_id, l.title 
    ORDER BY enrollment_count DESC 
    LIMIT 10
");
$top_lessons = $result->fetch_all(MYSQLI_ASSOC);

// Quiz Analytics
$result = $connection->query("
    SELECT q.quiz_id, q.title, 
           COUNT(qa.attempt_id) as attempts,
           AVG(qa.score) as avg_score,
           SUM(CASE WHEN qa.score >= 3 THEN 1 ELSE 0 END) / COUNT(qa.attempt_id) * 100 as pass_rate
    FROM tbl_quizzes q
    LEFT JOIN tbl_user_quiz_attempts qa ON q.quiz_id = qa.quiz_id
    GROUP BY q.quiz_id
    HAVING attempts > 0
    ORDER BY attempts DESC
    LIMIT 10
");
$quiz_analytics = $result->fetch_all(MYSQLI_ASSOC);

// Shop Analytics
$result = $connection->query("
    SELECT si.item_name, si.price, COUNT(ui.user_item_id) as sales_count,
           SUM(si.price) as total_revenue
    FROM tbl_shop_items si
    LEFT JOIN tbl_user_items ui ON si.item_id = ui.item_id
    GROUP BY si.item_id
    ORDER BY sales_count DESC
    LIMIT 10
");
$shop_analytics = $result->fetch_all(MYSQLI_ASSOC);

// Recent Activity
$result = $connection->query("
    SELECT al.*, u.username as admin_username 
    FROM tbl_admin_audit_logs al
    JOIN tbl_user u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC 
    LIMIT 10
");
$recent_activity = $result->fetch_all(MYSQLI_ASSOC);

include 'include/desktopKanriNav.php';
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

<!-- Main Layout -->
<div class="main-layout">
    <div class="content-area">
        <div class="container-fluid page-content">
            
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($username); ?>! Comprehensive platform management and analytics.</p>
            </div>

            <!-- KPI Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['users']['total_users']); ?></h3>
                        <p>Total Users</p>
                        <small>+<?php echo $stats['users']['new_users_30d']; ?> this month | DAU: <?php echo $stats['users']['dau']; ?></small>
                    </div>
                    <a href="#user-management" class="stat-action"><i class="fas fa-cog"></i></a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon lessons">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['lessons']['total_lessons']); ?></h3>
                        <p>Total Lessons</p>
                        <small>+<?php echo $stats['lessons']['new_lessons_30d']; ?> this month</small>
                    </div>
                    <a href="#lesson-management" class="stat-action"><i class="fas fa-cog"></i></a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($stats['revenue']['total_earnings'], 2); ?></h3>
                        <p>Total Revenue</p>
                        <small>₱<?php echo number_format($stats['revenue']['earnings_30d'], 2); ?> this month</small>
                    </div>
                    <a href="#creator-management" class="stat-action"><i class="fas fa-cog"></i></a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon quizzes">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['coins']['total_coins']); ?></h3>
                        <p>Gakucoins Supply</p>
                        <small>In circulation</small>
                    </div>
                    <a href="#shop-management" class="stat-action"><i class="fas fa-cog"></i></a>
                </div>
            </div>

            <!-- Analytics Section -->
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
                            <h3>User Growth Trend</h3>
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

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Quiz Performance</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="quizPerformanceChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Shop Revenue</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="shopRevenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <div class="activity-header">
                    <h3>Recent Admin Activity</h3>
                    <a href="#system-management" class="view-all">View All Logs</a>
                </div>
                <div class="activity-list">
                    <?php if (empty($recent_activity)): ?>
                        <div class="activity-item">
                            <div class="activity-details">
                                <p>No recent activity</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="activity-details">
                                    <p><strong><?php echo htmlspecialchars($activity['admin_username']); ?></strong></p>
                                    <p><?php echo htmlspecialchars($activity['action']); ?></p>
                                    <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Management Section -->
            <section id="user-management" class="management-section">
                <div class="section-header">
                    <h2>User Management</h2>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="showAddUserModal()">
                            <i class="bi bi-plus-circle"></i> Add User
                        </button>
                        <button class="btn btn-secondary" onclick="exportTableData('userTable', 'users.csv')">
                            <i class="bi bi-download"></i> Export CSV
                        </button>
                    </div>
                </div>

                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" id="userSearch" placeholder="Search users..." class="search-input" oninput="debounceSearch('user', this.value)">
                        <i class="bi bi-search"></i>
                    </div>
                    <div class="filters">
                        <select id="roleFilter" class="filter-select">
                            <option value="">All Roles</option>
                            <option value="Gakusei">Gakusei</option>
                            <option value="Gakusensei">Gakusensei</option>
                            <option value="Kanri">Kanri</option>
                        </select>
                        <select id="subscriptionFilter" class="filter-select">
                            <option value="">All Subscriptions</option>
                            <option value="Free">Free</option>
                            <option value="Premium">Premium</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table id="userTable" class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Subscription</th>
                                <th>Gakucoins</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            <?php
                            $user_page = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
                            $user_offset = ($user_page - 1) * 10;
                            $users_result = $connection->query("
                                SELECT user_id, username, email_address, first_name, last_name, role, 
                                       subscription_type, gakucoins, is_verified, created_at 
                                FROM tbl_user 
                                ORDER BY user_id ASC
                                LIMIT 10 OFFSET $user_offset
                            ");
                            
                            while ($user = $users_result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email_address']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($user['role']); ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($user['subscription_type']); ?>">
                                        <?php echo $user['subscription_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($user['gakucoins']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['is_verified'] ? 'success' : 'warning'; ?>">
                                        <?php echo $user['is_verified'] ? 'Verified' : 'Pending'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editUser(<?php echo $user['user_id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteUser(<?php echo $user['user_id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <button class="btn-action btn-view" onclick="viewUserDetails(<?php echo $user['user_id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="pagination-controls">
                        <button onclick="loadUserPage(<?php echo max(1, $user_page - 1); ?>)" <?php echo $user_page <= 1 ? 'disabled' : ''; ?>>Previous</button>
                        <span>Page <?php echo $user_page; ?></span>
                        <button onclick="loadUserPage(<?php echo $user_page + 1; ?>)">Next</button>
                    </div>
                </div>
            </section>

            <!-- Lesson Management Section -->
            <section id="lesson-management" class="management-section">
                <div class="section-header">
                    <h2>Lesson Management</h2>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="showAddLessonModal()">
                            <i class="bi bi-plus-circle"></i> Add Lesson
                        </button>
                        <button class="btn btn-secondary" onclick="exportTableData('lessonTable', 'lessons.csv')">
                            <i class="bi bi-download"></i> Export CSV
                        </button>
                    </div>
                </div>
                
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" id="lessonSearch" placeholder="Search lessons..." class="search-input" oninput="debounceSearch('lesson', this.value)">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table id="lessonTable" class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Topic</th>
                                <th>Author</th>
                                <th>Difficulty</th>
                                <th>Enrollments</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="lessonTableBody">
                            <?php
                            $page = isset($_GET['lesson_page']) ? (int)$_GET['lesson_page'] : 1;
                            $offset = ($page - 1) * 10;
                            $lessons_result = $connection->query("
                                SELECT l.*, t.topic_name, u.username as author_name,
                                       COUNT(e.user_id) as enrollment_count
                                FROM tbl_lesson l
                                LEFT JOIN tbl_topic t ON l.topic_id = t.topic_id
                                LEFT JOIN tbl_user u ON l.author_id = u.user_id
                                LEFT JOIN tbl_user_enrollments e ON l.lesson_id = e.lesson_id
                                GROUP BY l.lesson_id
                                ORDER BY l.lesson_id ASC
                                LIMIT 10 OFFSET $offset
                            ");
                            
                            while ($lesson = $lessons_result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $lesson['lesson_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($lesson['title']); ?></strong>
                                    <br><small><?php echo htmlspecialchars(substr($lesson['short_desc'], 0, 50)) . '...'; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($lesson['topic_name']); ?></td>
                                <td><?php echo $lesson['author_name'] ?: 'System'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($lesson['difficulty_level']); ?>">
                                        <?php echo $lesson['difficulty_level']; ?>
                                    </span>
                                </td>
                                <td><?php echo $lesson['enrollment_count']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $lesson['is_private'] ? 'warning' : 'success'; ?>">
                                        <?php echo $lesson['is_private'] ? 'Private' : 'Public'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editLesson(<?php echo $lesson['lesson_id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteLesson(<?php echo $lesson['lesson_id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <button class="btn-action btn-view" onclick="viewLessonDetails(<?php echo $lesson['lesson_id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="pagination-controls">
                        <button onclick="loadLessonPage(<?php echo max(1, $page - 1); ?>)" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
                        <span>Page <?php echo $page; ?></span>
                        <button onclick="loadLessonPage(<?php echo $page + 1; ?>)">Next</button>
                    </div>
                </div>
            </section>

            <!-- Quiz Management Section -->
            <section id="quiz-management" class="management-section">
                <div class="section-header">
                    <h2>Quiz Management</h2>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="showAddQuizModal()">
                            <i class="bi bi-plus-circle"></i> Add Quiz
                        </button>
                        <button class="btn btn-secondary" onclick="exportTableData('quizTable', 'quizzes.csv')">
                            <i class="bi bi-download"></i> Export CSV
                        </button>
                    </div>
                </div>
                
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" id="quizSearch" placeholder="Search quizzes..." class="search-input" oninput="debounceSearch('quiz', this.value)">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table id="quizTable" class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Lesson</th>
                                <th>Questions</th>
                                <th>Type</th>
                                <th>Attempts</th>
                                <th>Avg Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $quizzes_result = $connection->query("
                                SELECT q.quiz_id, q.title, q.is_ai_generated, q.created_at,
                                       l.title as lesson_title,
                                       COUNT(DISTINCT qn.question_id) as question_count,
                                       COUNT(DISTINCT qa.attempt_id) as attempt_count,
                                       AVG(qa.score) as avg_score
                                FROM tbl_quizzes q
                                LEFT JOIN tbl_lesson l ON q.lesson_id = l.lesson_id
                                LEFT JOIN tbl_questions qn ON q.quiz_id = qn.quiz_id
                                LEFT JOIN tbl_user_quiz_attempts qa ON q.quiz_id = qa.quiz_id
                                GROUP BY q.quiz_id
                                ORDER BY q.quiz_id ASC
                                LIMIT 10
                            ");
                            
                            while ($quiz = $quizzes_result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $quiz['quiz_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($quiz['title'] ?: 'Untitled Quiz'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($quiz['lesson_title'] ?: 'Standalone'); ?></td>
                                <td><?php echo $quiz['question_count']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $quiz['is_ai_generated'] ? 'info' : 'primary'; ?>">
                                        <?php echo $quiz['is_ai_generated'] ? 'AI Generated' : 'Manual'; ?>
                                    </span>
                                </td>
                                <td><?php echo $quiz['attempt_count']; ?></td>
                                <td><?php echo $quiz['avg_score'] ? number_format($quiz['avg_score'], 1) : 'N/A'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editQuiz(<?php echo $quiz['quiz_id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteQuiz(<?php echo $quiz['quiz_id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Creator Management Section -->
            <section id="creator-management" class="management-section">
                <div class="section-header">
                    <h2>Creator Management</h2>
                </div>

                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" id="creatorSearch" placeholder="Search creators..." class="search-input" oninput="debounceSearch('creator', this.value)">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="tabs">
                    <button class="tab-button active" onclick="openCreatorTab('applications')">Applications</button>
                    <button class="tab-button" onclick="openCreatorTab('earnings')">Earnings</button>
                    <button class="tab-button" onclick="openCreatorTab('payouts')">Payouts</button>
                </div>

                <div id="applications-tab" class="tab-content active">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Education</th>
                                    <th>School</th>
                                    <th>Expertise</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $applications_result = $connection->query("
                                    SELECT ca.*, u.username, u.email_address 
                                    FROM tbl_creator_applications ca
                                    JOIN tbl_user u ON ca.user_id = u.user_id
                                    ORDER BY ca.application_id ASC
                                    LIMIT 10
                                ");
                                
                                while ($app = $applications_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $app['application_id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($app['username']); ?></strong>
                                        <br><small><?php echo htmlspecialchars($app['email_address']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['educ_attainment']); ?></td>
                                    <td><?php echo htmlspecialchars($app['school']); ?></td>
                                    <td><?php echo htmlspecialchars($app['field_of_expertise']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($app['status'] == 'pending'): ?>
                                                <button class="btn-action btn-success" onclick="approveApplication(<?php echo $app['application_id']; ?>)">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <button class="btn-action btn-danger" onclick="rejectApplication(<?php echo $app['application_id']; ?>)">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="earnings-tab" class="tab-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Creator</th>
                                    <th>Lesson</th>
                                    <th>Metric</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $earnings_result = $connection->query("
                                    SELECT ce.*, u.username, l.title as lesson_title
                                    FROM tbl_creator_earnings ce
                                    JOIN tbl_user u ON ce.user_id = u.user_id
                                    LEFT JOIN tbl_lesson l ON ce.lesson_id = l.lesson_id
                                    ORDER BY ce.earning_id ASC
                                    LIMIT 10
                                ");
                                
                                while ($earning = $earnings_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($earning['username']); ?></td>
                                    <td><?php echo htmlspecialchars($earning['lesson_title'] ?: 'N/A'); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $earning['metric_type'])); ?></td>
                                    <td>₱<?php echo number_format($earning['earned_amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($earning['recorded_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="payouts-tab" class="tab-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Creator</th>
                                    <th>Bank</th>
                                    <th>Account</th>
                                    <th>Total Earnings</th>
                                    <th>Last Payout</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $payouts_result = $connection->query("
                                    SELECT cp.*, u.username, gb.account_first_name, gb.account_last_name, gb.account_number
                                    FROM tbl_creator_payouts cp
                                    JOIN tbl_user u ON cp.user_id = u.user_id
                                    LEFT JOIN tbl_gakusensei_bank_info gb ON u.user_id = gb.user_id
                                    ORDER BY cp.payout_id ASC
                                    LIMIT 10
                                ");
                                
                                while ($payout = $payouts_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payout['username']); ?></td>
                                    <td><?php echo htmlspecialchars($payout['bank_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payout['account_number']); ?></td>
                                    <td>₱<?php echo number_format($payout['total_earnings'], 2); ?></td>
                                    <td><?php echo $payout['last_payout'] ? date('M j, Y', strtotime($payout['last_payout'])) : 'Never'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-primary" onclick="processPayout(<?php echo $payout['payout_id']; ?>)">
                                                <i class="bi bi-cash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Shop Management Section -->
            <section id="shop-management" class="management-section">
                <div class="section-header">
                    <h2>Shop & Economy Management</h2>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="showAddItemModal()">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                    </div>
                </div>

                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" id="shopSearch" placeholder="Search items/users..." class="search-input" oninput="debounceSearch('shop', this.value)">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="tabs">
                    <button class="tab-button active" onclick="openShopTab('items')">Shop Items</button>
                    <button class="tab-button" onclick="openShopTab('inventory')">User Inventory</button>
                </div>

                <div id="items-tab" class="tab-content active">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Sales</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $items_result = $connection->query("
                                    SELECT si.*, COUNT(ui.user_item_id) as sales_count
                                    FROM tbl_shop_items si
                                    LEFT JOIN tbl_user_items ui ON si.item_id = ui.item_id
                                    GROUP BY si.item_id
                                    ORDER BY si.item_id ASC
                                    LIMIT 10
                                ");
                                
                                while ($item = $items_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $item['item_id']; ?></td>
                                    <td><span style="font-size: 24px;"><?php echo $item['image_url']; ?></span></td>
                                    <td>
                                        <span class="badge badge-<?php echo $item['item_type']; ?>">
                                            <?php echo ucfirst($item['item_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo $item['price']; ?> coins</td>
                                    <td><?php echo $item['sales_count']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-edit" onclick="editShopItem(<?php echo $item['item_id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="deleteShopItem(<?php echo $item['item_id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <button class="btn-action btn-primary" onclick="grantItemToUser(<?php echo $item['item_id']; ?>)">
                                                <i class="bi bi-gift"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="pets-tab" class="tab-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Pet ID</th>
                                    <th>Name</th>
                                    <th>Users</th>
                                    <th>Avg Energy</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pets_result = $connection->query("
                                    SELECT p.*, COUNT(up.user_pet_id) as user_count, AVG(up.energy_level) as avg_energy
                                    FROM tbl_pet p
                                    LEFT JOIN tbl_user_pet up ON p.pet_id = up.pet_id
                                    GROUP BY p.pet_id
                                ");
                                
                                while ($pet = $pets_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $pet['pet_id']; ?></td>
                                    <td><?php echo htmlspecialchars($pet['pet_name']); ?></td>
                                    <td><?php echo $pet['user_count']; ?></td>
                                    <td><?php echo $pet['avg_energy'] ? number_format($pet['avg_energy'], 1) . '%' : 'N/A'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-edit" onclick="editPet(<?php echo $pet['pet_id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="inventory-tab" class="tab-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Gakucoins</th>
                                    <th>Food Items</th>
                                    <th>Accessories</th>
                                    <th>Pet Energy</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                                <?php
                                $inv_page = isset($_GET['inv_page']) ? (int)$_GET['inv_page'] : 1;
                                $inv_offset = ($inv_page - 1) * 10;
                                $inventory_result = $connection->query("
                                    SELECT u.user_id, u.username, u.email_address, u.gakucoins,
                                           COUNT(DISTINCT uf.user_food_id) as food_count,
                                           COUNT(DISTINCT ua.user_accessory_id) as accessory_count,
                                           up.energy_level
                                    FROM tbl_user u
                                    LEFT JOIN tbl_user_foods uf ON u.user_id = uf.user_id
                                    LEFT JOIN tbl_user_accessories ua ON u.user_id = ua.user_id
                                    LEFT JOIN tbl_user_pet up ON u.user_id = up.user_id
                                    GROUP BY u.user_id
                                    ORDER BY u.user_id ASC
                                    LIMIT 10 OFFSET $inv_offset
                                ");
                                
                                while ($user = $inventory_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <br><small>Email: <?php echo htmlspecialchars($user['email_address']); ?></small>
                                    </td>
                                    <td><?php echo number_format($user['gakucoins']); ?></td>
                                    <td><?php echo $user['food_count']; ?></td>
                                    <td><?php echo $user['accessory_count']; ?></td>
                                    <td>
                                        <?php if ($user['energy_level'] !== null): ?>
                                            <div class="energy-bar">
                                                <div class="energy-fill" style="width: <?php echo $user['energy_level']; ?>%"></div>
                                                <span class="energy-text"><?php echo $user['energy_level']; ?>%</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No pet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-success" onclick="grantItemsToUser(<?php echo $user['user_id']; ?>)">
                                                <i class="bi bi-gift"></i>
                                            </button>
                                            <button class="btn-action btn-primary" onclick="showGakucoinModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['gakucoins']; ?>)">
                                                <i class="bi bi-coin"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <div class="pagination-controls">
                            <button onclick="loadInventoryPage(<?php echo max(1, $inv_page - 1); ?>)" <?php echo $inv_page <= 1 ? 'disabled' : ''; ?>>Previous</button>
                            <span>Page <?php echo $inv_page; ?></span>
                            <button onclick="loadInventoryPage(<?php echo $inv_page + 1; ?>)">Next</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- System Management Section -->
            <section id="system-management" class="management-section">
                <div class="section-header">
                    <h2>System Management</h2>
                </div>

                <div class="tabs">
                    <button class="tab-button active" onclick="openSystemTab('audit')">Audit Logs</button>
                    <button class="tab-button" onclick="openSystemTab('feedback')">Feedback</button>
                    <button class="tab-button" onclick="openSystemTab('pending')">Pending Verifications</button>
                    <button class="tab-button" onclick="openSystemTab('topics')">Topics</button>
                </div>

                <div id="audit-tab" class="tab-content active">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Target</th>
                                    <th>Target ID</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $audit_result = $connection->query("
                                    SELECT al.*, u.username as admin_username
                                    FROM tbl_admin_audit_logs al
                                    JOIN tbl_user u ON al.user_id = u.user_id
                                    ORDER BY al.log_id ASC
                                    LIMIT 10
                                ");
                                
                                while ($log = $audit_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['admin_username']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $log['target_type']; ?>">
                                            <?php echo ucfirst($log['target_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $log['target_id']; ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="feedback-tab" class="tab-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Feedback</th>
                                    <th>Rating</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $feedback_result = $connection->query("
                                    SELECT * FROM tbl_feedback 
                                    ORDER BY feedback_id ASC
                                    LIMIT 10
                                ");
                                
                                while ($feedback = $feedback_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($feedback['user_name']): ?>
                                            <?php echo htmlspecialchars($feedback['user_name']); ?>
                                            <br><small><?php echo htmlspecialchars($feedback['user_email']); ?></small>
                                        <?php else: ?>
                                            User #<?php echo $feedback['user_id']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="feedback-text">
                                            <?php echo nl2br(htmlspecialchars(substr($feedback['feedback_text'], 0, 100))); ?>
                                            <?php if (strlen($feedback['feedback_text']) > 100) echo '...'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($feedback['rating']): ?>
                                            <div class="rating-stars">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $feedback['rating'] ? '★' : '☆';
                                                }
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No rating</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($feedback['submitted_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-delete" onclick="deleteFeedback(<?php echo $feedback['feedback_id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="pending-tab" class="tab-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Verification Code</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pending_result = $connection->query("
                                    SELECT * FROM tbl_pending_verif 
                                    ORDER BY index_id ASC
                                    LIMIT 10
                                ");
                                
                                while ($pending = $pending_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pending['first_name'] . ' ' . $pending['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($pending['username']); ?></td>
                                    <td><?php echo htmlspecialchars($pending['email_address']); ?></td>
                                    <td><?php echo $pending['verif_code']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-success" onclick="approveVerification(<?php echo $pending['index_id']; ?>)">
                                                <i class="bi bi-check"></i>
                                            </button>
                                            <button class="btn-action btn-danger" onclick="rejectVerification(<?php echo $pending['index_id']; ?>)">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="topics-tab" class="tab-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Topic Name</th>
                                    <th>Icon</th>
                                    <th>Lessons</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $topics_result = $connection->query("
                                    SELECT t.*, COUNT(l.lesson_id) as lesson_count
                                    FROM tbl_topic t
                                    LEFT JOIN tbl_lesson l ON t.topic_id = l.topic_id
                                    GROUP BY t.topic_id
                                    ORDER BY t.topic_id ASC
                                    LIMIT 10
                                ");
                                
                                while ($topic = $topics_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $topic['topic_id']; ?></td>
                                    <td><?php echo htmlspecialchars($topic['topic_name']); ?></td>
                                    <td>
                                        <?php if ($topic['topic_icon']): ?>
                                            <div class="topic-icon"><?php echo $topic['topic_icon']; ?></div>
                                        <?php else: ?>
                                            <span class="text-muted">No icon</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $topic['lesson_count']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-edit" onclick="editTopic(<?php echo $topic['topic_id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="deleteTopic(<?php echo $topic['topic_id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </div>
</div>

<!-- Modal Styles -->
<style>
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
.modal-content { background: #fff; margin: 5% auto; padding: 0; border-radius: 8px; width: 500px; max-height: 80vh; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.modal-header { background: #f8f9fa; padding: 20px; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; color: #495057; }
.modal-body { padding: 20px; max-height: 60vh; overflow-y: auto; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #495057; }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; }
.form-control:focus { outline: none; border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
.modal-footer { background: #f8f9fa; padding: 15px 20px; border-top: 1px solid #dee2e6; text-align: right; }
.btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 10px; }
.btn-secondary { background: #6c757d; color: white; }
.btn-primary { background: #007bff; color: white; }
.btn:hover { opacity: 0.9; }
.close { cursor: pointer; font-size: 24px; color: #aaa; }
.close:hover { color: #000; }
.pagination-controls { margin-top: 15px; text-align: center; }
.pagination-controls button { margin: 0 5px; padding: 8px 16px; border: none; border-radius: 4px; background: #811212; color: white; cursor: pointer; font-size: 14px; }
.pagination-controls button:disabled { opacity: 0.5; cursor: not-allowed; background: #ccc; }
.pagination-controls button:hover:not(:disabled) { background: #a01515; }
.pagination-controls span { margin: 0 10px; font-weight: 500; color: #495057; }
.coin-buttons { display: flex; flex-wrap: wrap; gap: 5px; }
.coin-buttons button { padding: 5px 10px; border: 1px solid #ddd; background: #f8f9fa; cursor: pointer; border-radius: 4px; }
.coin-buttons button:hover { background: #e9ecef; }
</style>

<!-- Modal for Add/Edit User -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="userModalTitle">Add User</h3>
            <span class="close" onclick="closeModal('userModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId" name="user_id">
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" id="firstName" name="first_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" id="lastName" name="last_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" id="username" name="username" class="form-control">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="email" name="email_address" class="form-control">
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" id="password" name="password" class="form-control">
                </div>
                <div class="form-group">
                    <label>Role:</label>
                    <select id="role" name="role" class="form-control">
                        <option value="Gakusei">Gakusei</option>
                        <option value="Gakusensei">Gakusensei</option>
                        <option value="Kanri">Kanri</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subscription:</label>
                    <select id="subscription" name="subscription_type" class="form-control">
                        <option value="Free">Free</option>
                        <option value="Premium">Premium</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gakucoins:</label>
                    <input type="number" id="gakucoins" name="gakucoins" value="0" class="form-control">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" id="isVerified" name="is_verified" checked> Verified</label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('userModal')" class="btn btn-secondary">Cancel</button>
            <button type="submit" form="userForm" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Lesson -->
<div id="lessonModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="lessonModalTitle">Add Lesson</h3>
            <span class="close" onclick="closeModal('lessonModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="lessonForm">
                <input type="hidden" id="lessonId" name="lesson_id">
                <div class="form-group">
                    <label>Title:</label>
                    <input type="text" id="lessonTitle" name="title" class="form-control">
                </div>
                <div class="form-group">
                    <label>Short Description:</label>
                    <textarea id="shortDesc" name="short_desc" rows="2" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Long Description:</label>
                    <textarea id="longDesc" name="long_desc" rows="4" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Duration (HH:MM:SS):</label>
                    <input type="text" id="duration" name="duration" value="00:30:00" class="form-control">
                </div>
                <div class="form-group">
                    <label>Topic ID:</label>
                    <input type="number" id="topicId" name="topic_id" value="1" class="form-control">
                </div>
                <div class="form-group">
                    <label>Difficulty:</label>
                    <select id="difficulty" name="difficulty_level" class="form-control">
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" id="isPrivate" name="is_private"> Private</label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('lessonModal')" class="btn btn-secondary">Cancel</button>
            <button type="submit" form="lessonForm" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Quiz -->
<div id="quizModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="quizModalTitle">Add Quiz</h3>
            <span class="close" onclick="closeModal('quizModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="quizForm">
                <input type="hidden" id="quizId" name="quiz_id">
                <div class="form-group">
                    <label>Title:</label>
                    <input type="text" id="quizTitle" name="title" class="form-control">
                </div>
                <div class="form-group">
                    <label>Lesson ID (optional):</label>
                    <input type="number" id="quizLessonId" name="lesson_id" class="form-control">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" id="isAiGenerated" name="is_ai_generated"> AI Generated</label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('quizModal')" class="btn btn-secondary">Cancel</button>
            <button type="submit" form="quizForm" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<!-- Modal for View Details -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="viewModalTitle">Details</h3>
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="viewModalContent"></div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('viewModal')" class="btn btn-secondary">Close</button>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Shop Item -->
<div id="shopItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="shopItemModalTitle">Add Shop Item</h3>
            <span class="close" onclick="closeModal('shopItemModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="shopItemForm">
                <input type="hidden" id="shopItemId" name="item_id">
                <div class="form-group">
                    <label>Item Type:</label>
                    <select id="itemType" name="item_type" class="form-control">
                        <option value="food">Food</option>
                        <option value="accessory">Accessory</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Item Name:</label>
                    <input type="text" id="itemName" name="item_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea id="itemDescription" name="description" rows="3" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Price (coins):</label>
                    <input type="number" id="itemPrice" name="price" class="form-control">
                </div>
                <div class="form-group">
                    <label>Energy Restore (for food):</label>
                    <input type="number" id="energyRestore" name="energy_restore" class="form-control">
                </div>
                <div class="form-group">
                    <label>Image/Icon:</label>
                    <input type="text" id="imageUrl" name="image_url" class="form-control" placeholder="Emoji or URL">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('shopItemModal')" class="btn btn-secondary">Cancel</button>
            <button type="submit" form="shopItemForm" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Topic -->
<div id="topicModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="topicModalTitle">Add Topic</h3>
            <span class="close" onclick="closeModal('topicModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="topicForm">
                <input type="hidden" id="topicIdField" name="topic_id">
                <div class="form-group">
                    <label>Topic Name:</label>
                    <input type="text" id="topicName" name="topic_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Topic Icon:</label>
                    <input type="text" id="topicIcon" name="topic_icon" class="form-control" placeholder="Emoji or icon">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('topicModal')" class="btn btn-secondary">Cancel</button>
            <button type="submit" form="topicForm" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<!-- Modal for Grant Item (from shop to user) -->
<div id="grantItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Grant Item to User</h3>
            <span class="close" onclick="closeModal('grantItemModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="grantItemForm">
                <input type="hidden" id="grantItemId" name="item_id">
                <div class="form-group">
                    <label>Select User:</label>
                    <select id="grantUserId" name="user_id" class="form-control">
                        <option value="">Loading users...</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('grantItemModal')" class="btn btn-secondary">Cancel</button>
            <button type="submit" form="grantItemForm" class="btn btn-primary">Grant Item</button>
        </div>
    </div>
</div>

<!-- Modal for Grant Item to Specific User (from inventory) -->
<div id="grantToUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Grant Item to User</h3>
            <span class="close" onclick="closeModal('grantToUserModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="grantToUserForm">
                <input type="hidden" id="grantToUserId" name="user_id">
                <div class="form-group">
                    <label>Select Item:</label>
                    <select id="grantToUserItemId" name="item_id" class="form-control">
                        <option value="">Loading items...</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('grantToUserModal')" class="btn btn-secondary">Cancel</button>
            <button type="submit" form="grantToUserForm" class="btn btn-primary">Grant Item</button>
        </div>
    </div>
</div>

<!-- Modal for Gakucoin Management -->
<div id="gakucoinModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Manage Gakucoins</h3>
            <span class="close" onclick="closeModal('gakucoinModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>User: <strong id="gakucoinUsername"></strong></label>
                <label>Current Balance: <strong id="gakucoinCurrent"></strong></label>
            </div>
            <div class="form-group">
                <label>Action:</label>
                <select id="gakucoinAction" class="form-control" onchange="toggleCustomAmount()">
                    <option value="set">Set Amount</option>
                    <option value="add">Add Amount</option>
                    <option value="remove">Remove Amount</option>
                </select>
            </div>
            <div class="form-group">
                <label>Amount:</label>
                <div class="coin-buttons">
                    <button type="button" onclick="setGakucoinAmount(5)">5</button>
                    <button type="button" onclick="setGakucoinAmount(10)">10</button>
                    <button type="button" onclick="setGakucoinAmount(100)">100</button>
                    <button type="button" onclick="setGakucoinAmount(1000)">1000</button>
                    <input type="number" id="gakucoinAmount" class="form-control" placeholder="Custom amount" style="margin-top: 10px;">
                </div>
            </div>
            <input type="hidden" id="gakucoinUserId">
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('gakucoinModal')" class="btn btn-secondary">Cancel</button>
            <button type="button" onclick="processGakucoinChange()" class="btn btn-primary">Apply</button>
        </div>
    </div>
</div>

<!-- Include Chart.js for analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="JS/desktop/adminManagementScript.js"></script>
<script src="JS/desktop/adminCrudTest.js"></script>
<script src="JS/desktop/adminPagination.js"></script>


<script>
// Initialize charts with data
document.addEventListener('DOMContentLoaded', function() {
    // User Growth Chart
    const userGrowthData = <?php echo json_encode($user_growth_data); ?>;
    const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(userGrowthCtx, {
        type: 'line',
        data: {
            labels: userGrowthData.map(d => d.date),
            datasets: [{
                label: 'New Users',
                data: userGrowthData.map(d => d.count),
                borderColor: '#811212',
                backgroundColor: 'rgba(129, 18, 18, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true },
                x: { display: false }
            }
        }
    });

    // Top Lessons Chart
    const topLessonsData = <?php echo json_encode($top_lessons); ?>;
    const topLessonsCtx = document.getElementById('topLessonsChart').getContext('2d');
    new Chart(topLessonsCtx, {
        type: 'bar',
        data: {
            labels: topLessonsData.map(d => d.title.substring(0, 20) + '...'),
            datasets: [{
                label: 'Enrollments',
                data: topLessonsData.map(d => d.enrollment_count),
                backgroundColor: '#4299e1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } }
        }
    });

    // Quiz Performance Chart
    const quizData = <?php echo json_encode($quiz_analytics); ?>;
    const quizCtx = document.getElementById('quizPerformanceChart').getContext('2d');
    new Chart(quizCtx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Quiz Performance',
                data: quizData.map(d => ({x: d.attempts, y: d.avg_score})),
                backgroundColor: '#ed8936'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { title: { display: true, text: 'Attempts' } },
                y: { title: { display: true, text: 'Avg Score' } }
            }
        }
    });

    // Shop Revenue Chart
    const shopData = <?php echo json_encode($shop_analytics); ?>;
    const shopCtx = document.getElementById('shopRevenueChart').getContext('2d');
    new Chart(shopCtx, {
        type: 'doughnut',
        data: {
            labels: shopData.map(d => d.item_name),
            datasets: [{
                data: shopData.map(d => d.total_revenue),
                backgroundColor: ['#48bb78', '#4299e1', '#ed8936', '#9f7aea', '#f56565']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});

// Tab management functions
function openCreatorTab(tabName) {
    document.querySelectorAll('#creator-management .tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('#creator-management .tab-button').forEach(button => {
        button.classList.remove('active');
    });
    document.getElementById(tabName + '-tab').classList.add('active');
    event.currentTarget.classList.add('active');
}

function openShopTab(tabName) {
    document.querySelectorAll('#shop-management .tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('#shop-management .tab-button').forEach(button => {
        button.classList.remove('active');
    });
    document.getElementById(tabName + '-tab').classList.add('active');
    event.currentTarget.classList.add('active');
}

function openSystemTab(tabName) {
    document.querySelectorAll('#system-management .tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('#system-management .tab-button').forEach(button => {
        button.classList.remove('active');
    });
    document.getElementById(tabName + '-tab').classList.add('active');
    event.currentTarget.classList.add('active');
}

// Mobile detection and redirect
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

function redirectToIndex() {
    window.location.href = 'index.php';
}

if (isMobileDevice()) {
    document.querySelector('.mobile-warning-modal').style.display = 'flex';
}

// Modal functions
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// CRUD operation functions
function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userModal').style.display = 'block';
}

function editUser(id) {
    fetch(`admin_ajax.php?action=get_user&user_id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const u = data.user;
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = u.user_id;
            document.getElementById('firstName').value = u.first_name;
            document.getElementById('lastName').value = u.last_name;
            document.getElementById('username').value = u.username;
            document.getElementById('email').value = u.email_address;
            document.getElementById('password').value = '';
            document.getElementById('role').value = u.role;
            document.getElementById('subscription').value = u.subscription_type;
            document.getElementById('gakucoins').value = u.gakucoins;
            document.getElementById('isVerified').checked = u.is_verified == 1;
            document.getElementById('userModal').style.display = 'block';
        }
    });
}

function deleteUser(id) {
    if (confirm('Delete this user?')) {
        const formData = new FormData();
        formData.append('action', 'delete_user');
        formData.append('user_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function viewUserDetails(id) {
    fetch(`admin_ajax.php?action=get_user&user_id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const u = data.user;
            document.getElementById('viewModalTitle').textContent = 'User Details';
            document.getElementById('viewModalContent').innerHTML = `
                <div class="form-group"><strong>ID:</strong> ${u.user_id}</div>
                <div class="form-group"><strong>Name:</strong> ${u.first_name} ${u.last_name}</div>
                <div class="form-group"><strong>Username:</strong> ${u.username}</div>
                <div class="form-group"><strong>Email:</strong> ${u.email_address}</div>
                <div class="form-group"><strong>Role:</strong> ${u.role}</div>
                <div class="form-group"><strong>Subscription:</strong> ${u.subscription_type}</div>
                <div class="form-group"><strong>Gakucoins:</strong> ${u.gakucoins}</div>
                <div class="form-group"><strong>Verified:</strong> ${u.is_verified ? 'Yes' : 'No'}</div>
                <div class="form-group"><strong>Created:</strong> ${u.created_at}</div>
            `;
            document.getElementById('viewModal').style.display = 'block';
        }
    });
}

function showAddLessonModal() {
    document.getElementById('lessonModalTitle').textContent = 'Add Lesson';
    document.getElementById('lessonForm').reset();
    document.getElementById('lessonId').value = '';
    document.getElementById('duration').value = '00:30:00';
    document.getElementById('topicId').value = '1';
    document.getElementById('lessonModal').style.display = 'block';
}

function editLesson(id) {
    console.log('editLesson called with ID:', id);
    fetch(`admin_ajax.php?action=get_lesson&lesson_id=${id}`)
    .then(r => {
        console.log('Response status:', r.status);
        return r.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            const l = data.lesson;
            console.log('Lesson data:', l);
            document.getElementById('lessonModalTitle').textContent = 'Edit Lesson';
            document.getElementById('lessonId').value = l.lesson_id;
            document.getElementById('lessonTitle').value = l.title;
            document.getElementById('shortDesc').value = l.short_desc;
            document.getElementById('longDesc').value = l.long_desc;
            document.getElementById('duration').value = l.duration;
            document.getElementById('topicId').value = l.topic_id;
            document.getElementById('difficulty').value = l.difficulty_level;
            document.getElementById('isPrivate').checked = l.is_private == 1;
            document.getElementById('lessonModal').style.display = 'block';
            console.log('Modal should be visible now');
        } else {
            console.error('Failed to get lesson:', data.message);
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        alert('Network error: ' + err.message);
    });
}
function deleteLesson(id) {
    if (confirm('Delete this lesson?')) {
        const formData = new FormData();
        formData.append('action', 'delete_lesson');
        formData.append('lesson_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function viewLessonDetails(id) {
    fetch(`admin_ajax.php?action=get_lesson&lesson_id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const l = data.lesson;
            document.getElementById('viewModalTitle').textContent = 'Lesson Details';
            document.getElementById('viewModalContent').innerHTML = `
                <div class="form-group"><strong>ID:</strong> ${l.lesson_id}</div>
                <div class="form-group"><strong>Title:</strong> ${l.title}</div>
                <div class="form-group"><strong>Short Description:</strong> ${l.short_desc}</div>
                <div class="form-group"><strong>Duration:</strong> ${l.duration}</div>
                <div class="form-group"><strong>Topic ID:</strong> ${l.topic_id}</div>
                <div class="form-group"><strong>Difficulty:</strong> ${l.difficulty_level}</div>
                <div class="form-group"><strong>Private:</strong> ${l.is_private ? 'Yes' : 'No'}</div>
                <div class="form-group"><strong>Author ID:</strong> ${l.author_id || 'None'}</div>
            `;
            document.getElementById('viewModal').style.display = 'block';
        }
    });
}
function showAddQuizModal() {
    document.getElementById('quizModalTitle').textContent = 'Add Quiz';
    document.getElementById('quizForm').reset();
    document.getElementById('quizId').value = '';
    document.getElementById('quizModal').style.display = 'block';
}

function editQuiz(id) {
    fetch(`admin_ajax.php?action=get_quiz&quiz_id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const q = data.quiz;
            document.getElementById('quizModalTitle').textContent = 'Edit Quiz';
            document.getElementById('quizId').value = q.quiz_id;
            document.getElementById('quizTitle').value = q.title;
            document.getElementById('quizLessonId').value = q.lesson_id || '';
            document.getElementById('isAiGenerated').checked = q.is_ai_generated == 1;
            document.getElementById('quizModal').style.display = 'block';
        }
    });
}
function deleteQuiz(id) {
    if (confirm('Delete this quiz?')) {
        const formData = new FormData();
        formData.append('action', 'delete_quiz');
        formData.append('quiz_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function manageQuestions(id) { alert('Manage questions functionality - coming soon'); }
function approveApplication(id) {
    if (confirm('Approve this application?')) {
        const formData = new FormData();
        formData.append('action', 'approve_application');
        formData.append('application_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function rejectApplication(id) {
    if (confirm('Reject this application?')) {
        const formData = new FormData();
        formData.append('action', 'reject_application');
        formData.append('application_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function processPayout(id) { alert('Process payout - use admin_ajax.php endpoint'); }
function showAddItemModal() {
    document.getElementById('shopItemModalTitle').textContent = 'Add Shop Item';
    document.getElementById('shopItemForm').reset();
    document.getElementById('shopItemId').value = '';
    document.getElementById('shopItemModal').style.display = 'block';
}
function editShopItem(id) {
    fetch(`admin_ajax.php?action=get_shop_item&item_id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const item = data.item;
            document.getElementById('shopItemModalTitle').textContent = 'Edit Shop Item';
            document.getElementById('shopItemId').value = item.item_id;
            document.getElementById('itemType').value = item.item_type;
            document.getElementById('itemName').value = item.item_name;
            document.getElementById('itemDescription').value = item.description;
            document.getElementById('itemPrice').value = item.price;
            document.getElementById('energyRestore').value = item.energy_restore || '';
            document.getElementById('imageUrl').value = item.image_url;
            document.getElementById('shopItemModal').style.display = 'block';
        }
    });
}
function deleteShopItem(id) {
    if (confirm('Delete this shop item?')) {
        const formData = new FormData();
        formData.append('action', 'delete_shop_item');
        formData.append('item_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function grantItemToUser(id) {
    document.getElementById('grantItemId').value = id;
    // Load users for dropdown with proper format
    fetch('admin_ajax.php?action=get_users_for_dropdown')
    .then(r => r.json())
    .then(data => {
        const userSelect = document.getElementById('grantUserId');
        userSelect.innerHTML = '<option value="">Select a user...</option>';
        
        if (data.success && data.users) {
            data.users.forEach(user => {
                userSelect.innerHTML += `<option value="${user.user_id}">${user.username} (Email: ${user.email_address})</option>`;
            });
        }
        
        document.getElementById('grantItemModal').style.display = 'block';
    })
    .catch(() => {
        // Fallback
        const userSelect = document.getElementById('grantUserId');
        userSelect.innerHTML = '<option value="">Error loading users</option>';
        document.getElementById('grantItemModal').style.display = 'block';
    });
}
function editPet(id) { alert('Edit pet functionality - coming soon'); }
function grantItemsToUser(id) {
    // This opens the grant item modal for a specific user (select item from shop)
    document.getElementById('grantToUserId').value = id;
    
    // Load shop items for dropdown
    fetch('admin_ajax.php?action=get_shop_items')
    .then(r => r.json())
    .then(data => {
        const itemSelect = document.getElementById('grantToUserItemId');
        itemSelect.innerHTML = '<option value="">Select an item...</option>';
        
        if (data.success && data.items) {
            data.items.forEach(item => {
                itemSelect.innerHTML += `<option value="${item.item_id}">${item.item_name} (${item.price} coins)</option>`;
            });
        }
        
        document.getElementById('grantToUserModal').style.display = 'block';
    })
    .catch(() => {
        // Fallback: simple prompt
        const itemId = prompt('Enter item ID to grant:');
        if (itemId) {
            const formData = new FormData();
            formData.append('action', 'grant_item');
            formData.append('user_id', id);
            formData.append('item_id', itemId);
            
            fetch('admin_ajax.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }
    });
}
function showGakucoinModal(userId, username, currentCoins) {
    document.getElementById('gakucoinUserId').value = userId;
    document.getElementById('gakucoinUsername').textContent = username;
    document.getElementById('gakucoinCurrent').textContent = currentCoins.toLocaleString();
    document.getElementById('gakucoinAmount').value = '';
    document.getElementById('gakucoinAction').value = 'set';
    document.getElementById('gakucoinModal').style.display = 'block';
}

function setGakucoinAmount(amount) {
    document.getElementById('gakucoinAmount').value = amount;
}

function processGakucoinChange() {
    const userId = document.getElementById('gakucoinUserId').value;
    const action = document.getElementById('gakucoinAction').value;
    const amount = parseInt(document.getElementById('gakucoinAmount').value);
    
    if (!amount || amount < 0) {
        alert('Please enter a valid amount');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'adjust_gakucoins');
    formData.append('user_id', userId);
    formData.append('coin_action', action);
    formData.append('amount', amount);
    
    fetch('admin_ajax.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            closeModal('gakucoinModal');
            location.reload();
        }
    })
    .catch(err => alert('Error: ' + err.message));
}

function deleteFeedback(id) {
    if (confirm('Delete this feedback?')) {
        const formData = new FormData();
        formData.append('action', 'delete_feedback');
        formData.append('feedback_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function approveVerification(id) {
    if (confirm('Approve this verification?')) {
        const formData = new FormData();
        formData.append('action', 'approve_verification');
        formData.append('index_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function rejectVerification(id) {
    if (confirm('Reject this verification?')) {
        const formData = new FormData();
        formData.append('action', 'reject_verification');
        formData.append('index_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function showAddTopicModal() {
    document.getElementById('topicModalTitle').textContent = 'Add Topic';
    document.getElementById('topicForm').reset();
    document.getElementById('topicIdField').value = '';
    document.getElementById('topicModal').style.display = 'block';
}

function editTopic(id) {
    fetch(`admin_ajax.php?action=get_topic&topic_id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const t = data.topic;
            document.getElementById('topicModalTitle').textContent = 'Edit Topic';
            document.getElementById('topicIdField').value = t.topic_id;
            document.getElementById('topicName').value = t.topic_name;
            document.getElementById('topicIcon').value = t.topic_icon;
            document.getElementById('topicModal').style.display = 'block';
        }
    });
}
function deleteTopic(id) {
    if (confirm('Delete this topic?')) {
        const formData = new FormData();
        formData.append('action', 'delete_topic');
        formData.append('topic_id', id);
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function exportTableData(tableId, filename) { alert('Export functionality - coming soon'); }

// Debounced search functionality
let searchTimeouts = {};
function debounceSearch(type, query) {
    clearTimeout(searchTimeouts[type]);
    searchTimeouts[type] = setTimeout(() => {
        performSearch(type, query);
    }, 1000);
}

function performSearch(type, query) {
    const params = new URLSearchParams({
        action: `search_${type}s`,
        query: query,
        page: 1,
        limit: 10
    });
    
    fetch(`admin_ajax.php?${params}`)
    .then(r => r.text())
    .then(html => {
        const tableBody = document.querySelector(`#${type}TableBody`) || 
                         document.querySelector(`#${type}Table tbody`);
        if (tableBody) {
            tableBody.innerHTML = html;
        }
    })
    .catch(err => console.error('Search failed:', err));
}

// Pagination functions
function loadUserPage(page) {
    const params = new URLSearchParams({
        action: 'search_users',
        query: document.getElementById('userSearch')?.value || '',
        page: page,
        limit: 10
    });
    
    fetch(`admin_ajax.php?${params}`)
    .then(r => r.text())
    .then(html => {
        document.getElementById('userTableBody').innerHTML = html;
    })
    .catch(err => console.error('Pagination failed:', err));
}

function loadLessonPage(page) {
    const params = new URLSearchParams({
        action: 'search_lessons',
        query: document.getElementById('lessonSearch')?.value || '',
        page: page,
        limit: 10
    });
    
    fetch(`admin_ajax.php?${params}`)
    .then(r => r.text())
    .then(html => {
        document.getElementById('lessonTableBody').innerHTML = html;
    })
    .catch(err => console.error('Pagination failed:', err));
}

function loadInventoryPage(page) {
    const params = new URLSearchParams({
        action: 'search_inventory',
        query: '',
        page: page,
        limit: 10
    });
    
    fetch(`admin_ajax.php?${params}`)
    .then(r => r.text())
    .then(html => {
        document.getElementById('inventoryTableBody').innerHTML = html;
    })
    .catch(err => console.error('Pagination failed:', err));
}

// Form submission handlers
document.addEventListener('DOMContentLoaded', function() {
    // User form submission
    document.getElementById('userForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const isEdit = document.getElementById('userId').value !== '';
        formData.append('action', isEdit ? 'update_user' : 'create_user');
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeModal('userModal');
                location.reload();
            }
        })
        .catch(err => alert('Error: ' + err.message));
    });
    
    // Lesson form submission
    document.getElementById('lessonForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const isEdit = document.getElementById('lessonId').value !== '';
        formData.append('action', isEdit ? 'update_lesson' : 'create_lesson');
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeModal('lessonModal');
                location.reload();
            }
        })
        .catch(err => alert('Error: ' + err.message));
    });
    
    // Quiz form submission
    document.getElementById('quizForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const isEdit = document.getElementById('quizId').value !== '';
        formData.append('action', isEdit ? 'update_quiz' : 'create_quiz');
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeModal('quizModal');
                location.reload();
            }
        })
        .catch(err => alert('Error: ' + err.message));
    });
    
    // Shop item form submission
    document.getElementById('shopItemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const isEdit = document.getElementById('shopItemId').value !== '';
        formData.append('action', isEdit ? 'update_shop_item' : 'create_shop_item');
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeModal('shopItemModal');
                location.reload();
            }
        })
        .catch(err => alert('Error: ' + err.message));
    });
    
    // Topic form submission
    document.getElementById('topicForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const isEdit = document.getElementById('topicIdField').value !== '';
        formData.append('action', isEdit ? 'update_topic' : 'create_topic');
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeModal('topicModal');
                location.reload();
            }
        })
        .catch(err => alert('Error: ' + err.message));
    });
    
    // Grant item form submission
    document.getElementById('grantItemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'grant_item');
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeModal('grantItemModal');
                location.reload();
            }
        })
        .catch(err => alert('Error: ' + err.message));
    });
    
    // Grant to user form submission
    document.getElementById('grantToUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'grant_item');
        
        fetch('admin_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeModal('grantToUserModal');
                location.reload();
            }
        })
        .catch(err => alert('Error: ' + err.message));
    });
});
</script>

<?php include 'include/kanriFooter.php'; ?>