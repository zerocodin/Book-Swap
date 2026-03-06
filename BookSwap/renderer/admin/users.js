// Users Management Module
let allUsers = []; // Store all users for searching
let currentUserSearchTerm = ''; // Use consistent variable name

async function loadUsers() {
    const contentArea = document.getElementById('contentArea');
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');

    try {
        const token = localStorage.getItem('token');
        const response = await fetch('http://localhost/book_swap_api/admin/api/users.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch users');
        }

        allUsers = await response.json();
        console.log('Users loaded:', allUsers);

        renderUsersTable(allUsers, currentUser);

    } catch (error) {
        console.error('Users error:', error);
        contentArea.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Failed to load users. Please try again.
                <br><small>${error.message}</small>
                <br><small>Check console for details (F12)</small>
            </div>
        `;
    }
}

// Handle user search (exposed as window.handleUserSearch)
function handleUserSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    currentUserSearchTerm = searchTerm;
    
    const filteredUsers = allUsers.filter(user => {
        return user.name.toLowerCase().includes(searchTerm) ||
               user.email.toLowerCase().includes(searchTerm) ||
               (user.student_id && user.student_id.toLowerCase().includes(searchTerm)) ||
               (user.department && user.department.toLowerCase().includes(searchTerm));
    });
    
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    renderUsersTable(filteredUsers, currentUser, searchTerm);
}

// Clear user search
function clearUserSearch() {
    currentUserSearchTerm = '';
    const globalSearchInput = document.getElementById('globalSearchInput');
    if (globalSearchInput) {
        globalSearchInput.value = '';
    }
    
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    renderUsersTable(allUsers, currentUser);
}

// Render users table with role-based permissions
function renderUsersTable(users, currentUser, searchTerm = '') {
    const contentArea = document.getElementById('contentArea');
    
    // Check if current user can add users
    const canAddUser = window.permissions.hasPermission('add_user');
    
    contentArea.innerHTML = `
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3>User Management</h3>
            ${canAddUser ?
            '<button class="primary-btn" onclick="window.showAddUserModal()"><i class="fas fa-plus"></i> Add User</button>' :
            ''}
        </div>
        
        ${searchTerm ? `
            <div class="search-results-info">
                <i class="fas fa-search"></i> Found ${users.length} user${users.length !== 1 ? 's' : ''} matching "${searchTerm}"
                <span class="clear-search" onclick="window.clearUserSearch()">Clear search</span>
            </div>
        ` : ''}
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${users && users.length > 0 ?
            users.map(user => {
                // Check if current user can edit this user
                const canEdit = window.permissions.canEditUser(user.role);
                // Check if current user can delete this user
                const canDelete = window.permissions.canDeleteUser(user.role) && user.id != currentUser.id;
                
                return `
                            <tr>
                                <td>#${user.id}</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="${getProfileImageUrl(user)}" 
                                            style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;"
                                            onerror="this.src='../assets/default-avatar.png'">
                                        ${user.name}
                                    </div>
                                </td>
                                <td>${user.email}</td>
                                <td><span class="role-badge ${user.role || 'user'}">${(user.role || 'user').replace('_', ' ').toUpperCase()}</span></td>
                                <td><span class="status-badge ${user.is_verified == 1 ? 'active' : 'pending'}">
                                    ${user.is_verified == 1 ? 'Active' : 'Pending'}
                                </span></td>
                                <td>${user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}</td>
                                <td>
                                    ${canEdit ? `
                                        <button class="action-btn" onclick="window.editUserFull(${user.id})" style="width: 30px; height: 30px;" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    ` : ''}
                                    ${canDelete ? `
                                        <button class="action-btn" onclick="window.deleteUser(${user.id}, '${user.name.replace(/'/g, "\\'")}')" style="width: 30px; height: 30px; color: #dc3545;" title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : ''}
                                </td>
                            </tr>
                        `;
            }).join('') :
            '<tr><td colspan="7" style="text-align: center; padding: 50px;">No users found</td></tr>'
        }
                </tbody>
            </table>
        </div>
    `;
}

// Helper function to get correct profile image URL
function getProfileImageUrl(user) {
    // Return default if no user or no profile image
    if (!user || !user.profile_image || 
        user.profile_image === 'null' || 
        user.profile_image === '' || 
        user.profile_image === 'default.png') {
        return '../assets/default-avatar.png';
    }

    const filename = user.profile_image.split('/').pop();
    
    return `http://localhost/book_swap_api/profile_images/${filename}`;
}

// Enhanced edit user function - fetches full user details
async function editUserFull(userId) {
    try {
        const token = localStorage.getItem('token');
        
        // Show loading modal
        window.showModal('Edit User', '<div style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top: 15px;">Loading user details...</p></div>', null, false);
        
        // Fetch full user details
        const response = await fetch('http://localhost/book_swap_api/admin/api/get_profile.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const result = await response.json();
        
        if (result.success && result.user) {
            const user = result.user;
            const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
            
            // Get available roles for editing based on current user's role
            let roleOptions = '';
            const availableRoles = getAvailableRolesForEditing(currentUser.role, user.role);
            
            availableRoles.forEach(role => {
                const selected = role === user.role ? 'selected' : '';
                roleOptions += `<option value="${role}" ${selected}>${role.replace('_', ' ').toUpperCase()}</option>`;
            });
            
            // Close loading modal and open edit modal
            window.closeModal();
            
            window.showModal('Edit User Details', `
                <form id="editUserFullForm" style="max-height: 70vh; overflow-y: auto; padding-right: 10px;">
                    <input type="hidden" id="editUserId" value="${user.id}">
                    
                    <!-- Profile Image Preview -->
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div style="position: relative; width: 100px; height: 100px; margin: 0 auto;">
                            <img src="${getProfileImageUrl(user)}" 
                                 id="editProfilePreview"
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #00a884;">
                        </div>
                        <p style="color: #667781; font-size: 12px; margin-top: 5px;">Profile image can be changed in user's profile</p>
                    </div>
                    
                    <!-- Basic Information -->
                    <h4 style="margin: 15px 0 10px; color: #111b21;">Basic Information</h4>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Full Name</label>
                        <input type="text" id="editName" class="search-input" style="background: white; border: 1px solid #e9edef;" value="${user.name || ''}" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email Address</label>
                        <input type="email" id="editEmail" class="search-input" style="background: white; border: 1px solid #e9edef;" value="${user.email || ''}" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Role</label>
                        <select id="editRole" class="search-input" style="background: white; border: 1px solid #e9edef;">
                            ${roleOptions}
                        </select>
                    </div>
                    
                    <!-- Profile Information (for user role) -->
                    <h4 style="margin: 20px 0 10px; color: #111b21;">Profile Information</h4>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Student ID</label>
                        <input type="text" id="editStudentId" class="search-input" style="background: white; border: 1px solid #e9edef;" value="${user.student_id || ''}" placeholder="Optional">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Department</label>
                        <input type="text" id="editDepartment" class="search-input" style="background: white; border: 1px solid #e9edef;" value="${user.department || ''}" placeholder="Optional">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Current Location</label>
                        <input type="text" id="editCurrentLocation" class="search-input" style="background: white; border: 1px solid #e9edef;" value="${user.current_location || ''}" placeholder="Optional">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Permanent Location</label>
                        <input type="text" id="editPermanentLocation" class="search-input" style="background: white; border: 1px solid #e9edef;" value="${user.permanent_location || ''}" placeholder="Optional">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bio</label>
                        <textarea id="editBio" class="search-input" style="background: white; border: 1px solid #e9edef; min-height: 80px; resize: vertical;" placeholder="Optional">${user.bio || ''}</textarea>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px;">
                        <p style="margin: 0; font-size: 12px; color: #667781;">
                            <i class="fas fa-info-circle"></i> 
                            Password cannot be changed here. User can change it in their profile.
                        </p>
                    </div>
                </form>
            `, async () => {
                await updateUserFull();
            }, 'Save Changes');
            
        } else {
            window.closeModal();
            alert(result.message || 'Failed to load user details');
        }
        
    } catch (error) {
        console.error('Error loading user details:', error);
        window.closeModal();
        alert('Error loading user details. Please check your connection.');
    }
}

// Get available roles for editing based on current user's role
function getAvailableRolesForEditing(currentUserRole, targetUserRole) {
    switch (currentUserRole) {
        case 'super_admin':
            return ['user', 'viewer', 'admin', 'super_admin'];
        case 'admin':
            // Admin can only change to user or viewer, and cannot change super_admin or admin
            if (targetUserRole === 'user' || targetUserRole === 'viewer') {
                return ['user', 'viewer'];
            }
            return [targetUserRole]; // Return only current role if it's admin/super_admin
        case 'viewer':
            // Viewer can only change to user, and only if target is user
            if (targetUserRole === 'user') {
                return ['user'];
            }
            return [targetUserRole]; // Return only current role if it's not user
        default:
            return [targetUserRole];
    }
}

// Update user full details
async function updateUserFull() {
    const userId = document.getElementById('editUserId')?.value;
    const name = document.getElementById('editName')?.value;
    const email = document.getElementById('editEmail')?.value;
    const role = document.getElementById('editRole')?.value;
    const studentId = document.getElementById('editStudentId')?.value;
    const department = document.getElementById('editDepartment')?.value;
    const currentLocation = document.getElementById('editCurrentLocation')?.value;
    const permanentLocation = document.getElementById('editPermanentLocation')?.value;
    const bio = document.getElementById('editBio')?.value;

    // Validation
    if (!name || !email || !role) {
        alert('Name, Email, and Role are required');
        return;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return;
    }

    try {
        const token = localStorage.getItem('token');
        
        // Show loading state on save button
        const saveBtn = document.querySelector('.modal-footer .primary-btn');
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Updating...';
        saveBtn.disabled = true;

        const response = await fetch('http://localhost/book_swap_api/admin/api/update-user.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: parseInt(userId),
                name: name,
                email: email,
                role: role,
                student_id: studentId || null,
                department: department || null,
                current_location: currentLocation || null,
                permanent_location: permanentLocation || null,
                bio: bio || null
            })
        });

        const result = await response.json();

        // Reset button
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;

        if (result.success) {
            alert('User updated successfully!');
            window.closeModal();
            
            // Update the local allUsers array
            const index = allUsers.findIndex(u => u.id == userId);
            if (index !== -1) {
                allUsers[index] = { ...allUsers[index], ...result.user };
            }
            
            // Refresh the displayed users
            const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
            if (currentUserSearchTerm) {
                const filteredUsers = allUsers.filter(user => {
                    return user.name.toLowerCase().includes(currentUserSearchTerm) ||
                           user.email.toLowerCase().includes(currentUserSearchTerm);
                });
                renderUsersTable(filteredUsers, currentUser, currentUserSearchTerm);
            } else {
                renderUsersTable(allUsers, currentUser);
            }
        } else {
            alert(result.message || 'Failed to update user');
        }
    } catch (error) {
        console.error('Error updating user:', error);
        alert('Error updating user. Please check your connection.');
        
        // Reset button
        const saveBtn = document.querySelector('.modal-footer .primary-btn');
        if (saveBtn) {
            saveBtn.textContent = 'Save Changes';
            saveBtn.disabled = false;
        }
    }
}

function editUser(userId) {
    // Call the enhanced version
    editUserFull(userId);
}

async function updateUserRole(userId, newRole) {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('http://localhost/book_swap_api/admin/api/users.php', {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: userId,
                role: newRole
            })
        });

        const result = await response.json();

        if (response.ok) {
            alert('User role updated successfully');
            window.closeModal();
            
            // Update the local allUsers array
            const index = allUsers.findIndex(u => u.id == userId);
            if (index !== -1) {
                allUsers[index].role = newRole;
            }
            
            // Refresh the displayed users
            const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
            if (currentUserSearchTerm) {
                const filteredUsers = allUsers.filter(user => {
                    return user.name.toLowerCase().includes(currentUserSearchTerm) ||
                           user.email.toLowerCase().includes(currentUserSearchTerm);
                });
                renderUsersTable(filteredUsers, currentUser, currentUserSearchTerm);
            } else {
                renderUsersTable(allUsers, currentUser);
            }
        } else {
            alert(result.message || 'Failed to update user role');
        }
    } catch (error) {
        console.error('Error updating user role:', error);
        alert('Error updating user role');
    }
}

function deleteUser(userId, userName) {
    if (confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
        console.log('Deleting user:', userId);
        
        const token = localStorage.getItem('token');
        
        // Show loading state
        const deleteBtn = event?.target?.closest('button');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteBtn.disabled = true;
        }
        
        fetch('http://localhost/book_swap_api/admin/api/users.php', {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: userId
            })
        })
        .then(async response => {
            const result = await response.json();
            
            if (response.ok) {
                alert('User deleted successfully');
                
                // Remove from local array
                allUsers = allUsers.filter(u => u.id != userId);
                
                // Refresh the displayed users
                const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
                if (currentUserSearchTerm) {
                    const filteredUsers = allUsers.filter(user => {
                        return user.name.toLowerCase().includes(currentUserSearchTerm) ||
                               user.email.toLowerCase().includes(currentUserSearchTerm);
                    });
                    renderUsersTable(filteredUsers, currentUser, currentUserSearchTerm);
                } else {
                    renderUsersTable(allUsers, currentUser);
                }
            } else {
                alert(result.message || 'Failed to delete user');
                // Reset button if failed
                if (deleteBtn) {
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteBtn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error deleting user:', error);
            alert('Error deleting user. Please check your connection.');
            // Reset button if failed
            if (deleteBtn) {
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                deleteBtn.disabled = false;
            }
        });
    }
}

function showAddUserModal() {
    // Get available roles for user creation based on current user's role
    const availableRoles = window.permissions.getAvailableRolesForCreation();
    
    let roleOptions = '';
    availableRoles.forEach(role => {
        roleOptions += `<option value="${role}">${role.replace('_', ' ').toUpperCase()}</option>`;
    });
    
    window.showModal('Add New User', `
        <form id="addUserForm" onsubmit="event.preventDefault();">
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Full Name <span style="color: #dc3545;">*</span></label>
                <input type="text" id="userName" class="search-input" style="background: white; border: 1px solid #e9edef;" placeholder="Enter full name" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email Address <span style="color: #dc3545;">*</span></label>
                <input type="email" id="userEmail" class="search-input" style="background: white; border: 1px solid #e9edef;" placeholder="Enter email address" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Password <span style="color: #dc3545;">*</span></label>
                <div style="position: relative;">
                    <input type="password" id="userPassword" class="search-input" style="background: white; border: 1px solid #e9edef; padding-right: 45px;" placeholder="Enter password" required>
                    <i class="fas fa-eye-slash toggle-password" onclick="window.togglePasswordField('userPassword', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #667781;"></i>
                </div>
                <small style="color: #667781;">Password will be encrypted with MD5</small>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Role <span style="color: #dc3545;">*</span></label>
                <select id="userRole" class="search-input" style="background: white; border: 1px solid #e9edef;" required>
                    <option value="">Select Role</option>
                    ${roleOptions}
                </select>
            </div>
            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px;">
                <p style="margin: 0; font-size: 13px; color: #667781;">
                    <i class="fas fa-info-circle"></i> 
                    User will be created with is_verified = 1 (automatically verified)
                </p>
            </div>
        </form>
    `, async () => {
        await addUser();
    });
}

async function addUser() {
    const name = document.getElementById('userName')?.value;
    const email = document.getElementById('userEmail')?.value;
    const password = document.getElementById('userPassword')?.value;
    const role = document.getElementById('userRole')?.value;

    // Validation
    if (!name || !email || !password || !role) {
        alert('Please fill in all required fields');
        return;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return;
    }

    // Password validation (minimum 6 characters)
    if (password.length < 6) {
        alert('Password must be at least 6 characters long');
        return;
    }

    try {
        const token = localStorage.getItem('token');
        
        // Show loading state on save button
        const saveBtn = document.querySelector('.modal-footer .primary-btn');
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Creating...';
        saveBtn.disabled = true;

        const response = await fetch('http://localhost/book_swap_api/admin/api/add-user.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: name,
                email: email,
                password: password,
                role: role
            })
        });

        const result = await response.json();

        // Reset button
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;

        if (response.ok) {
            alert('User created successfully!');
            window.closeModal();
            window.loadUsers(); // Refresh the users list
        } else {
            alert(result.message || 'Failed to create user');
        }
    } catch (error) {
        console.error('Error creating user:', error);
        alert('Error creating user. Please check your connection.');
        
        // Reset button
        const saveBtn = document.querySelector('.modal-footer .primary-btn');
        if (saveBtn) {
            saveBtn.textContent = 'Save';
            saveBtn.disabled = false;
        }
    }
}

// Toggle password visibility
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

// Make functions available globally
window.loadUsers = loadUsers;
window.handleUserSearch = handleUserSearch;
window.clearUserSearch = clearUserSearch;
window.editUser = editUser;
window.editUserFull = editUserFull;
window.deleteUser = deleteUser;
window.showAddUserModal = showAddUserModal;
window.addUser = addUser;
window.togglePasswordField = togglePasswordField;
window.getProfileImageUrl = getProfileImageUrl;