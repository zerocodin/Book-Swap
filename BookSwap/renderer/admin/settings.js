// Settings Module

async function loadSettings() {
    const contentArea = document.getElementById('contentArea');
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    
    // Only super_admin can view settings
    if (currentUser.role !== 'super_admin') {
        contentArea.innerHTML = `
            <div class="error-message" style="text-align: center; padding: 50px;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 20px;"></i>
                <h3 style="color: #111b21; margin-bottom: 10px;">Access Denied</h3>
                <p style="color: #667781;">You don't have permission to view settings.</p>
            </div>
        `;
        return;
    }
    
    contentArea.innerHTML = `
        <div style="max-width: 600px; margin: 0 auto;">
            <div class="table-container" style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 20px;">System Settings</h3>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Site Name</label>
                    <input type="text" class="search-input" style="background: white; border: 1px solid #e9edef;" value="BookSwap">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Admin Email</label>
                    <input type="email" class="search-input" style="background: white; border: 1px solid #e9edef;" value="${currentUser.email}">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Maintenance Mode</label>
                    <select class="search-input" style="background: white; border: 1px solid #e9edef;">
                        <option value="0">Disabled</option>
                        <option value="1">Enabled</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Items Per Page</label>
                    <input type="number" class="search-input" style="background: white; border: 1px solid #e9edef;" value="20">
                </div>
                
                <button class="primary-btn" style="width: 100%;">Save Settings</button>
            </div>
            
            <div class="table-container">
                <h3 style="margin-bottom: 20px;">Image Upload Settings</h3>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 10px;">Profile Images Path</h4>
                    <p style="color: #667781; font-size: 13px;">C:/xampp/htdocs/book_swap_api/profile_images/</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 10px;">Post Images Path</h4>
                    <p style="color: #667781; font-size: 13px;">C:/xampp/htdocs/book_swap_api/post_images/</p>
                </div>
            </div>
        </div>
    `;
}

// Make functions available
window.loadSettings = loadSettings;