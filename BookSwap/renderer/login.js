async function handleLogin() {
    try {
        const email = document.getElementById('email');
        const password = document.getElementById('password');

        if (!email || !password) {
            console.error('Input elements not found');
            alert('Error: Form elements not found');
            return;
        }

        const emailValue = email.value.trim();
        const passwordValue = password.value;

        if (!emailValue || !passwordValue) {
            alert('Please fill in all fields');
            return;
        }

        if (!validateEmail(emailValue)) {
            alert('Please enter a valid email address');
            return;
        }

        const loginBtn = document.querySelector('.btn');
        const originalText = loginBtn.textContent;
        loginBtn.textContent = 'Logging in...';
        loginBtn.disabled = true;

        email.disabled = true;
        password.disabled = true;

        // Use fetch directly instead of electronAPI
        const response = await fetch('http://localhost/book_swap_api/admin/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: emailValue,
                password: passwordValue
            })
        });

        const result = await response.json();

        // Always re-enable inputs, regardless of success/failure
        email.disabled = false;
        password.disabled = false;
        loginBtn.textContent = originalText;
        loginBtn.disabled = false;

        if (response.ok && result.message === "Login successful") {
            localStorage.setItem('token', result.token);
            localStorage.setItem('user', JSON.stringify(result.user));

            // Clear form
            email.value = '';
            password.value = '';

            // Navigate based on user role
            if (result.user.role === 'user') {
                alert('Access denied. Admin privileges required.');
                return;
            } else {
                // Navigate to admin dashboard
                navigateToAdmin();
            }
        } else {
            alert(result.message || 'Login failed');
        }

    } catch (error) {
        console.error('Login error:', error);
        alert('An error occurred during login. Please check your connection.');

        // Re-enable everything on error
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        if (email) email.disabled = false;
        if (password) password.disabled = false;

        const loginBtn = document.querySelector('.btn');
        if (loginBtn) {
            loginBtn.textContent = 'Sign In';
            loginBtn.disabled = false;
        }
    }
}

// Helper function to navigate to admin
function navigateToAdmin() {
    try {
        // Try Electron navigation first
        if (window.electronAPI && window.electronAPI.navigate) {
            window.electronAPI.navigate('dashboard');
        } else {
            // Fallback to direct navigation
            window.location.href = '../pages/admin.html';
        }
    } catch (error) {
        console.error('Navigation error:', error);
        window.location.href = '../pages/admin.html';
    }
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function handleForgotPassword() {
    try {
        if (window.electronAPI && window.electronAPI.navigate) {
            window.electronAPI.navigate('forgot-password');
        } else {
            window.location.href = '../pages/forgot-password.html';
        }
    } catch (error) {
        console.error('Navigation error:', error);
        alert('Error navigating to forgot password page');
    }
}

function handleSignUp() {
    try {
        if (window.electronAPI && window.electronAPI.navigate) {
            window.electronAPI.navigate('register');
        } else {
            window.location.href = '../pages/register.html';
        }
    } catch (error) {
        console.error('Navigation error:', error);
        alert('Error navigating to register page');
    }
}

// Add event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    console.log('Login page loaded');

    // Add enter key support
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                handleLogin();
            }
        });
    }

    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
            }
        });
    }
});