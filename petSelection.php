<?php
    session_start();

    $pageTitle = 'GAKUMON — Pet Selection';
    $pageCSS = 'CSS/desktop/petSelectionStyle.css';
    $pageJS = 'JS/desktop/petSelectionScript.js';

    include 'include/header.php';
    require_once 'config/config.php';    // Database Connection

    // Check if user is logged in
    if(!isset($_SESSION['sUser'])) {
        header("Location: login.php");
        exit;
    }

    // Get user ID from session
    $user_id = $_SESSION['sUserID'];

    // Errors Array
    $errors = [];

    // Fetch all pets from database
    $pets = [];
    $stmt = $connection->prepare("SELECT pet_id, pet_name, image_url FROM tbl_pet ORDER BY pet_name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }
    $stmt->close();

    // Process form submission
    if(isset($_POST['save_pet'])) {
    if(isset($_POST['selected_pet']) && !empty($_POST['selected_pet'])) {
        $selected_pet = $_POST['selected_pet'];
        $pet_name = isset($_POST['pet_name']) ? trim($_POST['pet_name']) : '';
        
        if(empty($pet_name)) {
            $errors[] = "Please give your pet a name.";
        } else {
            // Check if a record exists for this user
            $stmt = $connection->prepare("SELECT user_id FROM tbl_user_pet WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->store_result();
            
            if($stmt->num_rows > 0) {
                // Record exists, update it
                $stmt->close();
                $stmt = $connection->prepare("UPDATE tbl_user_pet SET pet_id = ?, custom_name = ? WHERE user_id = ?");
                $stmt->bind_param("isi", $selected_pet, $pet_name, $user_id);
            } else {
                // No record, insert new
                $stmt->close();
                $stmt = $connection->prepare("INSERT INTO tbl_user_pet (user_id, pet_id, custom_name) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $user_id, $selected_pet, $pet_name);
            }
            
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Account created successfully!";
                header("Location: homepage.php");
                exit;
            } else {
                $errors[] = "There was an error saving your pet selection. Please try again.";
            }
            
            $stmt->close();
            
        }
    } else {
        $errors[] = "Please select a pet companion.";
    }
}
?>

<div class="transition-overlay"></div>
<div class="container page-contents">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="logo-container d-flex justify-content-center mb-4">
                <img src="IMG/Logos/logo_text_landscape_red.png" alt="Logo" class="logo img-fluid">
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="card-title text-center">Choose Your Pet Companion</div>
                    <p class="caption text-center mb-4">Select a loyal friend to accompany you on your learning journey</p>
                    
                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="petSelection.php" id="petForm">
                        <div class="pets-row d-flex justify-content-center mb-4">
                            <?php foreach($pets as $pet): ?>
                                <div class="pet-option" data-pet-id="<?php echo $pet['pet_id']; ?>">
                                    <div class="pet-img">
                                        <img src="<?php echo htmlspecialchars($pet['image_url']); ?>" alt="<?php echo htmlspecialchars($pet['pet_name']); ?>" class="pet-image">
                                    </div>
                                    <div class="pet-info">
                                        <h3 class="pet-name"><?php echo htmlspecialchars($pet['pet_name']); ?></h3>
                                    </div>
                                    <div class="selected-indicator">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="pet-description-container text-center mb-4">
                            <p id="petDescription" class="pet-description">Select a pet to see its description</p>
                        </div>

                        <input type="hidden" name="selected_pet" id="selectedPetInput">

                        <!-- Name input container (initially hidden) -->
                        <div id="nameInputContainer" class="name-input-container" style="display: none;">
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="petName" class="form-label">Name Your Pet</label>
                                        <input type="text" class="form-control" id="petName" name="pet_name" 
                                            placeholder="Enter a name for your pet" required>
                                        <div class="form-text">Give your new companion a special name!</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row mt-4">
                            <div class="col-12 submitButton">
                                <button type="button" class="btnSubmit btn btn-lg btn-primary w-100" id="confirmButton" style="display: none;" data-bs-toggle="modal" data-bs-target="#confirmationModal">Confirm Selection</button>
                                <button type="submit" name="save_pet" class="btnSubmit btn btn-lg btn-success w-100" id="letsGoButton" style="display: none;">Let's Go!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="custom-modal-backdrop"></div>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="custom-modal-header">
                <div class="modalCard-img">
                    <!-- <i class="fas fa-graduation-cap"></i> -->
                </div>
            </div>

            <div class="modal-body">
                <div class="enrollment-prompt">
                    <div class="alertTitle text-center">Confirm Pet?</div>
                    <p class="alertCaption text-center mb-4">Are you sure you want to choose <span id="selectedPetName" class="fw-bold">this pet</span> as your companion?</p>
                </div>
            </div>

            <div class="submitButton modal-footer">
                <button type="button" class="modalBtnSubmit btn btn-primary" id="finalConfirmButton">Yes, I'm Sure!</button>
                <button type="button" class="exitButton btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>