<?php
session_start();

$pageTitle = 'GAKUMON — FAQs';
$pageCSS = 'CSS/desktop/faqsStyle.css';
$pageJS = 'JS/desktop/faqsScript.js';

include 'include/header.php';
require_once 'config/config.php';

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
}

include 'include/desktopNav.php';
?>

<div class="main-layout">
   <div class="content-area">
        <div class="container-fluid page-content">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="hero-image">
                    <img src="IMG/Logos/logo_only_white.png" alt="Gakumon FAQs">
                </div>
                <div class="hero-content">
                    <h1 class="hero-title">FAQs</h1>
                    <p class="hero-subtitle">Find answers to common questions</p>
                </div>
            </div>

            <!-- FAQ Categories -->
            <div class="section">
                <div class="faq-categories">
                    <div class="category-tab active" data-category="general">General</div>
                    <div class="category-tab" data-category="subscription">Subscription</div>
                    <div class="category-tab" data-category="lessons">Lessons</div>
                    <div class="category-tab" data-category="technical">Technical</div>
                </div>
            </div>

            <!-- FAQ Accordion -->
            <div class="section">
                <div class="faq-container">
                    <!-- General FAQs -->
                    <div class="faq-category active" id="general-faqs">
                        <h2 class="section-title">General Questions</h2>
                        
                        <div class="accordion" id="generalAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="generalHeading1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#generalCollapse1">
                                        What is Gakumon?
                                    </button>
                                </h2>
                                <div id="generalCollapse1" class="accordion-collapse collapse show" aria-labelledby="generalHeading1">
                                    <div class="accordion-body">
                                        Gakumon is an interactive learning platform that combines educational content with gamification elements like virtual pets to make learning more engaging and enjoyable.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="generalHeading2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#generalCollapse2">
                                        Is Gakumon free to use?
                                    </button>
                                </h2>
                                <div id="generalCollapse2" class="accordion-collapse collapse" aria-labelledby="generalHeading2">
                                    <div class="accordion-body">
                                        Yes! Gakumon offers a free plan with access to basic lessons and limited features. You can upgrade to Premium for unlimited access to all features.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="generalHeading3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#generalCollapse3">
                                        What age group is Gakumon for?
                                    </button>
                                </h2>
                                <div id="generalCollapse3" class="accordion-collapse collapse" aria-labelledby="generalHeading3">
                                    <div class="accordion-body">
                                        Gakumon is designed for learners of all ages, but it's particularly effective for students and young professionals looking to expand their knowledge in various subjects.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="generalHeading4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#generalCollapse4">
                                        How do I get started with Gakumon?
                                    </button>
                                </h2>
                                <div id="generalCollapse4" class="accordion-collapse collapse" aria-labelledby="generalHeading4">
                                    <div class="accordion-body">
                                        Simply create an account, choose your interests, and start exploring lessons! You can begin with our recommended lessons or search for specific topics that interest you.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription FAQs -->
                    <div class="faq-category" id="subscription-faqs">
                        <h2 class="section-title">Subscription & Payment</h2>
                        
                        <div class="accordion" id="subscriptionAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="subscriptionHeading1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#subscriptionCollapse1">
                                        What payment methods do you accept?
                                    </button>
                                </h2>
                                <div id="subscriptionCollapse1" class="accordion-collapse collapse" aria-labelledby="subscriptionHeading1">
                                    <div class="accordion-body">
                                        We accept credit/debit cards, GCash, Maya, and PayPal for subscription payments.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="subscriptionHeading2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#subscriptionCollapse2">
                                        How does billing work?
                                    </button>
                                </h2>
                                <div id="subscriptionCollapse2" class="accordion-collapse collapse" aria-labelledby="subscriptionHeading2">
                                    <div class="accordion-body">
                                        Premium subscription costs ₱179 per month, billed monthly. You can cancel anytime before your next billing cycle.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="subscriptionHeading3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#subscriptionCollapse3">
                                        Can I cancel my subscription?
                                    </button>
                                </h2>
                                <div id="subscriptionCollapse3" class="accordion-collapse collapse" aria-labelledby="subscriptionHeading3">
                                    <div class="accordion-body">
                                        Yes, you can cancel your Premium subscription at any time. After cancellation, you'll retain Premium access until the end of your current billing period.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="subscriptionHeading4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#subscriptionCollapse4">
                                        What's the difference between Free and Premium?
                                    </button>
                                </h2>
                                <div id="subscriptionCollapse4" class="accordion-collapse collapse" aria-labelledby="subscriptionHeading4">
                                    <div class="accordion-body">
                                        Free users get access to basic lessons and limited quizzes. Premium users enjoy unlimited lessons, AI-generated quizzes, Gakusensei access, and an ad-free experience.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lessons FAQs -->
                    <div class="faq-category" id="lessons-faqs">
                        <h2 class="section-title">Lessons & Learning</h2>
                        
                        <div class="accordion" id="lessonsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="lessonsHeading1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#lessonsCollapse1">
                                        How do I enroll in a lesson?
                                    </button>
                                </h2>
                                <div id="lessonsCollapse1" class="accordion-collapse collapse" aria-labelledby="lessonsHeading1">
                                    <div class="accordion-body">
                                        Simply click on any lesson card from the homepage, then click the "Enroll" button in the lesson details modal. Once enrolled, you can access all lesson materials.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="lessonsHeading2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#lessonsCollapse2">
                                        Can I create my own lessons?
                                    </button>
                                </h2>
                                <div id="lessonsCollapse2" class="accordion-collapse collapse" aria-labelledby="lessonsHeading2">
                                    <div class="accordion-body">
                                        Yes! Premium users can create unlimited lessons. Free users have limited creation capabilities. Gakusensei users can also publish lessons for other users.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="lessonsHeading3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#lessonsCollapse3">
                                        How do quizzes work?
                                    </button>
                                </h2>
                                <div id="lessonsCollapse3" class="accordion-collapse collapse" aria-labelledby="lessonsHeading3">
                                    <div class="accordion-body">
                                        After completing a lesson, you can take a quiz to test your knowledge. Premium users get AI-generated quizzes with unlimited attempts, while free users have limited quiz access.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="lessonsHeading4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#lessonsCollapse4">
                                        What are Gakusensei lessons?
                                    </button>
                                </h2>
                                <div id="lessonsCollapse4" class="accordion-collapse collapse" aria-labelledby="lessonsHeading4">
                                    <div class="accordion-body">
                                        Gakusensei lessons are created by expert educators and professionals. These premium lessons offer in-depth knowledge and specialized content not available in regular lessons.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Technical FAQs -->
                    <div class="faq-category" id="technical-faqs">
                        <h2 class="section-title">Technical Support</h2>
                        
                        <div class="accordion" id="technicalAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="technicalHeading1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#technicalCollapse1">
                                        What should I do if I'm having technical issues?
                                    </button>
                                </h2>
                                <div id="technicalCollapse1" class="accordion-collapse collapse" aria-labelledby="technicalHeading1">
                                    <div class="accordion-body">
                                        If you're experiencing technical issues, try refreshing the page first. If problems persist, clear your browser cache or try a different browser. For persistent issues, contact our support team through the Contact Us page.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="technicalHeading2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technicalCollapse2">
                                        Is my data secure?
                                    </button>
                                </h2>
                                <div id="technicalCollapse2" class="accordion-collapse collapse" aria-labelledby="technicalHeading2">
                                    <div class="accordion-body">
                                        Yes, we take data security seriously. All user data is encrypted and stored securely. We never share your personal information with third parties without your consent.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="technicalHeading3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technicalCollapse3">
                                        What browsers are supported?
                                    </button>
                                </h2>
                                <div id="technicalCollapse3" class="accordion-collapse collapse" aria-labelledby="technicalHeading3">
                                    <div class="accordion-body">
                                        Gakumon works best on modern browsers like Chrome, Firefox, Safari, and Edge. We recommend using the latest version of your browser for optimal performance.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="technicalHeading4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technicalCollapse4">
                                        Can I use Gakumon on mobile devices?
                                    </button>
                                </h2>
                                <div id="technicalCollapse4" class="accordion-collapse collapse" aria-labelledby="technicalHeading4">
                                    <div class="accordion-body">
                                        Yes! Gakumon is fully responsive and works on smartphones and tablets. You can access all features through your mobile browser.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Still Need Help Section -->
            <div class="section">
                <div class="help-section">
                    <div class="help-content">
                        <h3 class="help-title">Still need help?</h3>
                        <p class="help-description">Can't find the answer you're looking for? Our support team is here to help.</p>
                        <a href="contactUs.php" class="cta-button help-button">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
   </div>
</div>

<?php include 'include/footer.php'; ?>