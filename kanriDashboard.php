<?php
   session_start();

   $pageTitle = 'GAKUMON — Kanri Dashboard';
   $pageCSS = 'CSS/desktop/kanriDashboardStyle.css';
   $pageJS = 'JS/desktop/kanriDashboardScript.js';

   include 'include/header.php';
   require_once 'config/config.php'; // Database Connection

   if (isset($_SESSION['sUser'])) {
      $username = $_SESSION['sUser'];

      // Get UserID from database
      $stmt = $connection->prepare("SELECT user_id FROM tbl_user WHERE username = ?");
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($row = $result->fetch_assoc()) {
         $userID = $row['user_id'];   // Now you have the userID
      } else {
         echo "User not found.";
         exit;
      }

   } else {
      echo "User not logged in.";
      header("Location: login.php");
      exit;
   }

   // Session and role validation
    if (!isset($_SESSION['sUser']) || $_SESSION['sRole'] !== 'Kanri') {
        header("Location: login.php");
        exit;
    }


   include 'include/desktopKanriNav.php';
?>

<!-- Main layout with three columns -->
<div class="main-layout">
   <!-- Left navigation (already fixed by your CSS) -->
   
   <!-- Middle content area -->
   <div class="content-area">
        <div class="container-fluid page-content">
            <div class="container-fluid page-content">
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back! Here's an overview of your platform.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>2,458</h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 12.5%
                </div>
                </div>

                <div class="stat-card">
                <div class="stat-icon lessons">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <h3>342</h3>
                    <p>Active Lessons</p>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 5.2%
                </div>
                </div>

                <div class="stat-card">
                <div class="stat-icon quizzes">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-info">
                    <h3>1,287</h3>
                    <p>Quizzes Taken</p>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 8.3%
                </div>
                </div>

                <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-info">
                    <h3>25,640</h3>
                    <p>Coins Distributed</p>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 15.7%
                </div>
                </div>
            </div>

            <div class="charts-container">
                <div class="chart-card">
                <div class="chart-header">
                    <h3>User Activity</h3>
                    <select class="time-filter">
                    <option>Last 7 Days</option>
                    <option>Last 30 Days</option>
                    <option>Last 90 Days</option>
                    </select>
                </div>
                <div class="chart-content">
                    <canvas id="userActivityChart"></canvas>
                </div>
                </div>

                <div class="chart-card">
                <div class="chart-header">
                    <h3>Lesson Popularity</h3>
                    <select class="time-filter">
                    <option>Last 7 Days</option>
                    <option>Last 30 Days</option>
                    <option>Last 90 Days</option>
                    </select>
                </div>
                <div class="chart-content">
                    <canvas id="lessonPopularityChart"></canvas>
                </div>
                </div>
            </div>

            <div class="recent-activity">
                <div class="activity-header">
                <h3>Recent Activity</h3>
                <a href="#" class="view-all">View All</a>
                </div>
                <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-icon">
                    <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="activity-details">
                    <p><strong>New user registration</strong></p>
                    <p>Sarah Johnson joined the platform</p>
                    <span class="activity-time">2 hours ago</span>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                    <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="activity-details">
                    <p><strong>Lesson completed</strong></p>
                    <p>Michael completed "Advanced Calculus"</p>
                    <span class="activity-time">5 hours ago</span>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                    <i class="fas fa-trophy"></i>
                    </div>
                    <div class="activity-details">
                    <p><strong>Quiz high score</strong></p>
                    <p>Emma scored 95% on "World History" quiz</p>
                    <span class="activity-time">Yesterday</span>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                    <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="activity-details">
                    <p><strong>New lesson created</strong></p>
                    <p>Professor Chen added "Introduction to Python"</p>
                    <span class="activity-time">2 days ago</span>
                    </div>
                </div>
                </div>
            </div>
            </div>
        </div>
   </div>
</div>

<?php include 'include/footer.php'; ?>