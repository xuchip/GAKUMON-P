document.addEventListener('DOMContentLoaded', function() {
    // Start the real-time energy updater
    startEnergyDecay();
});

function startEnergyDecay() {
    console.log('Energy decay system started');
    // Call update immediately on page load
    updateEnergyFromServer();
   
    // Then set up interval for periodic updates
    setInterval(updateEnergyFromServer, 10 * 1000); // check every 1 minute
}

async function updateEnergyFromServer() {
    try {
        const res = await fetch('include/updateEnergy.inc.php', {
            credentials: 'same-origin',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        });
        const data = await res.json();
        if (data.ok) {
            updateEnergyUI(data.energy);
        } else {
            console.error(data.error);
        }
    } catch (err) {
        console.error('Error updating energy:', err);
    }
}

// Function to update the energy UI elements
function updateEnergyUI(energy) {
    // Update the energy progress bar width
    const energyProgress = document.querySelector('.energy-progress');
    if (energyProgress) {
        energyProgress.style.width = `${energy}%`;
    }
    
    // Update the energy percentage text
    const energyPercent = document.querySelector('.percent');
    if (energyPercent) {
        energyPercent.textContent = `${Math.round(energy)}%`;
    }
    
    console.log(`Energy updated to: ${energy}%`);
}