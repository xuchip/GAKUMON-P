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

// JavaScript to handle modal opening
document.addEventListener('DOMContentLoaded', function() {
    // Get the apply button and modal
    const applyButton = document.querySelector('.cta-button');
    const applicationModal = document.getElementById('gakusenseiModal');
    
    // Add click event to open modal
    if (applyButton && applicationModal) {
        applyButton.addEventListener('click', function() {
            applicationModal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        });
    }
    
    // Close modal functionality
    const closeButtons = document.querySelectorAll('.custom-modal-close, .custom-modal-backdrop');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            applicationModal.classList.remove('active');
            document.body.style.overflow = ''; // Re-enable scrolling
        });
    });
    
    // Prevent modal content click from closing modal
    const modalContent = document.querySelector('.custom-modal-content');
    if (modalContent) {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});

// TRY
document.addEventListener('DOMContentLoaded', function () {
  const applicationModal = document.getElementById('gakusenseiModal');
  const applicationForm  = document.querySelector('form[name="gakusenseiApplication"]');
  const submitBtn        = document.getElementById('submitApplicationBtn');
  const toastEl          = document.getElementById('applicationToast');
  const toast            = new bootstrap.Toast(toastEl, { delay: 5000 });

  function closeModal() {
    applicationModal.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (!applicationForm) return;

  applicationForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const originalText = submitBtn.textContent;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Application Pending...';
    submitBtn.disabled = true;

    try {
      const url  = applicationForm.getAttribute('action');
      const data = new FormData(applicationForm);

      const res   = await fetch(url, { method: 'POST', body: data });
      const text  = await res.text();        // read raw text first
      let json    = null;

      try { json = JSON.parse(text); } catch (e) { /* not JSON */ }

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${text.slice(0, 300)}`);
      }
      if (!json || json.ok !== true) {
        // if server returned HTML / warning, show the first part so we see the root cause
        const serverMsg = json?.message || text.slice(0, 300) || 'Unknown error';
        throw new Error(serverMsg);
      }

      // success
      applicationForm.reset();
      closeModal();
      toast.show();
    } catch (err) {
      alert(`Submission failed: ${err.message}`);
      console.error(err);
    } finally {
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }
  });

  const modalContent = document.querySelector('.custom-modal-content');
  if (modalContent) modalContent.addEventListener('click', (e) => e.stopPropagation());
});


// document.addEventListener('DOMContentLoaded', function() {
//     // Get elements
//     const applicationModal = document.getElementById('gakusenseiModal');
//     const applicationForm = document.querySelector('form[name="gakusenseiApplication"]');
//     const submitBtn = document.getElementById('submitApplicationBtn');
//     const closeButtons = document.querySelectorAll('.custom-modal-close, .custom-modal-close-btn, .custom-modal-backdrop');
//     const toastEl = document.getElementById('applicationToast');
//     const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
    
//     // Close modal function
//     function closeModal() {
//         applicationModal.classList.remove('active');
//         document.body.style.overflow = '';
//     }
    
//     // Close modal when clicking close buttons or backdrop
//     closeButtons.forEach(button => {
//         button.addEventListener('click', closeModal);
//     });
    
//     // Form submission handler
//     if (applicationForm) {
//         applicationForm.addEventListener('submit', function(e) {
//             e.preventDefault();
            
//             // Change button to "Application Pending"
//             const originalText = submitBtn.textContent;
//             submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Application Pending...';
//             submitBtn.disabled = true;
            
//             // Simulate form submission (replace with actual AJAX call)
//             setTimeout(() => {
//                 // Close modal
//                 closeModal();
                
//                 // Show success toast
//                 toast.show();
                
//                 // Reset button after a delay
//                 setTimeout(() => {
//                     submitBtn.textContent = originalText;
//                     submitBtn.disabled = false;
//                 }, 2000);
                
//                 // Here you would normally submit the form via AJAX
//                 // For now, we're just simulating success
//                 console.log('Form would be submitted here');
                
//             }, 1500); // Simulate processing time
//         });
//     }
    
//     // Prevent modal content click from closing modal
//     const modalContent = document.querySelector('.custom-modal-content');
//     if (modalContent) {
//         modalContent.addEventListener('click', function(e) {
//             e.stopPropagation();
//         });
//     }
// });