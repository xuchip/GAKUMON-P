<?php
    // Get the current page name to determine active nav item
    $currentPage = basename($_SERVER['PHP_SELF']);
    $activeHome = '';
    $activeLessons = '';
    $activeQuizzes = '';
    $activeSensei = '';
    $activeAccount = '';

    // Set active class based on current page
    switch($currentPage) {
        case 'homepage.php':
            $activeHome = 'active';
            break;
        case 'lessons.php':
            $activeLessons = 'active';
            break;
        case 'quizzes.php':
            $activeQuizzes = 'active';
            break;
        case 'sensei.php':
            $activeSensei = 'active';
            break;
        case 'accountMobile.php':
            $activeAccount = 'active';
            break;
    }
?>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav">
    <!-- Navigation Items -->
    <div class="mobile-nav-items">
        <a href="homepage.php" class="mobile-nav-item <?php echo $activeHome; ?>">
            <i class="bi bi-house-door-fill"></i>
            <span class="mobile-nav-text">Home</span>
        </a>
        
        <a href="lessons.php" class="mobile-nav-item <?php echo $activeLessons; ?>">
            <i class="bi bi-book-half"></i>
            <span class="mobile-nav-text">Lessons</span>
        </a>
        
        <!-- Pet Dome in the middle -->
        <div class="mobile-pet-dome-nav">
            <?php if (isset($petData['pet_name'])): ?>
                <a href="gakumon.php" class="pet-dome-link">
                    <img src="<?php echo htmlspecialchars($petData['image_url']); ?>" 
                        alt="<?php echo htmlspecialchars($petData['pet_name']); ?>" 
                        class="pet-dome-image contain"> <!-- Use 'contain' class -->
                </a>
            <?php else: ?>
                <a href="gakumon.php" class="pet-dome-link">
                    <img src="IMG/Pets/default.png" alt="No Pet" class="pet-dome-image contain">
                </a>
            <?php endif; ?>
        </div>
        
        <a href="quizzes.php" class="mobile-nav-item <?php echo $activeQuizzes; ?>">
            <i class="bi bi-lightbulb-fill"></i>
            <span class="mobile-nav-text">Quizzes</span>
        </a>
        
        <!-- Account Button - Now redirects to accountMobile.php -->
        <a href="accountMobile.php" class="mobile-nav-item <?php echo $activeAccount; ?>">
            <i class="bi bi-person-circle"></i>
            <span class="mobile-nav-text">Account</span>
        </a>
    </div>
</nav>