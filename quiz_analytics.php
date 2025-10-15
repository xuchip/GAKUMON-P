<?php
   session_start();

   $pageTitle = 'GAKUMON — Quiz Analytics';
   $pageCSS = 'CSS/desktop/kanriDBStyle.css';
   $pageJS = 'JS/desktop/kanriDashboardScript.js';

   include 'include/header.php';

   if (isset($_SESSION['sUser'])) {
      $username = $_SESSION['sUser'];
   } else {
      echo "User not logged in.";
      header("Location: login.php");
      exit;
   }

   include 'include/desktopKanriNav.php';
?>

<!-- Main layout with three columns -->
<div class="main-layout">
   <!-- Left navigation (already fixed by your CSS) -->
    <div class="content-area">
        <div class="page-content">
            <div class="card account-card">
                <div class="card-header">
                    <h2 class="quiz-analytics">QUIZ ANALYTICS</h2>
                </div>

                <div class="card-body activity-logs-dashboard">
                    <!-- Stats Cards - First Row -->
                    <div class="activity-stats">
                        <div class="activity-stat-card">
                            <div class="activity-stat-content">
                                <h3>--</h3>
                                <p>New Quiz</p>
                            </div>
                        </div>
                        
                        <div class="activity-stat-card">
                            <div class="activity-stat-content">
                                <h3>--</h3>
                                <p>Reports</p>
                            </div>
                        </div>
                        
                        <div class="activity-stat-card">
                            <div class="activity-stat-content">
                                <h3>--</h3>
                                <p>Engagement</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards - Second Row -->
                    <div class="activity-stats">
                        <div class="activity-stat-card">
                            <div class="activity-stat-content">
                                <h3>--</h3>
                                <p>New Downloads</p>
                            </div>
                        </div>
                        
                        <div class="activity-stat-card">
                            <div class="activity-stat-content">
                                <h3>--</h3>
                                <p>Reports</p>
                            </div>
                        </div>
                        
                        <div class="activity-stat-card">
                            <div class="activity-stat-content">
                                <h3>--</h3>
                                <p>Feedbacks</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>