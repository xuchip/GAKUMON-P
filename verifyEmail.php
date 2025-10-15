<?php
    session_start();

    $pageTitle = 'GAKUMON — Verify Email';
    $pageCSS = 'CSS/desktop/loginStyle.css';
    $pageJS = 'JS/desktop/loginScript.js';

    include 'include/header.php';
    require_once 'config/config.php';    // Database Connection

    if (!isset($_SESSION['verification_email'])) {
    header("Location: signup.php");
    exit;
    }

    // Errors Array
    $errors = [];

    if(isset($_SESSION['sUser'])) {
        // Use JavaScript redirect with animation instead of header redirect
        echo '<script>navigateTo("homepage.php");</script>';
        exit;
    }

    // // Check if email is in session (user just signed up)
    // if(!isset($_SESSION['verification_email'])) {
    //     header("Location: signup.php");
    //     exit;
    // }

    if(isset($_POST['verify'])) {
        // Variables for User Input
        $digit1 = $_POST['digit1'] ?? '';
        $digit2 = $_POST['digit2'] ?? '';
        $digit3 = $_POST['digit3'] ?? '';
        $digit4 = $_POST['digit4'] ?? '';
        $digit5 = $_POST['digit5'] ?? '';
        $digit6 = $_POST['digit6'] ?? '';
        
        $verification_code = $digit1 . $digit2 . $digit3 . $digit4 . $digit5 . $digit6;
        $email = $_SESSION['verification_email'];

        // Validate code
        if(empty($verification_code) || strlen($verification_code) !== 6) {
            $errors[] = "Please enter a valid 6-digit verification code.";
        }

        // If no errors, proceed with verification
        if(empty($errors)) {
            // Check if code matches in pending verification table
            $stmt = $connection->prepare("SELECT * FROM tbl_pending_verif WHERE email_address = ? AND verif_code = ?");
            $stmt->bind_param("ss", $email, $verification_code);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows > 0) {
                // Get the user data from pending verification
                $pending_user = $result->fetch_assoc();
                
                // Insert into tbl_user
                $stmt = $connection->prepare("INSERT INTO tbl_user (first_name, last_name, username, email_address, pass, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("sssss", 
                    $pending_user['first_name'],
                    $pending_user['last_name'],
                    $pending_user['username'],
                    $pending_user['email_address'],
                    $pending_user['pass']
                );
                
                if($stmt->execute()) {
                    // Get the new user's ID
                    $user_id = $stmt->insert_id;
                    
                    // Delete from pending verification
                    $stmt = $connection->prepare("DELETE FROM tbl_pending_verif WHERE email_address = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    
                    // Set session variables
                    $_SESSION['sUserID'] = $user_id;
                    $_SESSION['sUser'] = $pending_user['username'];
                    $_SESSION['sEmail'] = $pending_user['email_address'];
                    
                    // Clear verification email from session
                    unset($_SESSION['verification_email']);
                    
                    // Redirect to topic selection
                    header("Location: topicSelection.php");
                    exit;
                } else {
                    $errors[] = "Error creating account. Please try again.";
                }
            } else {
                $errors[] = "Invalid verification code. Please try again.";
            }
            $stmt->close();
        }
    }
?>

<div class="transition-overlay"></div>
<div class="container page-contents">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="logo-container d-flex justify-content-center mb-4">
                <img src="IMG/Logos/logo_text_landscape_red.png" alt="Logo" class="logo img-fluid">
            </div>
            <div class="card">
                <div class="card-body">
                    <h2 class="text-center mb-4">Verify Your Email</h2>
                    <p class="text-center">We've sent a 6-digit verification code to your email address.</p>
                    
                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="verifyEmail.php" name="verify">
                        <div class="form-group row mt-3">
                            <div class="col-12">
                                <label for="verificationCode" class="form-label">Verification Code</label>
                                <div class="d-flex justify-content-between">
                                    <input type="text" class="form-control code-input text-center mx-1" name="digit1" id="digit1" maxlength="1" required autofocus>
                                    <input type="text" class="form-control code-input text-center mx-1" name="digit2" id="digit2" maxlength="1" required>
                                    <input type="text" class="form-control code-input text-center mx-1" name="digit3" id="digit3" maxlength="1" required>
                                    <input type="text" class="form-control code-input text-center mx-1" name="digit4" id="digit4" maxlength="1" required>
                                    <input type="text" class="form-control code-input text-center mx-1" name="digit5" id="digit5" maxlength="1" required>
                                    <input type="text" class="form-control code-input text-center mx-1" name="digit6" id="digit6" maxlength="1" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row mt-4">
                            <div class="col-12 submitButton">
                                <button type="submit" name="verify" class="btnSubmit btn btn-lg btn-primary w-100">Let's Go!</button>
                            </div>
                            <div class="col-12 text-center mt-3">
                                Didn't receive the code? <a href="#" class="text-link">Resend</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript to auto-advance between input fields
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.code-input');
    
    inputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            if (this.value.length === this.maxLength) {
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '') {
                if (index > 0) {
                    inputs[index - 1].focus();
                }
            }
        });
    });

    document.querySelector('form').addEventListener('paste', function(e) {
        const paste = e.clipboardData.getData('text');
        if (paste.length === 6) {
            inputs.forEach((input, index) => {
                input.value = paste.charAt(index); // Fill inputs with the pasted OTP digits
            });
        }
        e.preventDefault(); // Prevent default paste action to avoid issues
    });

});
</script>

<?php include 'include/footer.php'; ?>