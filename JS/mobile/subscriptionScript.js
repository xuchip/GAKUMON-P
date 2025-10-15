// ===== NAVIGATION DROPDOWN FUNCTIONALITY =====
document.addEventListener('DOMContentLoaded', () => {
  const accountBtn = document.getElementById('accountDropdownBtn');
  const dropdown  = document.getElementById('accountDropdown');

  if (accountBtn && dropdown) {
    accountBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      const clickedOutsideBtn = e.target !== accountBtn && !accountBtn.contains(e.target);
      const clickedOutsideMenu = !dropdown.contains(e.target);
      if (clickedOutsideBtn && clickedOutsideMenu) dropdown.classList.remove('show');
    });
  }
});

// ===== SUBSCRIPTION MODAL FUNCTIONALITY =====
document.addEventListener('DOMContentLoaded', function () {
  const subscriptionModal = document.getElementById('subscriptionModal');
  const subscribeBtn      = document.getElementById('subscribeBtn');
  const closeModalBtn     = document.querySelector('.custom-modal-close-btn');
  const modalBackdrop     = document.querySelector('.custom-modal-backdrop');
  const subscriptionForm  = document.getElementById('subscriptionForm');

  function closeModal() {
    if (!subscriptionModal) return;
    subscriptionModal.classList.remove('active');
    document.body.style.overflow = 'auto';
    if (subscriptionForm) subscriptionForm.reset();
  }

  if (subscribeBtn && subscriptionModal) {
    subscribeBtn.addEventListener('click', function () {
      subscriptionModal.classList.add('active');
      document.body.style.overflow = 'hidden';
    });
  }

  if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
  if (modalBackdrop)  modalBackdrop.addEventListener('click', closeModal);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && subscriptionModal && subscriptionModal.classList.contains('active')) {
      closeModal();
    }
  });

  if (subscriptionForm) {
    subscriptionForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const termsAgree = document.getElementById('termsAgree');
      if (!termsAgree || !termsAgree.checked) {
        alert('Please agree to the Terms of Service and Privacy Policy');
        return;
      }

      const submitBtn = document.querySelector('button[name="processSubscriptionBtn"]');
      if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        window.open('checkout.php', '_blank');
      }

      const formData = new FormData(subscriptionForm);

      fetch(subscriptionForm.action || window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(r => r.text())
        .then(data => {
          try {
            const json = JSON.parse(data);
            if (json.checkout_url) return (window.location.href = json.checkout_url);
            if (json.error) alert(json.error);
          } catch {
            const m = data.match(/checkout_url['"]:\s*['"]([^'"]+)['"]/);
            if (m && m[1]) return (window.location.href = m[1]);
            alert('Please accomplish payment on the new tab.');
          }
        })
        .catch(() => alert('Please accomplish payment on the new tab.'))
        .finally(() => {
          if (submitBtn) {
            submitBtn.innerHTML = 'Proceed to Payment';
            submitBtn.disabled = false;
          }
        });
    });
  }
});
