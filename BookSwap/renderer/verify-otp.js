let currentEmail = '';

document.addEventListener('DOMContentLoaded', function () {
    // Get email from localStorage
    currentEmail = localStorage.getItem('pendingVerificationEmail');
    if (!currentEmail) {
        alert('No email found for verification. Please register again.');
        window.electronAPI.navigate('register');
        return;
    }

    document.getElementById('emailDisplay').textContent = `OTP sent to: ${currentEmail}`;

    // Auto-submit when 6 digits are entered
    document.getElementById('otp').addEventListener('input', function (e) {
        if (e.target.value.length === 6) {
            handleVerifyOTP();
        }
    });
});

async function handleVerifyOTP() {
    try {
        const otp = document.getElementById('otp').value.trim();

        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP');
            return;
        }

        const verifyBtn = document.querySelector('.btn');
        const originalText = verifyBtn.textContent;
        verifyBtn.textContent = 'Verifying...';
        verifyBtn.disabled = true;

        const result = await window.electronAPI.verifyOTP(currentEmail, otp);

        verifyBtn.textContent = originalText;
        verifyBtn.disabled = false;

        if (result.message && result.message.includes("verified successfully")) {
            alert('Email verified successfully! You can now login.');
            localStorage.removeItem('pendingVerificationEmail');
            window.electronAPI.navigate('login');
        } else {
            alert(result.message || 'Verification failed');
        }

    } catch (error) {
        console.error('OTP verification error:', error);
        alert('An error occurred. Please try again.');

        const verifyBtn = document.querySelector('.btn');
        if (verifyBtn) {
            verifyBtn.textContent = 'Verify Email';
            verifyBtn.disabled = false;
        }
    }
}

async function handleResendOTP() {
    try {
        const resendLink = document.querySelector('.links a:first-child');
        const originalText = resendLink.textContent;
        resendLink.textContent = 'Sending...';
        resendLink.style.pointerEvents = 'none';

        const result = await window.electronAPI.forgotPassword(currentEmail);

        resendLink.textContent = originalText;
        resendLink.style.pointerEvents = 'auto';

        if (result.message && result.message.includes("OTP sent")) {
            alert('New OTP sent to your email!');
        } else {
            alert(result.message || 'Failed to resend OTP');
        }

    } catch (error) {
        console.error('Resend OTP error:', error);
        alert('An error occurred. Please try again.');

        const resendLink = document.querySelector('.links a:first-child');
        if (resendLink) {
            resendLink.textContent = 'Resend OTP';
            resendLink.style.pointerEvents = 'auto';
        }
    }
}

function handleBackToLogin() {
    localStorage.removeItem('pendingVerificationEmail');
    window.electronAPI.navigate('login');
}