<?php
// This file contains the navigation bar for the admin dashboard.
?>
<!-- Left Navigation Column -->
<stylesheet
<nav class="side-navbar">
    <div class="nav-logo">
        <img src="IMG/Logos/logo_text_portrait_white.png" alt="GAKUMON Logo">
    </div>
    <div class="nav-items">
        <a href="#dashboard" class="nav-item active" data-section="dashboard">
            <i class="bi bi-speedometer2"></i>
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="#user-management" class="nav-item" data-section="user-management">
            <i class="bi bi-people"></i>
            <span class="nav-text">User Management</span>
        </a>
        <a href="#lesson-management" class="nav-item" data-section="lesson-management">
            <i class="bi bi-journal-text"></i>
            <span class="nav-text">Lesson Management</span>
        </a>
        <a href="#quiz-management" class="nav-item" data-section="quiz-management">
            <i class="bi bi-question-circle"></i>
            <span class="nav-text">Quiz Management</span>
        </a>
        <a href="#creator-management" class="nav-item" data-section="creator-management">
            <i class="bi bi-person-check"></i>
            <span class="nav-text">Creator Management</span>
        </a>
        <a href="#shop-management" class="nav-item" data-section="shop-management">
            <i class="bi bi-shop"></i>
            <span class="nav-text">Shop Management</span>
        </a>
        <a href="#system-management" class="nav-item" data-section="system-management">
            <i class="bi bi-gear"></i>
            <span class="nav-text">System Management</span>
        </a>
    </div>
    <div class="account-section">
        <button class="account-btn" id="accountDropdownBtn">
            <i class="bi bi-person-circle account"></i>
        </button>
        <div class="account-dropdown" id="accountDropdown">
            <div class="dropdown-user-info">
                <div class="dropdown-username"><?php echo htmlspecialchars($username); ?></div>
                <div class="dropdown-email"><?php echo htmlspecialchars($userEmail); ?></div>
            </div>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a href="#" class="dropdown-item">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="dropdown-logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>