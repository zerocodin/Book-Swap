let allAdmins = []; // Store all admins for searching
let currentAdminSearchTerm = '';

console.log('admins.js loaded successfully');

async function loadManageAdmins() {
    const contentArea = document.getElementById('contentArea');
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');

    try {
        console.log('=== LOADING MANAGE ADMINS ===');
        console.log('Current user:', currentUser);

        const token = localStorage.getItem('token');

        const response = await fetch('http://localhost/book_swap_api/admin/api/users.php', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }

        const allUsers = await response.json();
        console.log('All users loaded:', allUsers.length);

        // Filter only admin roles (super_admin, admin, viewer)
        allAdmins = allUsers.filter(user =>
            ['super_admin', 'admin', 'viewer'].includes(user.role)
        );

        console.log('Filtered admins:', allAdmins.length);
        console.log('Admin list:', allAdmins.map(a => ({ name: a.name, role: a.role })));

        // Force render the table
        renderAdminsTable(allAdmins, currentUser);

    } catch (error) {
        console.error('Manage Admins error:', error);
        contentArea.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Failed to load admins: ${error.message}
                <br><br>
                <button class="primary-btn" onclick="window.loadManageAdmins()">Try Again</button>
            </div>
        `;
    }
}

// Handle admin search
function handleAdminSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    currentAdminSearchTerm = searchTerm;

    const filteredAdmins = allAdmins.filter(admin => {
        const roleText = (admin.role || '').replace('_', ' ').toLowerCase();
        return admin.name.toLowerCase().includes(searchTerm) ||
            admin.email.toLowerCase().includes(searchTerm) ||
            roleText.includes(searchTerm);
    });

    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    renderAdminsTable(filteredAdmins, currentUser, searchTerm);
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

// Render admins table with role-based permissions
function renderAdminsTable(admins, currentUser, searchTerm = '') {
    const contentArea = document.getElementById('contentArea');

    console.log('Rendering admins table with', admins.length, 'admins');
    console.log('Admins data:', admins);
    
    // Check if current user can add admins
    const canAddAdmin = currentUser.role === 'super_admin' || 
                       (currentUser.role === 'admin' && window.permissions.hasPermission('add_admin'));

    // Build the table HTML
    let tableHTML = `
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3>Admin Management</h3>
            ${canAddAdmin ?
            '<button class="primary-btn" onclick="window.showAddAdminModal()"><i class="fas fa-plus"></i> Add Admin</button>' :
            ''}
        </div>
    `;

    if (searchTerm) {
        tableHTML += `
            <div class="search-results-info">
                <i class="fas fa-search"></i> Found ${admins.length} admin${admins.length !== 1 ? 's' : ''} matching "${searchTerm}"
                <span class="clear-search" onclick="window.clearAdminSearch()">Clear search</span>
            </div>
        `;
    }

    if (admins.length === 0) {
        tableHTML += `
            <div class="table-container">
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-user-shield" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                    <p style="color: #667781; font-size: 16px;">No admins found</p>
                    ${canAddAdmin ?
                '<p style="color: #667781; font-size: 14px; margin-top: 10px;">Click the "Add Admin" button to create a new admin</p>' :
                ''}
                </div>
            </div>
        `;
    } else {
        tableHTML += `
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
                        ${admins.map(admin => {
                            // Check if current user can edit this admin
                            const canEdit = currentUser.role === 'super_admin' || 
                                           (currentUser.role === 'admin' && admin.role === 'viewer');
                            // Check if current user can delete this admin
                            const canDelete = (currentUser.role === 'super_admin' && admin.id != currentUser.id) ||
                                             (currentUser.role === 'admin' && admin.role === 'viewer' && admin.id != currentUser.id);
                            
                            return `
                            <tr>
                                <td>#${admin.id}</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="${getProfileImageUrl(admin)}" 
                                            style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;"
                                            onerror="this.src='../assets/default-avatar.png'">
                                        ${admin.name}
                                        ${admin.id === currentUser.id ? ' <span style="color: #00a884; font-weight: 500;">(You)</span>' : ''}
                                    </div>
                                </td>
                                <td>${admin.email}</td>
                                <td>
                                    <span class="admin-role-badge ${admin.role}">
                                        ${(admin.role || '').replace('_', ' ').toUpperCase()}
                                    </span>
                                </td>
                                <td><span class="status-badge ${admin.is_verified == 1 ? 'active' : 'pending'}">
                                    ${admin.is_verified == 1 ? 'Active' : 'Pending'}
                                </span></td>
                                <td>${admin.created_at ? new Date(admin.created_at).toLocaleDateString() : 'N/A'}</td>
                                <td>
                                    ${canEdit ? `
                                        <button class="action-btn" onclick="window.editAdminFull(${admin.id})" style="width: 30px; height: 30px;" title="Edit Admin">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    ` : ''}
                                    ${canDelete ? `
                                        <button class="action-btn" onclick="window.deleteAdmin(${admin.id}, '${admin.name.replace(/'/g, "\\'")}')" style="width: 30px; height: 30px; color: #dc3545;" title="Delete Admin">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : ''}
                                </td>
                            </tr>
                        `}).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    contentArea.innerHTML = tableHTML;
}

// Edit admin function
async function editAdminFull(adminId) {
    try {
        const token = localStorage.getItem('token');

        window.showModal('Edit Admin', '<div style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top: 15px;">Loading admin details...</p></div>', null, false);

        const response = await fetch('http://localhost/book_swap_api/admin/api/get_profile.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: adminId })
        });

        const result = await response.json();

        if (result.success && result.user) {
            const user = result.user;
            const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
            
            // Determine available roles for editing
            let roleOptions = '';
            if (currentUser.role === 'super_admin') {
                roleOptions = `
                    <option value="viewer" ${user.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                    <option value="super_admin" ${user.role === 'super_admin' ? 'selected' : ''}>Super Admin</option>
                `;
            } else if (currentUser.role === 'admin' && user.role === 'viewer') {
                // Admin can only edit viewer
                roleOptions = `
                    <option value="viewer" ${user.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                `;
            } else {
                // Cannot edit
                alert('You do not have permission to edit this admin');
                window.closeModal();
                return;
            }

            window.closeModal();

            window.showModal('Edit Admin Details', `
                <form id="editAdminFullForm" style="max-height: 70vh; overflow-y: auto; padding-right: 10px;">
                    <input type="hidden" id="editUserId" value="${user.id}">
                    
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div style="position: relative; width: 100px; height: 100px; margin: 0 auto;">
                            <img src="${getProfileImageUrl(user)}" 
                                 id="editProfilePreview"
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #00a884;">
                        </div>
                    </div>
                    
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
                </form>
            `, async () => {
                await updateAdminFull();
            }, 'Save Changes');

        } else {
            window.closeModal();
            alert(result.message || 'Failed to load admin details');
        }

    } catch (error) {
        console.error('Error loading admin details:', error);
        window.closeModal();
        alert('Error loading admin details. Please check your connection.');
    }
}

// Update admin full details
async function updateAdminFull() {
    const userId = document.getElementById('editUserId')?.value;
    const name = document.getElementById('editName')?.value;
    const email = document.getElementById('editEmail')?.value;
    const role = document.getElementById('editRole')?.value;

    if (!name || !email || !role) {
        alert('Name, Email, and Role are required');
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return;
    }

    try {
        const token = localStorage.getItem('token');

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
                role: role
            })
        });

        const result = await response.json();

        saveBtn.textContent = originalText;
        saveBtn.disabled = false;

        if (result.success) {
            alert('Admin updated successfully!');
            window.closeModal();
            loadManageAdmins();
        } else {
            alert(result.message || 'Failed to update admin');
        }
    } catch (error) {
        console.error('Error updating admin:', error);
        alert('Error updating admin. Please check your connection.');

        const saveBtn = document.querySelector('.modal-footer .primary-btn');
        if (saveBtn) {
            saveBtn.textContent = 'Save Changes';
            saveBtn.disabled = false;
        }
    }
}

// Delete admin function
function deleteAdmin(adminId, adminName) {
    if (confirm(`Are you sure you want to delete admin "${adminName}"? This action cannot be undone.`)) {
        const token = localStorage.getItem('token');

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
            body: JSON.stringify({ id: adminId })
        })
            .then(async response => {
                const result = await response.json();

                if (response.ok) {
                    alert('Admin deleted successfully');
                    loadManageAdmins();
                } else {
                    alert(result.message || 'Failed to delete admin');
                    if (deleteBtn) {
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                        deleteBtn.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error deleting admin:', error);
                alert('Error deleting admin. Please check your connection.');
                if (deleteBtn) {
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteBtn.disabled = false;
                }
            });
    }
}

// Show add admin modal with role-based options
function showAddAdminModal() {
    // Get available roles for admin creation
    const availableRoles = window.permissions.getAvailableAdminRolesForCreation();
    
    if (availableRoles.length === 0) {
        alert('You do not have permission to add admins');
        return;
    }
    
    let roleOptions = '';
    availableRoles.forEach(role => {
        roleOptions += `<option value="${role}">${role.replace('_', ' ').toUpperCase()}</option>`;
    });

    window.showModal('Add New Admin', `
        <form id="addAdminForm" onsubmit="event.preventDefault();">
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
        </form>
    `, async () => {
        await addAdmin();
    }, 'Create Admin');
}

// Add admin function
async function addAdmin() {
    const name = document.getElementById('userName')?.value;
    const email = document.getElementById('userEmail')?.value;
    const password = document.getElementById('userPassword')?.value;
    const role = document.getElementById('userRole')?.value;

    if (!name || !email || !password || !role) {
        alert('Please fill in all required fields');
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return;
    }

    if (password.length < 6) {
        alert('Password must be at least 6 characters long');
        return;
    }

    try {
        const token = localStorage.getItem('token');

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

        saveBtn.textContent = originalText;
        saveBtn.disabled = false;

        if (response.ok) {
            alert('Admin created successfully!');
            window.closeModal();
            loadManageAdmins();
        } else {
            alert(result.message || 'Failed to create admin');
        }
    } catch (error) {
        console.error('Error creating admin:', error);
        alert('Error creating admin. Please check your connection.');

        const saveBtn = document.querySelector('.modal-footer .primary-btn');
        if (saveBtn) {
            saveBtn.textContent = 'Create Admin';
            saveBtn.disabled = false;
        }
    }
}

// Clear admin search
function clearAdminSearch() {
    currentAdminSearchTerm = '';
    const globalSearchInput = document.getElementById('globalSearchInput');
    if (globalSearchInput) {
        globalSearchInput.value = '';
    }
    
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    renderAdminsTable(allAdmins, currentUser);
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
window.loadManageAdmins = loadManageAdmins;
window.handleAdminSearch = handleAdminSearch;
window.clearAdminSearch = clearAdminSearch;
window.editAdminFull = editAdminFull;
window.deleteAdmin = deleteAdmin;
window.showAddAdminModal = showAddAdminModal;
window.addAdmin = addAdmin;
window.togglePasswordField = togglePasswordField;