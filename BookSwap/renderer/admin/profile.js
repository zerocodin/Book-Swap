// Profile Management Module
function showProfileEdit() {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');

    // Update header title
    const titleElement = document.getElementById('currentPageTitle');
    titleElement.textContent = 'Edit Profile';

    // Load profile edit content
    const contentArea = document.getElementById('contentArea');

    contentArea.innerHTML = `
        <div style="max-width: 600px; margin: 0 auto;">
            <div class="table-container">
                <form id="profileForm" enctype="multipart/form-data">
                    <!-- Profile Image Upload Section -->
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h4 style="margin-bottom: 20px;">Profile Picture</h4>
                        <div class="profile-image-upload" style="position: relative; width: 150px; height: 150px; margin: 0 auto;">
                            <img src="${getProfileImageUrlForUser(currentUser)}" 
                                 id="profilePreview"
                                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #00a884; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"
                                 onerror="this.src='../assets/default-avatar.png'">
                            <label for="profileImageInput" style="position: absolute; bottom: 10px; right: 10px; background: #00a884; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                <i class="fas fa-camera" style="color: white; font-size: 18px;"></i>
                            </label>
                            <input type="file" id="profileImageInput" accept="image/*" style="display: none;" onchange="window.previewProfileImage(this)">
                        </div>
                        <p style="color: #667781; font-size: 13px; margin-top: 10px;">Click the camera icon to change profile picture</p>
                    </div>

                    <!-- Personal Information -->
                    <h4 style="margin-bottom: 20px;">Personal Information</h4>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #111b21;">Full Name</label>
                        <input type="text" id="profileName" class="search-input" style="background: white; border: 1px solid #e9edef; padding: 12px;" 
                               value="${currentUser.name || ''}" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #111b21;">Email Address</label>
                        <input type="email" id="profileEmail" class="search-input" style="background: #f0f2f5; border: 1px solid #e9edef; padding: 12px; color: #667781;" 
                               value="${currentUser.email || ''}" readonly disabled>
                        <small style="color: #667781; margin-top: 5px; display: block;">Email address cannot be changed</small>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #111b21;">Role</label>
                        <input type="text" class="search-input" style="background: #f0f2f5; border: 1px solid #e9edef; padding: 12px; color: #667781;" 
                               value="${(currentUser.role || 'user').replace('_', ' ').toUpperCase()}" readonly disabled>
                    </div>

                    <!-- Change Password Section -->
                    <h4 style="margin: 30px 0 20px;">Change Password</h4>
                    <p style="color: #667781; font-size: 13px; margin-bottom: 15px;">Leave blank if you don't want to change your password</p>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #111b21;">Current Password</label>
                        <div style="position: relative;">
                            <input type="password" id="currentPassword" class="search-input" style="background: white; border: 1px solid #e9edef; padding: 12px; padding-right: 45px;" 
                                   placeholder="Enter current password">
                            <i class="fas fa-eye-slash toggle-password" onclick="window.togglePasswordField('currentPassword', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #667781;"></i>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #111b21;">New Password</label>
                        <div style="position: relative;">
                            <input type="password" id="newPassword" class="search-input" style="background: white; border: 1px solid #e9edef; padding: 12px; padding-right: 45px;" 
                                   placeholder="Enter new password">
                            <i class="fas fa-eye-slash toggle-password" onclick="window.togglePasswordField('newPassword', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #667781;"></i>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #111b21;">Confirm New Password</label>
                        <div style="position: relative;">
                            <input type="password" id="confirmPassword" class="search-input" style="background: white; border: 1px solid #e9edef; padding: 12px; padding-right: 45px;" 
                                   placeholder="Confirm new password">
                            <i class="fas fa-eye-slash toggle-password" onclick="window.togglePasswordField('confirmPassword', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #667781;"></i>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="button" class="secondary-btn" onclick="window.cancelProfileEdit()" style="flex: 1; padding: 14px;">Cancel</button>
                        <button type="button" class="primary-btn" onclick="window.updateProfile()" style="flex: 1; padding: 14px;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    `;
}

// Helper function to get profile image URL for current user
function getProfileImageUrlForUser(user) {
    // Return default if no user or no profile image
    if (!user || !user.profile_image ||
        user.profile_image === 'null' ||
        user.profile_image === '' ||
        user.profile_image === 'default.png') {
        return '../assets/default-avatar.png';
    }

    // Extract filename - works for both:
    // - "profile_images/user_5_1771276052.jpg" -> "user_5_1771276052.jpg"
    // - "profile_8_1771394489.jpg" -> "profile_8_1771394489.jpg"
    const filename = user.profile_image.split('/').pop();

    return `http://localhost/book_swap_api/profile_images/${filename}`;
}

function cancelProfileEdit() {
    // Go back to dashboard or previous page
    const activeNav = document.querySelector('.nav-item.active');
    if (activeNav) {
        const page = activeNav.dataset.page;
        window.loadPage(page);
    } else {
        window.loadPage('dashboard');
    }
}

function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('profilePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function updateProfile() {
    const name = document.getElementById('profileName').value;
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const profileImage = document.getElementById('profileImageInput').files[0];

    if (!name) {
        alert('Name is required');
        return;
    }

    if (newPassword && newPassword !== confirmPassword) {
        alert('New passwords do not match');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('name', name);
        formData.append('current_password', currentPassword);
        if (newPassword) {
            formData.append('new_password', newPassword);
        }
        if (profileImage) {
            formData.append('profile_image', profileImage);
        }

        const token = localStorage.getItem('token');

        // Show loading state
        const saveBtn = document.querySelector('.primary-btn');
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;

        const response = await fetch('http://localhost/book_swap_api/admin/api/update-profile.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });

        const result = await response.json();

        // Reset button
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;

        if (response.ok) {
            alert('Profile updated successfully');

            // Update local storage with new user data from response
            if (result.user) {
                localStorage.setItem('user', JSON.stringify(result.user));

                // Update all UI elements
                window.updateUserInfo(); // This will update name, role, and profile image

                // Force sidebar refresh with a slight delay
                setTimeout(() => {
                    // Use the refreshSidebarProfile function from index.js
                    if (window.refreshSidebarProfile) {
                        window.refreshSidebarProfile();
                    } else {
                        // Fallback: directly update the image using the correct function
                        const profileImg = document.getElementById('profileImg');
                        if (profileImg && result.user.profile_image) {
                            // Use the same logic as getProfileImageUrl
                            const filename = result.user.profile_image.split('/').pop();
                            profileImg.src = `http://localhost/book_swap_api/profile_images/${filename}`;
                        }
                    }
                }, 100);
            }

            // Go back to dashboard
            cancelProfileEdit();
        } else {
            alert(result.message || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        alert('Error updating profile. Please check your connection.');

        // Reset button
        const saveBtn = document.querySelector('.primary-btn');
        if (saveBtn) {
            saveBtn.textContent = 'Save Changes';
            saveBtn.disabled = false;
        }
    }
}

function togglePasswordField(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input) {
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
}

// Make functions available
window.showProfileEdit = showProfileEdit;
window.cancelProfileEdit = cancelProfileEdit;
window.previewProfileImage = previewProfileImage;
window.updateProfile = updateProfile;
window.togglePasswordField = togglePasswordField;