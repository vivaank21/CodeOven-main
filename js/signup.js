// script.js
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    // Password toggle
    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    // Confirm password toggle
    if (toggleConfirmPassword && confirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    // Form validation
    const form = document.querySelector('.signup-form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = this.querySelectorAll('input[required]');
            
            // Reset previous errors
            document.querySelectorAll('.field-error').forEach(el => {
                if (el.parentNode) el.parentNode.removeChild(el);
            });
            
            // Validate required fields
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    showError(input, 'This field is required');
                    isValid = false;
                }
            });
            
            // Validate password length
            const password = document.getElementById('password');
            if (password && password.value.length > 0 && password.value.length < 6) {
                showError(password, 'Password must be at least 6 characters');
                isValid = false;
            }
            
            // Validate password match
            const confirmPassword = document.getElementById('confirm_password');
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                showError(confirmPassword, 'Passwords do not match');
                isValid = false;
            }
            
            // Validate terms agreement
            const agreeTerms = document.getElementById('agree_terms');
            if (agreeTerms && !agreeTerms.checked) {
                showError(agreeTerms, 'You must agree to the terms and conditions');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    function showError(input, message) {
        // Add error class to input
        input.classList.add('error');
        
        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        
        // Insert after input
        input.parentNode.parentNode.appendChild(errorDiv);
        
        // Remove error on input
        input.addEventListener('input', function() {
            input.classList.remove('error');
            if (input.parentNode.parentNode.contains(errorDiv)) {
                input.parentNode.parentNode.removeChild(errorDiv);
            }
        });
    }
    
    // Real-time password confirmation validation
    // const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            const password = document.getElementById('password');
            const errorDiv = this.parentNode.parentNode.querySelector('.field-error');
            
            if (password && this.value !== password.value) {
                if (!errorDiv || errorDiv.textContent !== 'Passwords do not match') {
                    // Remove existing errors
                    if (errorDiv) errorDiv.parentNode.removeChild(errorDiv);
                    
                    // Create new error
                    const newErrorDiv = document.createElement('div');
                    newErrorDiv.className = 'field-error';
                    newErrorDiv.textContent = 'Passwords do not match';
                    this.parentNode.parentNode.appendChild(newErrorDiv);
                    this.classList.add('error');
                }
            } else if (errorDiv && errorDiv.textContent === 'Passwords do not match') {
                errorDiv.parentNode.removeChild(errorDiv);
                this.classList.remove('error');
            }
        });
    }
});