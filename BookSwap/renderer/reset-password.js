let resetEmail = '';

document.addEventListener('DOMContentLoaded', function () {
    resetEmail = localStorage.getItem('resetEmail');
    if (!resetEmail) {
        alert('No email found for password reset. Please try again.');
        window.electronAPI.navigate('forgot-password');
    }
});

async function handleResetPassword() {
    try {
        const otp = document.getElementById('otp').value.trim();
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const errorDiv = document.getElementById('passwordError');

        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP');
            return;
        }

        if (!newPassword || !confirmPassword) {
            alert('Please fill in all fields');
            return;
        }

        if (newPassword !== confirmPassword) {
            errorDiv.style.display = 'block';
            return;
        }

        if (newPassword.length < 6) {
            alert('Password must be at least 6 characters long');
            return;
        }

        errorDiv.style.display = 'none';

        const resetBtn = document.querySelector('.btn');
        const originalText = resetBtn.textContent;
        resetBtn.textContent = 'Resetting...';
        resetBtn.disabled = true;

        const result = await window.electronAPI.resetPassword(resetEmail, newPassword, otp);

        resetBtn.textContent = originalText;
        resetBtn.disabled = false;

        if (result.message && result.message.includes("Password reset successfully")) {
            alert('Password reset successful! Please login with your new password.');
            localStorage.removeItem('resetEmail');
            window.electronAPI.navigate('login');
        } else {
            alert(result.message || 'Failed to reset password');
        }

    } catch (error) {
        console.error('Reset password error:', error);
        alert('An error occurred. Please try again.');

        const resetBtn = document.querySelector('.btn');
        if (resetBtn) {
            resetBtn.textContent = 'Reset Password';
            resetBtn.disabled = false;
        }
    }
}

function handleBackToForgot() {
    window.electronAPI.navigate('forgot-password');
}

function handleBackToLogin() {
    localStorage.removeItem('resetEmail');
    window.electronAPI.navigate('login');
}