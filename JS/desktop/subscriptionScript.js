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

// ===== SUBSCRIPTION MODAL FUNCTIONALITY =====
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const subscriptionModal = document.getElementById('subscriptionModal');
    const subscribeBtn = document.getElementById('subscribeBtn');
    const closeModalBtn = document.querySelector('.custom-modal-close-btn');
    const modalBackdrop = document.querySelector('.custom-modal-backdrop');
    const subscriptionForm = document.getElementById('subscriptionForm');
    
    // Modal open function
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', function() {
            subscriptionModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Modal close function
    function closeModal() {
        subscriptionModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        if (subscriptionForm) subscriptionForm.reset();
    }
    
    // Modal close triggers
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }
    
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', closeModal);
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && subscriptionModal.classList.contains('active')) {
            closeModal();
        }
    });

    // Form submission handling
    if (subscriptionForm) {
        subscriptionForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent normal form submission
            
            const termsAgree = document.getElementById('termsAgree');
            
            if (!termsAgree.checked) {
                alert('Please agree to the Terms of Service and Privacy Policy');
                return false;
            }
            
            // Show loading state on submit button
            const submitBtn = document.querySelector('button[name="processSubscriptionBtn"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
                window.open("checkout.php", "_blank");
            }

            // Get form data
            const formData = new FormData(subscriptionForm);
            
            // Log form submission
            console.log('Submitting payment form...', Object.fromEntries(formData));

            // Submit form via fetch
            fetch(subscriptionForm.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(data => {
                console.log('Response data:', data);
                try {
                    // Try to parse as JSON in case it's JSON
                    const jsonData = JSON.parse(data);
                    console.log('Parsed JSON response:', jsonData);
                    
                    if (jsonData.checkout_url) {
                        console.log('Redirecting to:', jsonData.checkout_url);
                        window.location.href = jsonData.checkout_url;
                    } else if (jsonData.error) {
                        alert(jsonData.error);
                    }
                } catch (e) {
                    // If it's not JSON, check if it contains a redirect URL
                    if (data.includes('checkout_url')) {
                        const url = data.match(/checkout_url['"]:\s*['"]([^'"]+)['"]/);
                        if (url && url[1]) {
                            console.log('Redirecting to extracted URL:', url[1]);
                            window.location.href = url[1];
                        }
                    } else {
                        console.error('Could not process response:', e);
                        alert('Please accomplish payment on the new tab.');
                    }
                }
            })
            .catch(error => {
                console.error('Payment processing error:', error);
                alert('Please accomplish payment on the new tab.');
            })
            .finally(() => {
                // Reset button state
                if (submitBtn) {
                    submitBtn.innerHTML = 'Proceed to Payment';
                    submitBtn.disabled = false;
                }
            });
        });
    }
});