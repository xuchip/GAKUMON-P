// ===== NAVIGATION DROPDOWN FUNCTIONALITY =====
// Toggle dropdown visibility
document.getElementById('accountDropdownBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('accountDropdown');
    dropdown.classList.toggle('show');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('accountDropdown');
    const accountBtn = document.getElementById('accountDropdownBtn');
    if (!dropdown.contains(e.target) && e.target !== accountBtn && !accountBtn.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// ===== CONTACT FORM FUNCTIONALITY =====
document.addEventListener('DOMContentLoaded', function() {
    // Form submission handling
    const contactForm = document.getElementById('contactForm');
    const successModal = document.getElementById('contactSuccessModal');
    const successOkBtn = document.querySelector('.success-ok-btn');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic form validation
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value.trim();
            
            if (!firstName || !lastName || !email || !subject || !message) {
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[name="submitContactBtn"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Create FormData object to handle file uploads
            const formData = new FormData(contactForm);
            
            // Send AJAX request
            fetch('include/processContact.inc.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                if (data.success) {
                    // Show success modal
                    if (successModal) {
                        successModal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                    // Reset form
                    contactForm.reset();
                } else {
                    // Show error message
                    if (data.errors) {
                        alert(data.errors.join('\n'));
                    } else {
                        alert(data.message || 'Sorry, there was an error sending your message.');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                alert('Sorry, there was an error sending your message. Please try again.');
            });
        });
    }
    
    // Success modal close functionality
    if (successOkBtn) {
        successOkBtn.addEventListener('click', function() {
            if (successModal) {
                successModal.classList.remove('active');
                document.body.style.overflow = 'auto';
                // Reset form
                if (contactForm) {
                    contactForm.reset();
                }
            }
        });
    }
    
    // Close modal with backdrop click
    const modalBackdrop = document.querySelector('#contactSuccessModal .custom-modal-backdrop');
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', function() {
            successModal.classList.remove('active');
            document.body.style.overflow = 'auto';
            if (contactForm) {
                contactForm.reset();
            }
        });
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && successModal && successModal.classList.contains('active')) {
            successModal.classList.remove('active');
            document.body.style.overflow = 'auto';
            if (contactForm) {
                contactForm.reset();
            }
        }
    });
    
    // File attachment validation
    const attachmentInput = document.getElementById('attachment');
    if (attachmentInput) {
        attachmentInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Check file size (5MB limit)
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB.');
                    this.value = ''; // Clear the input
                    return;
                }
                
                // Check file type
                const allowedTypes = [
                    'image/jpeg', 
                    'image/jpg', 
                    'image/png', 
                    'image/gif', 
                    'application/pdf', 
                    'application/msword', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid file type (JPG, PNG, GIF, PDF, DOC, DOCX).');
                    this.value = ''; // Clear the input
                    return;
                }
                
                // Check file extension as additional safety
                const fileExtension = file.name.split('.').pop().toLowerCase();
                const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
                
                if (!allowedExtensions.includes(fileExtension)) {
                    alert('Please select a valid file type (JPG, PNG, GIF, PDF, DOC, DOCX).');
                    this.value = ''; // Clear the input
                    return;
                }
                
                console.log('File validated successfully:', file.name, file.type, file.size);
            }
        });
    }
    
    // Real-time form validation
    setupRealTimeValidation();
});

// Real-time form validation
function setupRealTimeValidation() {
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('email');
    const subjectInput = document.getElementById('subject');
    const messageInput = document.getElementById('message');
    
    // Name validation
    if (firstNameInput) {
        firstNameInput.addEventListener('blur', function() {
            validateName(this);
        });
    }
    
    if (lastNameInput) {
        lastNameInput.addEventListener('blur', function() {
            validateName(this);
        });
    }
    
    // Email validation
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            validateEmail(this);
        });
    }
    
    // Subject validation
    if (subjectInput) {
        subjectInput.addEventListener('change', function() {
            validateSubject(this);
        });
    }
    
    // Message validation
    if (messageInput) {
        messageInput.addEventListener('blur', function() {
            validateMessage(this);
        });
    }
}

// Validation functions
function validateName(input) {
    const value = input.value.trim();
    if (value.length < 2) {
        showFieldError(input, 'Name must be at least 2 characters long');
        return false;
    } else {
        clearFieldError(input);
        return true;
    }
}

function validateEmail(input) {
    const value = input.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!value) {
        showFieldError(input, 'Email is required');
        return false;
    } else if (!emailRegex.test(value)) {
        showFieldError(input, 'Please enter a valid email address');
        return false;
    } else {
        clearFieldError(input);
        return true;
    }
}

function validateSubject(input) {
    const value = input.value;
    if (!value) {
        showFieldError(input, 'Please select a subject');
        return false;
    } else {
        clearFieldError(input);
        return true;
    }
}

function validateMessage(input) {
    const value = input.value.trim();
    if (value.length < 10) {
        showFieldError(input, 'Message must be at least 10 characters long');
        return false;
    } else {
        clearFieldError(input);
        return true;
    }
}

// Error display functions
function showFieldError(input, message) {
    // Remove any existing error
    clearFieldError(input);
    
    // Add error class to input
    input.classList.add('is-invalid');
    
    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    
    // Insert after input
    input.parentNode.appendChild(errorDiv);
}

function clearFieldError(input) {
    input.classList.remove('is-invalid');
    
    // Remove any existing error message
    const existingError = input.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
}

// Add some CSS for validation styles (dynamically)
function addValidationStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
            font-family: 'SFpro_regular', sans-serif;
        }
    `;
    document.head.appendChild(style);
}

// Initialize validation styles
addValidationStyles();

// Form auto-save functionality (optional)
function setupAutoSave() {
    const contactForm = document.getElementById('contactForm');
    if (!contactForm) return;
    
    const inputs = contactForm.querySelectorAll('input, textarea, select');
    const autoSaveKey = 'contactFormDraft';
    
    // Load saved data
    const savedData = localStorage.getItem(autoSaveKey);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const input = contactForm.querySelector(`[name="${key}"]`);
                if (input && input.type !== 'file') {
                    input.value = data[key];
                }
            });
            console.log('Auto-saved form data loaded');
        } catch (e) {
            console.error('Error loading auto-saved data:', e);
        }
    }
    
    // Save on input change
    inputs.forEach(input => {
        if (input.type !== 'file') {
            input.addEventListener('input', debounce(saveFormData, 1000));
        }
    });
    
    // Clear saved data on successful submission
    contactForm.addEventListener('submit', function() {
        localStorage.removeItem(autoSaveKey);
    });
}

function saveFormData() {
    const contactForm = document.getElementById('contactForm');
    if (!contactForm) return;
    
    const formData = new FormData(contactForm);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (key !== 'attachment') { // Don't save file data
            data[key] = value;
        }
    }
    
    localStorage.setItem('contactFormDraft', JSON.stringify(data));
}

// Debounce function to limit how often saveFormData is called
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize auto-save when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupAutoSave();
});

// Character counter for message field
function setupCharacterCounter() {
    const messageInput = document.getElementById('message');
    if (!messageInput) return;
    
    // Create counter element
    const counter = document.createElement('div');
    counter.className = 'character-counter';
    counter.style.cssText = `
        text-align: right;
        font-size: 12px;
        color: #666;
        margin-top: 5px;
        font-family: 'SFpro_regular', sans-serif;
    `;
    
    messageInput.parentNode.appendChild(counter);
    
    function updateCounter() {
        const length = messageInput.value.length;
        counter.textContent = `${length} characters`;
        
        if (length < 10) {
            counter.style.color = '#dc3545';
        } else if (length < 50) {
            counter.style.color = '#ffc107';
        } else {
            counter.style.color = '#28a745';
        }
    }
    
    messageInput.addEventListener('input', updateCounter);
    updateCounter(); // Initialize counter
}

// Initialize character counter
document.addEventListener('DOMContentLoaded', function() {
    setupCharacterCounter();
});

// Enhanced file upload UI
function enhanceFileUpload() {
    const attachmentInput = document.getElementById('attachment');
    if (!attachmentInput) return;
    
    // Create custom file upload UI
    const customUpload = document.createElement('div');
    customUpload.className = 'custom-file-upload';
    customUpload.style.cssText = `
        border: 2px dashed #ddd;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 15px;
    `;
    
    customUpload.innerHTML = `
        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #811212; margin-bottom: 10px;"></i>
        <div style="font-family: SFpro_regular; color: #666;">
            <div>Click to upload or drag and drop</div>
            <small>JPG, PNG, GIF, PDF, DOC, DOCX (Max 5MB)</small>
        </div>
        <div class="file-preview" style="margin-top: 10px; display: none;"></div>
    `;
    
    // Insert before the actual file input
    attachmentInput.parentNode.insertBefore(customUpload, attachmentInput);
    attachmentInput.style.display = 'none';
    
    // Click event
    customUpload.addEventListener('click', function() {
        attachmentInput.click();
    });
    
    // Drag and drop functionality
    customUpload.addEventListener('dragover', function(e) {
        e.preventDefault();
        customUpload.style.borderColor = '#811212';
        customUpload.style.backgroundColor = '#f8f9fa';
    });
    
    customUpload.addEventListener('dragleave', function(e) {
        e.preventDefault();
        customUpload.style.borderColor = '#ddd';
        customUpload.style.backgroundColor = 'transparent';
    });
    
    customUpload.addEventListener('drop', function(e) {
        e.preventDefault();
        customUpload.style.borderColor = '#ddd';
        customUpload.style.backgroundColor = 'transparent';
        
        if (e.dataTransfer.files.length > 0) {
            attachmentInput.files = e.dataTransfer.files;
            handleFileSelection(e.dataTransfer.files[0]);
        }
    });
    
    // Handle file selection
    attachmentInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelection(this.files[0]);
        }
    });
    
    function handleFileSelection(file) {
        const preview = customUpload.querySelector('.file-preview');
        const fileSize = (file.size / (1024 * 1024)).toFixed(2);
        
        preview.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; background: #f8f9fa; padding: 10px; border-radius: 5px;">
                <div style="display: flex; align-items: center;">
                    <i class="fas fa-file" style="color: #811212; margin-right: 10px;"></i>
                    <div>
                        <div style="font-weight: bold;">${file.name}</div>
                        <small>${fileSize} MB</small>
                    </div>
                </div>
                <button type="button" class="remove-file" style="background: none; border: none; color: #dc3545; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        preview.style.display = 'block';
        
        // Remove file functionality
        const removeBtn = preview.querySelector('.remove-file');
        removeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            attachmentInput.value = '';
            preview.style.display = 'none';
        });
    }
}

// Initialize enhanced file upload
document.addEventListener('DOMContentLoaded', function() {
    enhanceFileUpload();
});