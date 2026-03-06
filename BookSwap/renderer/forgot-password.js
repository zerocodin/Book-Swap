async function handleSendOTP() {
    try {
        const email = document.getElementById('email');
        const emailValue = email.value.trim();

        if (!emailValue) {
            alert('Please enter your email');
            return;
        }

        if (!validateEmail(emailValue)) {
            alert('Please enter a valid email address');
            return;
        }

        const sendBtn = document.querySelector('.btn');
        const originalText = sendBtn.textContent;
        sendBtn.textContent = 'Sending OTP...';
        sendBtn.disabled = true;

        const result = await window.electronAPI.forgotPassword(emailValue);

        sendBtn.textContent = originalText;
        sendBtn.disabled = false;

        if (result.message && result.message.includes("OTP sent")) {
            localStorage.setItem('resetEmail', emailValue);
            window.electronAPI.navigate('reset-password');
        } else {
            alert(result.message || 'Failed to send OTP');
        }

    } catch (error) {
        console.error('Forgot password error:', error);
        alert('An error occurred. Please check your connection.');
        
        const sendBtn = document.querySelector('.btn');
        if (sendBtn) {
            sendBtn.textContent = 'Send OTP';
            sendBtn.disabled = false;
        }
    }
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function handleBackToLogin() {
    window.electronAPI.navigate('login');
}