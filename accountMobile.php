<?php
session_start();
require_once 'config/config.php'; // Database Connection
// require_once 'config/checkAuth.php'; // Check authentication

// Get user data
$username = $_SESSION['sUser'] ?? 'slwyntlr';
$gakuCoins = $_SESSION['sGakuCoins'] ?? '0';
$userRole = $_SESSION['sUserRole'] ?? '';
$subscription = $_SESSION['sSubscription'] ?? '';

$pageTitle = 'Account - Gakusei';

// Resolve user id from session
$userID = null;
if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
$userID = (int)$_SESSION['user_id'];
} elseif (!empty($_SESSION['sUser'])) {
$u = $connection->prepare("SELECT user_id FROM tbl_user WHERE username = ? LIMIT 1");
$u->bind_param("s", $_SESSION['sUser']);
$u->execute();
$res = $u->get_result();
if ($res && ($row = $res->fetch_assoc())) $userID = (int)$row['user_id'];
$u->close();
}
// ✅ redirect if still not logged in
if ($userID === null) {
    header("Location: login.php");
    exit;
}

$petData = null;
   $petSql = "SELECT 
               p.pet_name,
               p.image_url,
               up.custom_name,
               up.created_at as pet_created_at,
               DATEDIFF(NOW(), up.created_at) as days_old,
               up.energy_level
            FROM tbl_user_pet up
            INNER JOIN tbl_pet p ON up.pet_id = p.pet_id
            WHERE up.user_id = $userID
            LIMIT 1";

   $petResult = $connection->query($petSql);

   if ($petResult && $petResult->num_rows > 0) {
      $petData = $petResult->fetch_assoc();
   }

// Mobile detection function
function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $mobileKeywords = [
        'mobile', 'android', 'silk', 'kindle', 'blackberry', 'iphone', 'ipod',
        'ipad', 'webos', 'symbian', 'windows phone', 'phone'
    ];
    
    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

$isMobile = isMobileDevice();

// CSS and JS includes
if ($isMobile) {
    $pageCSS = 'CSS/mobile/accountMobileStyle.css';
    // $pageJS = '../JS/mobile/accountMobileScript.js';
} else {
    $pageCSS = 'CSS/desktop/accountMobileStyle.css';
    $pageJS = 'JS/desktop/accountMobileScript.js';
}

include 'include/header.php';

// Get user data from database
if (isset($_SESSION['sUser'])) {
    $username = $_SESSION['sUser'];
    
    $stmt = $connection->prepare("SELECT user_id, role FROM tbl_user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userID = $row['user_id'];
        $_SESSION['sUserRole'] = $row['role'];
    }
}

// Include navigation
if ($isMobile) {
    include 'include/mobileNav.php';
} else {
    include 'include/desktopNav.php';
}
?>

<!-- Account Mobile Page -->
<div class="account-mobile-page">
    <div class="account-mobile-container">
        <!-- Header -->
        <div class="account-mobile-header">
            <div class="mobile-user-info">
                <i class="bi bi-person-circle mobile-user-icon"></i>
                <div class="mobile-user-details">
                    <div class="mobile-user-username"><?php echo htmlspecialchars($username); ?></div>
                    <div class="mobile-user-email"><?php echo $_SESSION['sEmail'] ?? 'user@example.com'; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Body -->
        <div class="account-mobile-body">
            <a href="faqs.php" class="account-mobile-item">
                <i class="bi bi-question-circle"></i>
                <span>FAQs</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            
            <a href="contactUs.php" class="account-mobile-item">
                <i class="bi bi-envelope"></i>
                <span>Contact Us</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            
            <?php if (!isset($_SESSION['sUserRole']) || $_SESSION['sUserRole'] !== 'Gakusensei'): ?>
                <a href="gakusensei.php" class="account-mobile-item">
                    <i class="bi bi-file-text"></i>
                    <span>Become one of us</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php endif; ?>

            <?php if (!isset($_SESSION['sSubscription']) || $_SESSION['sSubscription'] !== 'Premium'): ?>
                <a href="subscription.php" class="account-mobile-item">
                    <i class="bi bi-star"></i>
                    <span>Subscribe Now</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php else: ?>
                <a href="subscription.php" class="account-mobile-item premium-item">
                    <i class="bi bi-star-fill"></i>
                    <span>Premium Member</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php endif; ?>
            
            <!-- GakuCoins Display
            <div class="account-gakucoins-display">
                <div class="gakucoins-content">
                    <i class="bi bi-coin"></i>
                    <div class="gakucoins-details">
                        <span class="gakucoins-label">GakuCoins</span>
                        <span class="gakucoins-amount"><?php echo $gakuCoins; ?></span>
                    </div>
                </div>
            </div> -->
        </div>
        
        <!-- Footer -->
        <div class="account-mobile-footer">
            <a href="config/logoutAccount.php" class="account-logout-btn">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>