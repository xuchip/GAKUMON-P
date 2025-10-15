<?php
session_start();
require_once 'config/config.php'; // Database Connection

// Successful account creation message for confirmation (CAN REMOVE IF NOT APPEALING)
if (isset($_SESSION['success_message'])) {
    echo "<script>alert('" . addslashes($_SESSION['success_message']) . "');</script>";
    unset($_SESSION['success_message']);
}

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

// MOBILE or DESKTOP includes
if ($isMobile) {
    $pageCSS = 'CSS/mobile/subscriptionStyle.css';
    $pageJS = 'JS/mobile/subscriptionScript.js';
} else {
    $pageCSS = 'CSS/desktop/subscriptionStyle.css';
    $pageJS = 'JS/desktop/subscriptionScript.js';
}

include 'include/header.php';

if (isset($_SESSION['sUser'])) {
    $username = $_SESSION['sUser'];
    $email = $_SESSION['sEmail'] ?? '';

    $stmt = $connection->prepare("SELECT user_id, subscription_type FROM tbl_user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userID = $row['user_id'];
        $currentSubscription = $row['subscription_type'];
        $isPremium = ($currentSubscription === 'Premium');
       
        // Set the session variable for navigation
        $_SESSION['sSubscription'] = $currentSubscription;
    } else {
        echo "User not found.";
        exit;
    }
} else {
    echo "User not logged in.";
    header("Location: login.php");
    exit;
}

// Check if user just logged in (you might need to set this flag after successful login)
if (!isset($_SESSION['login_shown'])) {
    $_SESSION['login_shown'] = true;
    // This ensures the modal only shows once after login
}

// MOBILE or DESKTOP includes
if ($isMobile) {
    include 'include/mobileNav.php';
} else {
    include 'include/desktopNav.php';
}
?>

<?php if ($isMobile): ?>
    <!-- MOBILE LAYOUT FOR SUBSCRIPTION PAGE -->
    <div class="main-layout">
        <div class="content-area">
            <div class="container-fluid page-content">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-image">
                        <img src="IMG/Logos/logo_only_white.png" alt="Gakumon Premium">
                    </div>
                    <div class="hero-content">
                        <h1 class="hero-title">Upgrade to Premium</h1>
                        <p class="hero-subtitle">Get unlimited access to all features for only ₱179/month</p>
                    </div>
                </div>

                <!-- Current Status -->
                <div class="section">
                    <div class="status-card" style="background: <?php echo $isPremium ? 'linear-gradient(135deg, #811212 0%, #4C0707 100%)' : 'linear-gradient(135deg, #6c757d 0%, #495057 100%)'; ?>; color: white; padding: 30px; border-radius: 15px; text-align: center;">
                        <h2 style="font-family: 'SFpro_bold', sans-serif; font-size: 1.5rem; margin-bottom: 10px;">
                            Current Plan: <?php echo $isPremium ? 'PREMIUM' : 'FREE'; ?>
                        </h2>
                        <p style="font-family: 'SFpro_regular', sans-serif; font-size: 16px;">
                            <?php echo $isPremium ? 'You have full access to all premium features!' : 'Upgrade to unlock unlimited learning'; ?>
                        </p>
                    </div>
                </div>

                <!-- Free Plan -->
                <div class="section">
                    <div class="plan-card free-plan">
                        <div class="plan-header">
                            <h2 class="plan-title">Free</h2>
                            <div class="plan-price">
                                <span class="price-amount">₱0</span>
                                <span class="price-period">/month</span>
                            </div>
                        </div>
                       
                        <div class="benefits-grid">
                            <div class="benefit-card free-benefit">
                                <div class="benefit-icon"><i class="bi bi-check-lg"></i></div>
                                <div class="benefit-content">
                                    <h3>Basic Lessons</h3>
                                    <p class="benefit-text">Access to predefined lessons</p>
                                </div>
                            </div>
                            <div class="benefit-card free-benefit">
                                <div class="benefit-icon"><i class="bi bi-check-lg"></i></div>
                                <div class="benefit-content">
                                    <h3>Limited Quizzes</h3>
                                    <p class="benefit-text">Basic quiz attempts</p>
                                </div>
                            </div>
                        </div>
                       
                        <div class="button-container">
                            <?php if (!$isPremium): ?>
                                <button class="cta-button current-plan-btn" disabled>Current Plan</button>
                            <?php else: ?>
                                <button class="cta-button free-plan-btn" disabled>Free Plan</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Premium Plan -->
                <div class="section">
                    <div class="plan-card premium-plan">
                        <div class="plan-badge">MOST POPULAR</div>
                        <div class="plan-header">
                            <h2 class="plan-title">Premium</h2>
                            <div class="plan-price">
                                <span class="price-amount">₱179</span>
                                <span class="price-period">/month</span>
                            </div>
                        </div>
                       
                        <div class="benefits-grid">
                            <div class="benefit-card">
                                <div class="benefit-icon"><i class="bi bi-infinity"></i></div>
                                <div class="benefit-content">
                                    <h3>Unlimited Lessons</h3>
                                    <p class="benefit-text">Create unlimited lessons</p>
                                </div>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon"><i class="bi bi-robot"></i></div>
                                <div class="benefit-content">
                                    <h3>AI Quizzes</h3>
                                    <p class="benefit-text">Unlimited auto-generated quizzes</p>
                                </div>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon"><i class="bi bi-people-fill"></i></div>
                                <div class="benefit-content">
                                    <h3>Gakusensei Access</h3>
                                    <p class="benefit-text">Enroll in expert-created lessons</p>
                                </div>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon"><i class="bi bi-star-fill"></i></div>
                                <div class="benefit-content">
                                    <h3>No Ads</h3>
                                    <p class="benefit-text">Clean, ad-free experience</p>
                                </div>
                            </div>
                        </div>
                       
                        <div class="button-container">
                            <?php if ($isPremium): ?>
                                <button class="cta-button current-plan-btn" disabled>Current Plan</button>
                            <?php else: ?>
                                <button class="cta-button subscribe-btn" id="subscribeBtn">Subscribe Now</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="section FAQ">
                    <h2 class="section-title">Frequently Asked Questions</h2>
                    <div class="accordion" id="subscriptionFaqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="paymentHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentCollapse">
                                    What payment methods do you accept?
                                </button>
                            </h2>
                            <div id="paymentCollapse" class="accordion-collapse collapse" aria-labelledby="paymentHeading">
                                <div class="accordion-body">
                                    We accept credit/debit cards, GCash, Maya, and PayPal.
                                </div>
                            </div>
                        </div>
                       
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="billingHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#billingCollapse">
                                    How does billing work?
                                </button>
                            </h2>
                            <div id="billingCollapse" class="accordion-collapse collapse" aria-labelledby="billingHeading">
                                <div class="accordion-body">
                                    ₱179 billed monthly. Cancel anytime.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- DESKTOP LAYOUT FOR SUBSCRIPTION PAGE -->
    <div class="main-layout">
        <div class="content-area">
            <div class="container-fluid page-content">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-image">
                        <img src="IMG/Logos/logo_only_white.png" alt="Gakumon Premium">
                    </div>
                    <div class="hero-content">
                        <h1 class="hero-title">Upgrade to Premium</h1>
                        <p class="hero-subtitle">Get unlimited access to all features for only ₱179/month</p>
                    </div>
                </div>

                <!-- Current Status -->
                <div class="section">
                    <div class="status-card" style="background: <?php echo $isPremium ? 'linear-gradient(135deg, #811212 0%, #4C0707 100%)' : 'linear-gradient(135deg, #6c757d 0%, #495057 100%)'; ?>; color: white; padding: 30px; border-radius: 15px; text-align: center;">
                        <h2 style="font-family: 'SFpro_bold', sans-serif; font-size: 2rem; margin-bottom: 10px;">
                            Current Plan: <?php echo $isPremium ? 'PREMIUM' : 'FREE'; ?>
                        </h2>
                        <p style="font-family: 'SFpro_regular', sans-serif; font-size: 18px;">
                            <?php echo $isPremium ? 'You have full access to all premium features!' : 'Upgrade to unlock unlimited learning'; ?>
                        </p>
                    </div>
                </div>

                <!-- Pricing Cards -->
                <div class="two-column-section">
                    <!-- Free Plan -->
                    <div class="plan-card free-plan">
                        <div class="plan-header">
                            <h2 class="plan-title">Free</h2>
                            <div class="plan-price">
                                <span class="price-amount">₱0</span>
                                <span class="price-period">/month</span>
                            </div>
                        </div>
                       
                        <div class="benefits-grid">
                            <div class="benefit-card free-benefit">
                                <div class="benefit-icon"><i class="bi bi-check-lg"></i></div>
                                <div class="benefit-content">
                                    <h3>Basic Lessons</h3>
                                    <p class="benefit-text">Access to predefined lessons</p>
                                </div>
                            </div>
                            <div class="benefit-card free-benefit">
                                <div class="benefit-icon"><i class="bi bi-check-lg"></i></div>
                                <div class="benefit-content">
                                    <h3>Limited Quizzes</h3>
                                    <p class="benefit-text">Basic quiz attempts</p>
                                </div>
                            </div>
                        </div>
                       
                        <div class="button-container">
                            <?php if (!$isPremium): ?>
                                <button class="cta-button current-plan-btn" disabled>Current Plan</button>
                            <?php else: ?>
                                <button class="cta-button free-plan-btn" disabled>Free Plan</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Premium Plan -->
                    <div class="plan-card premium-plan">
                        <div class="plan-badge">MOST POPULAR</div>
                        <div class="plan-header">
                            <h2 class="plan-title">Premium</h2>
                            <div class="plan-price">
                                <span class="price-amount">₱179</span>
                                <span class="price-period">/month</span>
                            </div>
                        </div>
                       
                        <div class="benefits-grid">
                            <div class="benefit-card">
                                <div class="benefit-icon"><i class="bi bi-infinity"></i></div>
                                <div class="benefit-content">
                                    <h3>Unlimited Lessons</h3>
                                    <p class="benefit-text">Create unlimited lessons</p>
                                </div>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon"><i class="bi bi-robot"></i></div>
                                <div class="benefit-content">
                                    <h3>AI Quizzes</h3>
                                    <p class="benefit-text">Unlimited auto-generated quizzes</p>
                                </div>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon"><i class="bi bi-people-fill"></i></div>
                                <div class="benefit-content">
                                    <h3>Gakusensei Access</h3>
                                    <p class="benefit-text">Enroll in expert-created lessons</p>
                                </div>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon"><i class="bi bi-star-fill"></i></div>
                                <div class="benefit-content">
                                    <h3>No Ads</h3>
                                    <p class="benefit-text">Clean, ad-free experience</p>
                                </div>
                            </div>
                        </div>
                       
                        <div class="button-container">
                            <?php if ($isPremium): ?>
                                <button class="cta-button current-plan-btn" disabled>Current Plan</button>
                            <?php else: ?>
                                <button class="cta-button subscribe-btn" id="subscribeBtn">Subscribe Now</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="section">
                    <h2 class="section-title">Frequently Asked Questions</h2>
                    <div class="accordion" id="subscriptionFaqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="paymentHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentCollapse">
                                    What payment methods do you accept?
                                </button>
                            </h2>
                            <div id="paymentCollapse" class="accordion-collapse collapse" aria-labelledby="paymentHeading">
                                <div class="accordion-body">
                                    We accept credit/debit cards, GCash, Maya, and PayPal.
                                </div>
                            </div>
                        </div>
                       
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="billingHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#billingCollapse">
                                    How does billing work?
                                </button>
                            </h2>
                            <div id="billingCollapse" class="accordion-collapse collapse" aria-labelledby="billingHeading">
                                <div class="accordion-body">
                                    ₱179 billed monthly. Cancel anytime.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Payment Modal -->
<div class="custom-modal" id="subscriptionModal">
    <div class="custom-modal-backdrop"></div>
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
               <div class="modalCard-img"></div>
            </div>
            <div class="custom-modal-body">
                <div class="modal-lesson-content">
                    <h3 class="cardLesson-title">Subscribe to Premium</h3>
                    <p class="cardLesson-description">Complete your payment to unlock all features</p>
                   
                    <form method="post" action="" name="subscriptionForm" id="subscriptionForm">
                        <input type="hidden" name="user_id" value="<?php echo $userID; ?>">
                       
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <h4 style="font-family: 'SFpro_bold'; margin-bottom: 15px;">Order Summary</h4>
                            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ddd;">
                                <span>Premium Subscription</span>
                                <span>₱179.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 10px 0; font-family: 'SFpro_bold'; font-size: 18px;">
                                <span>Total</span>
                                <span>₱179.00</span>
                            </div>
                        </div>

                        <p class="text-muted mb-4">
                            You will be redirected to our secure payment partner where you can choose from multiple payment methods including:
                            <ul class="mt-2">
                                <li>Credit/Debit Cards</li>
                                <li>GCash</li>
                                <li>Maya</li>
                            </ul>
                        </p>

                        <div class="form-group row mt-4">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="termsAgree" id="termsAgree" required>
                                    <label class="form-check-label" for="termsAgree">
                                        I agree to the <a href="#" class="text-link">Terms and Conditions.</a>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- In the Payment Modal section, update the submit button -->
                        <div class="submitButton custom-modal-footer">
                            <button type="submit" name="processSubscriptionBtn" id="processSubscriptionBtn" class="btnSubmit btn btn-primary start-lesson-btn">
                                Pay ₱179.00
                            </button>
                            <button type="button" class="exitButton btn btn-secondary custom-modal-close-btn">x</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="subscriptionToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-check-circle text-success me-2"></i>
            <strong class="me-auto">Gakumon</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            Welcome to Premium! Your subscription is now active.
        </div>
    </div>
</div>

<script>
// run update_from_csv.php silently
window.addEventListener("DOMContentLoaded", () => {
    fetch("update_from_csv.php")
        .then(response => response.text())
        .then(data => console.log("CSV update triggered:", data))
        .catch(err => console.error("Error running update_from_csv:", err));
});
</script>

<?php include 'include/footer.php'; ?>