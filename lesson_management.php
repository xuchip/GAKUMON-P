<?php
   session_start();

   $pageTitle = 'GAKUMON — Kanri Dashboard';
   $pageCSS = 'CSS/desktop/kanriStyle.css';
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

   include 'include/desktopKanriNav.php';
?>


<?php include 'include/footer.php'; ?>