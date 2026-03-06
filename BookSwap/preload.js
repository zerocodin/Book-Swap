const { contextBridge, ipcRenderer } = require('electron');

const API_BASE_URL = 'http://localhost/book_swap_api/admin/auth';

contextBridge.exposeInMainWorld('electronAPI', {
    navigate: (page) => ipcRenderer.send('navigate', page),
    
    // ... existing API methods ...
    
    // Add these if not already present
    login: async (email, password) => {
        try {
            const response = await fetch(`${API_BASE_URL}/login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            return await response.json();
        } catch (error) {
            console.error('Login API error:', error);
            throw error;
        }
    },
    
    register: async (name, email, password) => {
        try {
            const response = await fetch(`${API_BASE_URL}/register.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, password })
            });
            return await response.json();
        } catch (error) {
            console.error('Register API error:', error);
            throw error;
        }
    },
    
    forgotPassword: async (email) => {
        try {
            const response = await fetch(`${API_BASE_URL}/forgot-password.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });
            return await response.json();
        } catch (error) {
            console.error('Forgot password API error:', error);
            throw error;
        }
    },
    
    resetPassword: async (email, newPassword, otp) => {
        try {
            const response = await fetch(`${API_BASE_URL}/reset-password.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, new_password: newPassword, otp })
            });
            return await response.json();
        } catch (error) {
            console.error('Reset password API error:', error);
            throw error;
        }
    },
    
    verifyOTP: async (email, otp) => {
        try {
            const response = await fetch(`${API_BASE_URL}/verify-otp.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, otp })
            });
            return await response.json();
        } catch (error) {
            console.error('Verify OTP API error:', error);
            throw error;
        }
    }
});