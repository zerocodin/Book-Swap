async function handleRegister() {
    try {
        const fullname = document.getElementById('fullname');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const errorDiv = document.getElementById('passwordError');
        
        if (!fullname || !email || !password || !confirmPassword || !errorDiv) {
            console.error('Form elements not found');
            alert('Error: Form elements not found');
            return;
        }
        
        const fullnameValue = fullname.value.trim();
        const emailValue = email.value.trim();
        const passwordValue = password.value;
        const confirmPasswordValue = confirmPassword.value;
        
        if (!fullnameValue || !emailValue || !passwordValue || !confirmPasswordValue) {
            alert('Please fill in all fields');
            return;
        }
        
        if (!validateEmail(emailValue)) {
            alert('Please enter a valid email address');
            return;
        }
        
        if (passwordValue !== confirmPasswordValue) {
            errorDiv.style.display = 'block';
            return;
        }
        
        if (passwordValue.length < 6) {
            alert('Password must be at least 6 characters long');
            return;
        }
        
        errorDiv.style.display = 'none';
        
        const registerBtn = document.querySelector('.btn');
        const originalText = registerBtn.textContent;
        registerBtn.textContent = 'Creating account...';
        registerBtn.disabled = true;
        
        // Disable inputs
        fullname.disabled = true;
        email.disabled = true;
        password.disabled = true;
        confirmPassword.disabled = true;
        
        const result = await window.electronAPI.register(fullnameValue, emailValue, passwordValue);
        
        // Re-enable inputs
        fullname.disabled = false;
        email.disabled = false;
        password.disabled = false;
        confirmPassword.disabled = false;
        registerBtn.textContent = originalText;
        registerBtn.disabled = false;
        
        if (result.message && result.message.includes("created successfully")) {
            alert('Account created! Please verify your email with the OTP sent.');
            
            // Store email for OTP verification
            localStorage.setItem('pendingVerificationEmail', emailValue);
            
            // Navigate to OTP verification page
            window.electronAPI.navigate('verify-otp');
            
        } else {
            alert(result.message || 'Registration failed');
        }
        
    } catch (error) {
        console.error('Registration error:', error);
        alert('An error occurred during registration. Please check your connection.');
        
        // Re-enable all inputs and button
        const inputs = ['fullname', 'email', 'password', 'confirmPassword'];
        inputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) input.disabled = false;
        });
        
        const registerBtn = document.querySelector('.btn');
        if (registerBtn) {
            registerBtn.textContent = 'Register Now';
            registerBtn.disabled = false;
        }
    }
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function handleSignIn() {
    try {
        window.electronAPI.navigate('login');
    } catch (error) {
        console.error('Navigation error:', error);
        alert('Error navigating to login page');
    }
}