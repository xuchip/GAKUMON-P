<?php
session_start();

$pageTitle = 'GAKUMON — Contact Us';
$pageCSS = 'CSS/desktop/contactUsStyle.css';
$pageJS = 'JS/desktop/contactUsScript.js';

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
                    <img src="IMG/Logos/logo_only_white.png" alt="Contact Gakumon">
                </div>
                <div class="hero-content">
                    <h1 class="hero-title">Contact Us</h1>
                    <p class="hero-subtitle">We're here to help with any questions</p>
                </div>
            </div>

            <!-- Contact Methods -->
            <div class="two-column-section">
                <!-- Contact Form -->
                <div class="contact-form-section">
                    <h2 class="section-title">Send us a Message</h2>
                    
                    <form method="post" action="include/processContact.inc.php" name="contactForm" id="contactForm">
                        <input type="hidden" name="user_id" value="<?php echo $userID; ?>">
                        
                        <div class="form-group row">
                            <div class="col-md-6 mb-3">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" name="firstName" id="firstName" 
                                    value="<?php echo htmlspecialchars($_SESSION['sFirstName'] ?? ''); ?>" 
                                    placeholder="Enter your first name" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="lastName" id="lastName"
                                    value="<?php echo htmlspecialchars($_SESSION['sLastName'] ?? ''); ?>"
                                    placeholder="Enter your last name" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-12 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" id="email"
                                    value="<?php echo htmlspecialchars($email); ?>"
                                    placeholder="Enter your email address" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-12 mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-control" name="subject" id="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="Technical Support">Technical Support</option>
                                    <option value="Billing Inquiry">Billing Inquiry</option>
                                    <option value="Feature Request">Feature Request</option>
                                    <option value="Bug Report">Bug Report</option>
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="Partnership">Partnership</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-12 mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" name="message" id="message" 
                                    rows="6" placeholder="Please describe your issue or question in detail..." required></textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-12 mb-3">
                                <label for="attachment" class="form-label">Attachment (Optional)</label>
                                <input type="file" class="form-control" name="attachment" id="attachment"
                                    accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                                <small class="form-text text-muted">You can attach screenshots or documents to help us understand your issue (Max 5MB)</small>
                            </div>
                        </div>

                        <div class="submitButton">
                            <button type="submit" name="submitContactBtn" class="btnSubmit btn btn-primary">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="contact-info-section">
                    <h2 class="section-title">Other Ways to Reach Us</h2>
                    
                    <div class="contact-methods">
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Email Us</h3>
                                <p>gakumonsupport@gmail.com</p>
                                <small>We typically respond within 24 hours</small>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Call Us</h3>
                                <p>+63 947 305-2449</p>
                                <small>Mon-Fri, 9AM-6PM PST</small>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Live Chat</h3>
                                <p>Available on our Social Media Pages</p>
                                <small>Mon-Fri, 9AM-5PM PST</small>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Visit Us</h3>
                                <p>Lyceum Subic Bay Inc.</p>
                                <small>Subic Bay, Philippines</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <h3>Follow Us</h3>
                        <div class="social-icons">
                            <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Quick Link -->
            <div class="section">
                <div class="faq-quicklink">
                    <div class="faq-quicklink-content">
                        <h3>Check our FAQs first</h3>
                        <p>Many common questions are already answered in our Frequently Asked Questions section.</p>
                        <a href="faqs.php" class="cta-button faq-button">Browse FAQs</a>
                    </div>
                </div>
            </div>
        </div>
   </div>
</div>

<!-- Success Modal -->
<div class="custom-modal" id="contactSuccessModal">
    <div class="custom-modal-backdrop"></div>
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
               <div class="modalCard-img"></div>
            </div>
            <div class="custom-modal-body">
                <div class="modal-lesson-content">
                    <div class="enrollment-prompt">
                        <div class="alertTitle text-center">Message Sent!</div>
                        <p class="alertCaption text-center mb-4">Thank you for contacting us. We've received your message and will get back to you within 24 hours.</p>
                    </div>
                </div>
            </div>
            <div class="submitButton custom-modal-footer">
                <button type="button" class="btnSubmit btn btn-primary success-ok-btn">OK</button>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>