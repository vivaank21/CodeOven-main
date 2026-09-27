// script.js
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    // Form validation
    const loginForm = document.querySelector('.login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            // Reset previous errors
            document.querySelectorAll('.field-error').forEach(el => el.remove());
            
            // Validate username
            if (!username.value.trim()) {
                showError(username, 'Username is required');
                isValid = false;
            }
            
            // Validate password
            if (!password.value.trim()) {
                showError(password, 'Password is required');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    function showError(input, message) {
        // Add error styling to input
        input.style.borderColor = '#dc2626';
        
        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = '#dc2626';
        errorDiv.style.fontSize = '14px';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        
        input.parentNode.parentNode.appendChild(errorDiv);
        
        // Remove error on input
        input.addEventListener('input', function() {
            input.style.borderColor = '#e5e7eb';
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        });
    }
    
    // Check for remembered user
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    
    const rememberedUser = getCookie('remembered_user');
    if (rememberedUser) {
        document.getElementById('username').value = rememberedUser;
        document.getElementById('remember').checked = true;
    }
    
    // Add hover effects to interactive elements
    const interactiveElements = document.querySelectorAll('a, button, .checkbox-container, .toggle-password');
    
    interactiveElements.forEach(el => {
        el.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.2s ease';
        });
        
        el.addEventListener('mouseleave', function() {
            this.style.transition = 'all 0.2s ease';
        });
    });
});