document.addEventListener('DOMContentLoaded', function() {
    const petOptions = document.querySelectorAll('.pet-option');
    const selectedPetInput = document.getElementById('selectedPetInput');
    const confirmButton = document.getElementById('confirmButton');
    const letsGoButton = document.getElementById('letsGoButton');
    const petForm = document.getElementById('petForm');
    const petDescription = document.getElementById('petDescription');
    const selectedPetName = document.getElementById('selectedPetName');
    const finalConfirmButton = document.getElementById('finalConfirmButton');
    const nameInputContainer = document.getElementById('nameInputContainer');
    const petNameInput = document.getElementById('petName');
    const petsRow = document.querySelector('.pets-row');
    
    // Add click event to each pet option
    petOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            petOptions.forEach(o => o.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Store the selected pet ID
            const selectedPetId = this.getAttribute('data-pet-id');
            selectedPetInput.value = selectedPetId;
            
            // Get the pet name for display
            const petNameElement = this.querySelector('.pet-name');
            const petName = petNameElement ? petNameElement.textContent : 'this pet';
            
            // Update description
            petDescription.textContent = `You've selected ${petName} as your companion.`;
            
            // Show the confirm button
            confirmButton.style.display = 'block';
            letsGoButton.style.display = 'none';
            
            // Reset name input and hide it
            petNameInput.value = '';
            nameInputContainer.style.display = 'none';
            
            // Show all pets again (in case they were hidden previously)
            petOptions.forEach(pet => {
                pet.style.display = 'block';
                pet.style.transform = 'translateX(0)';
            });
        });
    });
    
    // Confirm button click handler
    confirmButton.addEventListener('click', function() {
        // Update modal with selected pet name
        const selectedOption = document.querySelector('.pet-option.selected');
        if (selectedOption) {
            const petNameElement = selectedOption.querySelector('.pet-name');
            const petName = petNameElement ? petNameElement.textContent : 'this pet';
            selectedPetName.textContent = petName;
        }
    });
    
    // Final confirmation button in modal
    finalConfirmButton.addEventListener('click', function() {
        // Hide the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        modal.hide();
        
        // Hide all non-selected pets
        const selectedPet = document.querySelector('.pet-option.selected');
        const otherPets = document.querySelectorAll('.pet-option:not(.selected)');
        
        otherPets.forEach(pet => {
            pet.style.display = 'none';
        });
        
        // Move selected pet to the right - FIXED animation
        selectedPet.style.transition = 'transform 0.5s ease';
        
        // Reset any scale transformation that might have been applied
        selectedPet.style.transform = 'translateX(50px) scale(1)';
        
        // Show name input container on the left
        nameInputContainer.style.display = 'block';
        
        // Hide confirm button
        confirmButton.style.display = 'none';
        
        // Focus on the name input
        setTimeout(() => {
            petNameInput.focus();
        }, 300);
    });
    
    // Event listener for name input
    petNameInput.addEventListener('input', function() {
        // Show the final submit button when user starts typing
        if (this.value.trim() !== '') {
            letsGoButton.style.display = 'block';
        } else {
            letsGoButton.style.display = 'none';
        }
    });
    
    // Form submission
    petForm.addEventListener('submit', function(e) {
        if (!selectedPetInput.value) {
            e.preventDefault();
            alert('Please select a pet companion.');
            return;
        }
        
        if (!petNameInput.value.trim()) {
            e.preventDefault();
            alert('Please give your pet a name.');
            petNameInput.focus();
        }
    });
    
    // Initially hide the buttons
    confirmButton.style.display = 'none';
    letsGoButton.style.display = 'none';
});