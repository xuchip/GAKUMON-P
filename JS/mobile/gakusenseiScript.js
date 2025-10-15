// --- Run only after the DOM is ready ---
document.addEventListener('DOMContentLoaded', () => {
  // ===== NAV: Account dropdown =====
  const accountBtn = document.getElementById('accountDropdownBtn');
  const dropdown   = document.getElementById('accountDropdown');

  if (accountBtn && dropdown) {
    accountBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      // Re-check in case this script is used on pages without these nodes
      const btn = document.getElementById('accountDropdownBtn');
      const menu = document.getElementById('accountDropdown');
      if (!btn || !menu) return; // nothing to do
      const clickedOutsideBtn  = e.target !== btn && !btn.contains(e.target);
      const clickedOutsideMenu = !menu.contains(e.target);
      if (clickedOutsideBtn && clickedOutsideMenu) menu.classList.remove('show');
    });
  }

  // ===== CTA → open application modal =====
  const applyButton      = document.querySelector('.cta-button');
  const applicationModal = document.getElementById('gakusenseiModal');

  const openModal = () => {
    if (!applicationModal) return;
    applicationModal.classList.add('active');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    if (!applicationModal) return;
    applicationModal.classList.remove('active');
    document.body.style.overflow = '';
  };

  if (applyButton && applicationModal) {
    applyButton.addEventListener('click', openModal);
  }

  // Close modal on X/backdrop
  const closeButtons = document.querySelectorAll('.custom-modal-close, .custom-modal-backdrop');
  if (closeButtons.length && applicationModal) {
    closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
  }

  // Keep clicks inside content from closing modal
  const modalContent = document.querySelector('.custom-modal-content');
  if (modalContent) modalContent.addEventListener('click', (e) => e.stopPropagation());

  // Escape key closes modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && applicationModal && applicationModal.classList.contains('active')) {
      closeModal();
    }
  });

  // ===== Application form submit (AJAX) =====
  const applicationForm = document.querySelector('form[name="gakusenseiApplication"]');
  const submitBtn       = document.getElementById('submitApplicationBtn');

  // Optional toast (only if present and Bootstrap loaded)
  const toastEl = document.getElementById('applicationToast');
  const toast   = (typeof bootstrap !== 'undefined' && toastEl)
    ? new bootstrap.Toast(toastEl, { delay: 5000 })
    : null;

  if (applicationForm && submitBtn) {
    applicationForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const originalText = submitBtn.textContent;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Application Pending...';
      submitBtn.disabled = true;

      try {
        const url = applicationForm.getAttribute('action') || window.location.href;
        const data = new FormData(applicationForm);

        const res  = await fetch(url, { method: 'POST', body: data });
        const text = await res.text();

        let json = null;
        try { json = JSON.parse(text); } catch (_) {}

        if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0, 300)}`);
        if (!json || json.ok !== true) {
          const serverMsg = (json && (json.message || json.error)) || text.slice(0, 300) || 'Unknown error';
          throw new Error(serverMsg);
        }

        applicationForm.reset();
        closeModal();
        if (toast) toast.show();
      } catch (err) {
        alert(`Submission failed: ${err.message}`);
        console.error(err);
      } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      }
    });
  }
});
