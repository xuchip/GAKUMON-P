// // Handle login form submission with animation
// document.addEventListener('DOMContentLoaded', function() {
//     // Page load animation
//     const overlay = document.querySelector('.transition-overlay');
//     setTimeout(() => {
//         overlay.classList.add('overlay-slide-out');
//     }, 100);
    
//     // Handle form submissions
//     const loginForm = document.querySelector('form[name="login"]');
//     if (loginForm) {
//         loginForm.addEventListener('submit', function(e) {
//             e.preventDefault(); // Prevent default form submission
            
//             // Show loading animation
//             const overlay = document.querySelector('.transition-overlay');
//             const content = document.querySelector('.page-content');
            
//             overlay.classList.remove('overlay-slide-out');
//             overlay.classList.add('overlay-slide-in');
//             content.classList.add('content-fade-out');
            
//             // Submit the form after animation
//             setTimeout(() => {
//                 this.submit();
//             }, 800);
//         });
//     }
    
//     // Handle signup form submission
//     const signupForm = document.querySelector('form[name="signup"]');
//     if (signupForm) {
//         signupForm.addEventListener('submit', function(e) {
//             e.preventDefault(); // Prevent default form submission
            
//             // Show loading animation
//             const overlay = document.querySelector('.transition-overlay');
//             const content = document.querySelector('.page-content');
            
//             overlay.classList.remove('overlay-slide-out');
//             overlay.classList.add('overlay-slide-in');
//             content.classList.add('content-fade-out');
            
//             // Submit the form after animation
//             setTimeout(() => {
//                 this.submit();
//             }, 800);
//         });
//     }
    
//     // Handle all link clicks with the text-link class
//     document.addEventListener('click', function(e) {
//         if (e.target.closest('a.text-link')) {
//             e.preventDefault();
//             const link = e.target.closest('a.text-link');
//             navigateTo(link.href);
//         }
//     });
// });

// function navigateTo(url) {
//     const overlay = document.querySelector('.transition-overlay');
//     const content = document.querySelector('.page-content');
    
//     // Start transition
//     overlay.classList.remove('overlay-slide-out');
//     overlay.classList.add('overlay-slide-in');
//     content.classList.add('content-fade-out');
    
//     // Navigate after animation completes
//     setTimeout(() => {
//         window.location.href = url;
//     }, 800);
// }

function togglePassword(fieldId, iconId) {
    const passwordField = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function updatePasswordRequirements() {
    const password = document.getElementById('password').value;
    const lengthReq = document.getElementById('lengthReq');
    const caseReq = document.getElementById('caseReq');
    
    // Check length requirement
    if (password.length >= 8) {
        lengthReq.classList.remove('text-danger');
        lengthReq.classList.add('text-success');
    } else {
        lengthReq.classList.remove('text-success');
        lengthReq.classList.add('text-danger');
    }
    
    // Check case requirement
    if (password !== password.toLowerCase() && password !== password.toUpperCase() && password !== '') {
        caseReq.classList.remove('text-danger');
        caseReq.classList.add('text-success');
    } else {
        caseReq.classList.remove('text-success');
        caseReq.classList.add('text-danger');
    }
}

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const matchText = document.getElementById('passwordMatch');
    
    if (confirmPassword === '') {
        matchText.textContent = '';
        matchText.classList.remove('text-danger', 'text-success');
    } else if (password === confirmPassword) {
        matchText.textContent = '✓ Passwords match';
        matchText.classList.remove('text-danger');
        matchText.classList.add('text-success');
    } else {
        matchText.textContent = '✗ Passwords do not match';
        matchText.classList.remove('text-success');
        matchText.classList.add('text-danger');
    }
}

// Custom Alert System
class CustomAlerts {
    constructor() {
        this.initializeAlerts();
    }

    initializeAlerts() {
        // Override native alert
        window.originalAlert = window.alert;
        window.alert = (message) => {
            return this.showAlert(message);
        };

        // Override native confirm
        window.originalConfirm = window.confirm;
        window.confirm = (message) => {
            return this.showConfirm(message);
        };

        // Override native prompt
        window.originalPrompt = window.prompt;
        window.prompt = (message, defaultValue = '') => {
            return this.showPrompt(message, defaultValue);
        };
    }

    showAlert(message, type = 'info') {
        return new Promise((resolve) => {
            const modal = document.getElementById('customAlertModal');
            const messageEl = document.getElementById('alertMessage');
            const iconEl = document.getElementById('alertIcon');
            const okBtn = document.getElementById('alertOkBtn');
            const cancelBtn = document.getElementById('alertCancelBtn');

            // Set message and styling based on type
            messageEl.innerHTML = message;
            this.setAlertStyle(iconEl, type);

            // Show only OK button
            okBtn.style.display = 'block';
            cancelBtn.style.display = 'none';
            okBtn.textContent = 'OK';

            // Remove previous event listeners
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);

            // Add event listener
            newOkBtn.addEventListener('click', () => {
                this.closeModal(modal);
                resolve(true);
            });

            // Show modal
            this.openModal(modal);
        });
    }

    showConfirm(message) {
        return new Promise((resolve) => {
            const modal = document.getElementById('customAlertModal');
            const messageEl = document.getElementById('alertMessage');
            const iconEl = document.getElementById('alertIcon');
            const okBtn = document.getElementById('alertOkBtn');
            const cancelBtn = document.getElementById('alertCancelBtn');

            // Set message and styling
            messageEl.innerHTML = message;
            this.setAlertStyle(iconEl, 'question');

            // Show both buttons
            okBtn.style.display = 'block';
            cancelBtn.style.display = 'block';
            okBtn.textContent = 'Yes';
            cancelBtn.textContent = 'No';

            // Remove previous event listeners
            const newOkBtn = okBtn.cloneNode(true);
            const newCancelBtn = cancelBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

            // Add event listeners
            newOkBtn.addEventListener('click', () => {
                this.closeModal(modal);
                resolve(true);
            });

            newCancelBtn.addEventListener('click', () => {
                this.closeModal(modal);
                resolve(false);
            });

            // Show modal
            this.openModal(modal);
        });
    }

    showPrompt(message, defaultValue = '') {
        return new Promise((resolve) => {
            const modal = document.getElementById('customPromptModal');
            const messageEl = document.getElementById('promptMessage');
            const inputEl = document.getElementById('promptInput');
            const okBtn = document.getElementById('promptOkBtn');
            const cancelBtn = document.getElementById('promptCancelBtn');

            // Set message and default value
            messageEl.innerHTML = message;
            inputEl.value = defaultValue;

            // Remove previous event listeners
            const newOkBtn = okBtn.cloneNode(true);
            const newCancelBtn = cancelBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

            const handleConfirm = () => {
                const value = inputEl.value;
                this.closeModal(modal);
                resolve(value);
            };

            const handleCancel = () => {
                this.closeModal(modal);
                resolve(null);
            };

            // Add event listeners
            newOkBtn.addEventListener('click', handleConfirm);
            newCancelBtn.addEventListener('click', handleCancel);

            // Enter key support
            inputEl.onkeypress = (e) => {
                if (e.key === 'Enter') {
                    handleConfirm();
                }
            };

            // Show modal and focus input
            this.openModal(modal);
            setTimeout(() => inputEl.focus(), 100);
        });
    }

    setAlertStyle(iconEl, type) {
        const icons = {
            'info': 'fa-info-circle',
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-triangle',
            'error': 'fa-times-circle',
            'question': 'fa-question-circle'
        };

        const colors = {
            'info': '#17a2b8',
            'success': '#28a745',
            'warning': '#ffc107',
            'error': '#dc3545',
            'question': '#6f42c1'
        };

        // Remove all icon classes and add the appropriate one
        iconEl.className = 'fas ' + (icons[type] || icons.info);
        iconEl.style.color = colors[type] || colors.info;
    }

    openModal(modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Add backdrop click to close
        const backdrop = modal.querySelector('.custom-modal-backdrop');
        backdrop.onclick = () => this.closeModal(modal);
    }

    closeModal(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Initialize custom alerts
const customAlerts = new CustomAlerts();

// Password visibility toggle
function togglePassword(fieldId, iconId) {
    const passwordField = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password requirement validation
function updatePasswordRequirements() {
    const password = document.getElementById('password').value;
    const lengthReq = document.getElementById('lengthReq');
    const caseReq = document.getElementById('caseReq');
    
    // Check length requirement
    if (password.length >= 8) {
        lengthReq.classList.remove('text-danger');
        lengthReq.classList.add('text-success');
    } else {
        lengthReq.classList.remove('text-success');
        lengthReq.classList.add('text-danger');
    }
    
    // Check case requirement
    if (password !== password.toLowerCase() && password !== password.toUpperCase() && password !== '') {
        caseReq.classList.remove('text-danger');
        caseReq.classList.add('text-success');
    } else {
        caseReq.classList.remove('text-success');
        caseReq.classList.add('text-danger');
    }
}

// Password match validation
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const matchText = document.getElementById('passwordMatch');
    
    if (confirmPassword === '') {
        matchText.textContent = '';
        matchText.classList.remove('text-danger', 'text-success');
    } else if (password === confirmPassword) {
        matchText.textContent = '✓ Passwords match';
        matchText.classList.remove('text-danger');
        matchText.classList.add('text-success');
    } else {
        matchText.textContent = '✗ Passwords do not match';
        matchText.classList.remove('text-success');
        matchText.classList.add('text-danger');
    }
}

// Real-time validation with custom alerts
document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.querySelector('form[name="signup"]');
    
    // Real-time validation for username
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.addEventListener('blur', function() {
            if (this.value.length > 0 && !/^[a-zA-Z0-9]{4,20}$/.test(this.value)) {
                customAlerts.showAlert(
                    'Username must be 4-20 alphanumeric characters (letters and numbers only).',
                    'warning'
                );
            }
        });
    }

    // Real-time validation for email
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value.length > 0 && !emailRegex.test(this.value)) {
                customAlerts.showAlert(
                    'Please enter a valid email address.',
                    'warning'
                );
            }
        });
    }

    // Form submission validation
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            let hasErrors = false;
            const errorMessages = [];
            
            // Validate password
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                errorMessages.push('Password must be at least 8 characters long');
                hasErrors = true;
            }
            
            if (!/(?=.*[a-z])(?=.*[A-Z])/.test(password)) {
                errorMessages.push('Password must contain both uppercase and lowercase letters');
                hasErrors = true;
            }
            
            // Validate password match
            const confirmPassword = document.getElementById('confirmPassword').value;
            if (password !== confirmPassword) {
                errorMessages.push('Passwords do not match');
                hasErrors = true;
            }
            
            // Validate username
            const username = document.getElementById('username').value;
            if (!/^[a-zA-Z0-9]{4,20}$/.test(username)) {
                errorMessages.push('Username must be 4-20 alphanumeric characters');
                hasErrors = true;
            }
            
            // If there are errors, show them in custom alert and prevent form submission
            if (hasErrors) {
                e.preventDefault();
                const errorList = errorMessages.map(error => `• ${error}`).join('<br>');
                
                customAlerts.showAlert(`
                    <div style="text-align: left;">
                        <h4 style="color: #dc3545; margin-bottom: 15px;">Please fix the following errors:</h4>
                        <div style="color: #6c757d;">${errorList}</div>
                    </div>
                `, 'error');
                
                return false;
            }
            
            return true;
        });
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.custom-modal.active');
        modals.forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});