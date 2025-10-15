<?php
    session_start();

   $pageTitle = 'GAKUMON';
   $pageCSS = 'CSS/desktop/homepageStyle.css';
   $pageJS = 'JS/desktop/searchResultScript.js';

   include 'include/header.php';
   require_once 'config/config.php'; // Database Connection

   if(!isset($_SESSION['sUser'])) {
      header("Location: login.php");
      exit;
   }

    // Fetch lesson contents from database
    $lessons = [];
    $sql = "SELECT lesson_id, title, short_desc, duration, topic_id, difficulty_level FROM tbl_lesson";
    $result = $connection->query($sql);

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Fetch topic name
            $topicSql = "SELECT topic_name, topic_icon FROM tbl_topic WHERE topic_id = " . $row['topic_id'];
            $topicResult = $connection->query($topicSql);
            $topic = $topicResult->fetch_assoc();

            $lessons[] = [
            'id' => $row['lesson_id'],
            'title' => $row['title'],
            'short_desc' => $row['short_desc'],
            'duration' => $row['duration'],
            'topic' => $topic['topic_name'],
            'icon' => $topic['topic_icon'],
            'difficulty' => $row['difficulty_level']
            ];
        }
    }

   include 'include/desktopNav.php';
?>

<!-- Main layout with three columns -->
<div class="main-layout">
   <!-- Left navigation (already fixed by your CSS) -->
   
   <!-- Middle content area -->
   <div class="content-area">
      <!-- Search Bar at the top -->
      <div class="search-container">
        <form class="search-form" id="lessonSearchForm" autocomplete="off">
        <div class="input-group">
            <input type="text" class="form-control search-input" placeholder="Search GakuLessons" name="query" id="lessonSearchInput" aria-label="Search">

            <!-- Search Button -->
            <button class="searchbtn btn btn-search" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </div>
        </form>

         <div class="tabs-scroll">
            <div class="tab active" data-category="all">All Lessons</div>
            <div class="tab" data-category="inputdevices">Input Devices</div>
            <div class="tab" data-category="webprogramming">Web Programming</div>
            <div class="tab" data-category="js">JavaScript</div>
            <div class="tab" data-category="intro">Intro to Computing</div>
            <div class="tab" data-category="history">Computer History</div>
            <div class="tab" data-category="database">Databases</div>
            <div class="tab" data-category="network">Networking</div>
         </div>
      </div>

      <!-- Page content below the search bar -->
      <div class="container-fluid page-content">
         <div class="tabs-container">

            <div class="cards-container"> <!-- Added container for scrollable cards -->
                  <div class="cards-grid">
                     <!-- Cards will be dynamically loaded here -->
                  </div>
            </div>

            <div class="pagination">
                  <div class="page-item">
                     <div class="page-link prev"><i class="fas fa-chevron-left"></i></div>
                  </div>
                  <div class="page-item">
                     <div class="page-link active">1</div>
                  </div>
                  <div class="page-item">
                     <div class="page-link">2</div>
                  </div>
                  <div class="page-item">
                     <div class="page-link">3</div>
                  </div>
                  <div class="page-item">
                     <div class="page-link next"><i class="fas fa-chevron-right"></i></div>
                  </div>
            </div>
         </div>
      </div>
   </div>
   
   <?php include 'include/petPanel.php'; ?>
</div>

<!-- Custom Lesson Detail Modal -->
<div class="custom-modal" id="lessonModal">
    <div class="custom-modal-backdrop"></div>
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
               <div class="card-img">
                  <i class="fas ${lesson.icon}"></i>
               </div>
               <!-- <button type="button" class="custom-modal-close" aria-label="Close">
                  <i class="fas fa-times"></i>
               </button> -->
            </div>
            <div class="custom-modal-body">
                <div class="modal-lesson-content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="btn btn-secondary custom-modal-close-btn">Close</button>
                <button type="button" class="btn btn-primary start-lesson-btn">Start Lesson</button>
            </div>
        </div>
    </div>
</div>

<script>
   // Pass PHP lessons array to JS
   const lessons = {
      all: <?php echo json_encode($lessons); ?>,
      // You can filter by topic in JS if needed
   };
</script>

<?php include 'include/footer.php'; ?>