<?php
    session_start();


    $pageTitle = 'GAKUMON — Login';
    $pageCSS = 'CSS/desktop/loginStyle.css';
    $pageJS = 'JS/desktop/loginScript.js';


    include 'include/header.php';
    require_once 'config/config.php';    // Database Connection


    // Errors Array
    $errors = [];


    if(isset($_SESSION['sUser'])) {
        // Use JavaScript redirect with animation instead of header redirect
        echo '<script>navigateTo("homepage.php");</script>';
        exit;
    }


    if(isset($_POST['login'])) {
        // Variables for User Input
        $username = $connection->real_escape_string($_POST['username']);
        $pass = $_POST['password'];


        // Validate username and password
        if(empty($username)) {
            $errors[] = "Username is required.";
        }
        if(empty($pass)) {
            $errors[] = "Password is required.";
        }


        // If no errors, proceed with login
        if(empty($errors)) {
            // Prepare Statement
            $stmt = $connection->prepare("SELECT * FROM tbl_user WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();


            if($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                // Verify password
                if(password_verify($pass, $user['pass'])) {
                    $_SESSION['sUser'] = $user['username']; // Store username in Session
                    $_SESSION['sEmail'] = $user['email_address']; // Store email in Session
                    $_SESSION['sUserID'] = $user['user_id']; // Store user ID in Session
                    $_SESSION['sRole'] = $user['role']; // ✅ store role in session


                    // ✅ Redirect based on role
                    if ($user['role'] === 'Kanri') {
                        header("Location: admin_dashboard.php");
                        exit;
                    } elseif ($user['role'] === 'Gakusensei') {
                        header("Location: homepage.php");
                        exit;
                    } else {
                        header("Location: homepage.php");
                        exit;
                    }
                } else {
                    $errors[] = "Invalid username or password.";
                }
            } else {
                $errors[] = "Invalid username or password.";
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
                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                   
                    <form method="post" action="login.php" name="login">
                        <div class="form-group row mt-3">
                            <div class="col-12">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" id="username"
                                    placeholder="Enter username" required
                                    title="Enter your username">
                            </div>
                        </div>


                        <div class="form-group row mt-3">
                            <div class="col-12">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password"
                                        placeholder="Enter password" required
                                        title="Enter your password">


                                        <!-- Eye Button -->
                                        <button class="eyebtn btn btn-outline-secondary" type="button" onclick="togglePassword('password', 'toggleIcon')">
                                            <i id="toggleIcon" class="fas fa-eye"></i>
                                        </button>
                                </div>
                            </div>
                        </div>


                        <div class="form-group row mt-4">
                            <div class="col-12 submitButton">
                                <button type="submit" name="login" class="btnSubmit btn btn-lg btn-primary w-100">Let's Go!</button>
                            </div>
                            <div class="col-12 text-center mt-3 signupNote">
                                Don't have an account? <a href="signup.php" class="text-link">Sign up</a>
                            </div>
            </div>
        </div>
    </div>
</div>


<?php include 'include/footer.php'; ?>
