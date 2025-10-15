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
    
//     // Handle link clicks
//     document.addEventListener('click', function(e) {
//         if (e.target.matches('a.text-link')) {
//             e.preventDefault();
//             navigateTo(e.target.href);
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