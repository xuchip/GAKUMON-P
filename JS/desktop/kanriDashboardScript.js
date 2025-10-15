// Toggle dropdown visibility
document.getElementById("accountDropdownBtn").addEventListener("click", function (e) {
	e.stopPropagation();
	const dropdown = document.getElementById("accountDropdown");
	dropdown.classList.toggle("show");
});

// Close dropdown when clicking outside
document.addEventListener("click", function (e) {
	const dropdown = document.getElementById("accountDropdown");
	const accountBtn = document.getElementById("accountDropdownBtn");
	if (!dropdown.contains(e.target) && e.target !== accountBtn && !accountBtn.contains(e.target)) {
		dropdown.classList.remove("show");
	}
});

// ACCOUNT MANAGEMENT

// Open delete confirmation modal
function openDeleteModal(userId) {
	document.getElementById("deleteUserId").value = userId;
	var deleteModal = new bootstrap.Modal(document.getElementById("deleteModal"));
	deleteModal.show();
}

// Open edit modal with user data
function openEditModal(button) {
	document.getElementById("editUserId").value = button.dataset.userId;
	document.getElementById("editFirstName").value = button.dataset.firstName;
	document.getElementById("editLastName").value = button.dataset.lastName;
	document.getElementById("editUsername").value = button.dataset.username;
	document.getElementById("editEmail").value = button.dataset.email;
	document.getElementById("editRole").value = button.dataset.role;

	var editModal = new bootstrap.Modal(document.getElementById("editModal"));
	editModal.show();
}

// Handle delete confirmation
function confirmDelete(userId) {
	fetch("delete_user.php", {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: `user_id=${userId}`,
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				location.reload();
			} else {
				alert("Error: " + data.message);
			}
		})
		.catch((error) => {
			console.error("Error:", error);
			alert("An error occurred while deleting the user.");
		});
}

// Handle edit save
function saveEdit(userId) {
	const formData = new FormData(document.getElementById(`editForm${userId}`));

	fetch("update_user.php", {
		method: "POST",
		body: formData,
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				location.reload();
			} else {
				alert("Error: " + data.message);
			}
		})
		.catch((error) => {
			console.error("Error:", error);
			alert("An error occurred while updating the user.");
		});
}

// APPLICATION MODAL

document.addEventListener("DOMContentLoaded", function () {
	// Get the apply button and modal
	const applyButton = document.querySelector(".cta-button");
	const applicationModal = document.getElementById("gakusenseiModal");

	// Add click event to open modal
	if (applyButton && applicationModal) {
		applyButton.addEventListener("click", function () {
			applicationModal.classList.add("active");
			document.body.style.overflow = "hidden"; // Prevent scrolling
		});
	}

	// Close modal functionality
	const closeButtons = document.querySelectorAll(".custom-modal-close, .custom-modal-backdrop");
	closeButtons.forEach((button) => {
		button.addEventListener("click", function () {
			applicationModal.classList.remove("active");
			document.body.style.overflow = ""; // Re-enable scrolling
		});
	});

	// Prevent modal content click from closing modal
	const modalContent = document.querySelector(".custom-modal-content");
	if (modalContent) {
		modalContent.addEventListener("click", function (e) {
			e.stopPropagation();
		});
	}
});

document.addEventListener("DOMContentLoaded", function () {
	// Get elements
	const applicationModal = document.getElementById("gakusenseiModal");
	const applicationForm = document.querySelector('form[name="gakusenseiApplication"]');
	const submitBtn = document.getElementById("submitApplicationBtn");
	const closeButtons = document.querySelectorAll(".custom-modal-close, .custom-modal-close-btn, .custom-modal-backdrop");
	const toastEl = document.getElementById("applicationToast");
	const toast = new bootstrap.Toast(toastEl, { delay: 5000 });

	// Close modal function
	function closeModal() {
		applicationModal.classList.remove("active");
		document.body.style.overflow = "";
	}

	// Close modal when clicking close buttons or backdrop
	closeButtons.forEach((button) => {
		button.addEventListener("click", closeModal);
	});

	// Form submission handler
	if (applicationForm) {
		applicationForm.addEventListener("submit", function (e) {
			e.preventDefault();

			// Change button to "Application Pending"
			const originalText = submitBtn.textContent;
			submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Application Pending...';
			submitBtn.disabled = true;

			// Simulate form submission (replace with actual AJAX call)
			setTimeout(() => {
				// Close modal
				closeModal();

				// Show success toast
				toast.show();

				// Reset button after a delay
				setTimeout(() => {
					submitBtn.textContent = originalText;
					submitBtn.disabled = false;
				}, 2000);

				console.log("Form would be submitted here");
			}, 1500); // Simulate processing time
		});
	}

	// Prevent modal content click from closing modal
	const modalContent = document.querySelector(".custom-modal-content");
	if (modalContent) {
		modalContent.addEventListener("click", function (e) {
			e.stopPropagation();
		});
	}

	// PET CUSTOMIZATION

	// Handle pet item delete confirmation
	function confirmDeletePet(itemId) {
		fetch("delete_pet_item.php", {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded",
			},
			body: `item_id=${itemId}`,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					location.reload();
				} else {
					alert("Error: " + data.message);
				}
			})
			.catch((error) => {
				console.error("Error:", error);
				alert("An error occurred while deleting the item.");
			});
	}

	// Handle pet item edit save
	function savePetEdit(itemId) {
		const formData = new FormData(document.getElementById(`editPetForm${itemId}`));

		fetch("update_pet_item.php", {
			method: "POST",
			body: formData,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					location.reload();
				} else {
					alert("Error: " + data.message);
				}
			})
			.catch((error) => {
				console.error("Error:", error);
				alert("An error occurred while updating the item.");
			});
	}
});
