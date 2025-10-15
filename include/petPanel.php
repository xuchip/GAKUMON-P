<div class="pet-panel">
    <div class="pet-header">
        <div class="pet-info">
            <div class="pet-type">
                <?php echo isset($petData['pet_name']) ? htmlspecialchars($petData['pet_name']) : 'No Pet'; ?>
            </div>
            <div class="pet-name">
                <?php echo isset($petData['custom_name']) ? htmlspecialchars($petData['custom_name']) : 'Unnamed'; ?>
            </div>
            <div class="pet-age">
                <?php echo isset($petData['days_old']) ? htmlspecialchars($petData['days_old']) . ' days' : '0 days'; ?>
            </div>
        </div>
        <a href="gakumon.php" style="text-decoration: none;" class="pet-level">PLAY</a>
    </div>
    
    <!-- GAKUCOINS DISPLAY - POSITIONED PROPERLY -->
    <div class="gakucoins-display">
        <?php 
        // Get gakucoins from session user data
        $gakucoins = 0;
        if (isset($_SESSION['sUser'])) {
            $username = $_SESSION['sUser'];
            $coinsStmt = $connection->prepare("SELECT gakucoins FROM tbl_user WHERE username = ?");
            $coinsStmt->bind_param("s", $username);
            $coinsStmt->execute();
            $coinsResult = $coinsStmt->get_result();
            
            if ($coinsRow = $coinsResult->fetch_assoc()) {
                $gakucoins = $coinsRow['gakucoins'];
            }
            $coinsStmt->close();
        }
        echo htmlspecialchars($gakucoins) . ' GAKUCOINS';
        ?>
    </div>
    
    <div class="pet-content">
        <div class="pet-image">
            <?php if (isset($petData['pet_name'])): ?>
                <img src="<?php echo htmlspecialchars($petData['image_url']); ?>" 
                    alt="<?php echo htmlspecialchars($petData['pet_name']); ?> Avatar">
            <?php else: ?>
                <img src="IMG/Pets/default.png" alt="No Pet">
            <?php endif; ?>
        </div>
    </div>
    
    <div class="energy-bar-container">
        <div class="energy-label">
            <div class="gakumonEnergy">Gakumon Energy</div>
            <div class="percent">
                <?php echo isset($petData['energy_level']) ? htmlspecialchars($petData['energy_level']) . '%' : '100%'; ?>
            </div>
        </div>
        <div class="energy-bar">
            <div class="energy-progress" 
                style="width: <?php echo isset($petData['energy_level']) ? htmlspecialchars($petData['energy_level']) : '100'; ?>%;">
            </div>
        </div>
    </div>
</div>