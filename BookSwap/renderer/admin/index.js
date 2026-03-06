let currentUser = null;
let currentPage = 'dashboard';

// Verify token on load
async function verifyToken() {
    const token = localStorage.getItem('token');
    if (!token) return false;

    try {
        const response = await fetch('http://localhost/book_swap_api/admin/api/dashboard-stats.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        return response.ok;
    } catch (error) {
        console.error('Token verification error:', error);
        return false;
    }
}

// Initialize admin dashboard - SINGLE DOMContentLoaded event
document.addEventListener('DOMContentLoaded', async function () {
    console.log('Admin panel initializing...');

    // Check if user is logged in
    const token = localStorage.getItem('token');
    const userStr = localStorage.getItem('user');

    if (!token || !userStr) {
        console.log('No token or user found, redirecting to login');
        navigateToLogin();
        return;
    }

    try {
        currentUser = JSON.parse(userStr);

        // Verify token is still valid
        const isValid = await verifyToken();
        if (!isValid) {
            console.log('Token expired, redirecting to login');
            alert('Session expired. Please login again.');
            localStorage.clear();
            navigateToLogin();
            return;
        }

        // Check if user has admin privileges
        if (!['super_admin', 'admin', 'viewer'].includes(currentUser.role)) {
            alert('Access denied. Admin privileges required.');
            navigateToLogin();
            return;
        }

        // Update UI with user info - do this immediately
        updateUserInfo();

        // Show/hide navigation items based on role
        setupNavigationVisibility();

        // Force sidebar profile image update after a short delay to ensure DOM is ready
        setTimeout(() => {
            refreshSidebarProfile();
        }, 100);

        // Load initial page (dashboard)
        loadPage('dashboard');

        // Setup navigation
        setupNavigation();

        // Setup search functionality
        setupSearch();

        console.log('Admin panel initialized successfully');
    } catch (error) {
        console.error('Initialization error:', error);
        alert('Error loading admin panel');
    }
});

// Helper function to navigate to login
function navigateToLogin() {
    try {
        // Try Electron navigation first
        if (window.electronAPI && window.electronAPI.navigate) {
            window.electronAPI.navigate('login');
        } else {
            // Fallback to direct navigation
            window.location.href = '../pages/login.html';
        }
    } catch (error) {
        console.error('Navigation error:', error);
        window.location.href = '../pages/login.html';
    }
}

// Update user info in sidebar
function updateUserInfo() {
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // Update name
    const adminNameElement = document.getElementById('adminName');
    if (adminNameElement) {
        adminNameElement.textContent = user.name || 'Admin User';
    }

    // Update role badge
    const adminRoleElement = document.getElementById('adminRole');
    if (adminRoleElement) {
        adminRoleElement.textContent = (user.role || 'user').replace('_', ' ').toUpperCase();
        adminRoleElement.className = `role-badge ${user.role || 'user'}`;
    }

    // Update profile image
    updateProfileImage();
}

// Setup navigation visibility based on role
function setupNavigationVisibility() {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    // Manage Admins nav item
    const manageAdminsNav = document.querySelector('.nav-item[data-page="manage-admins"]');
    if (manageAdminsNav) {
        if (user.role === 'viewer') {
            manageAdminsNav.style.display = 'none';
        } else {
            manageAdminsNav.style.display = 'flex';
        }
    }
    
    // Settings nav item - only super_admin can see
    const settingsNav = document.querySelector('.nav-item[data-page="settings"]');
    if (settingsNav) {
        if (user.role === 'super_admin') {
            settingsNav.style.display = 'flex';
        } else {
            settingsNav.style.display = 'none';
        }
    }
}

// Add this function to get profile image URL correctly
function getProfileImageUrl(user) {
    if (!user) return '../assets/default-avatar.png';
    
    if (user.profile_image && 
        user.profile_image !== 'null' && 
        user.profile_image !== '' && 
        user.profile_image !== 'default.png') {
        
        // Extract filename to handle both storage formats
        const filename = user.profile_image.split('/').pop();
        return `http://localhost/book_swap_api/profile_images/${filename}`;
    }
    
    return '../assets/default-avatar.png';
}

// Update ProfileImage function
function updateProfileImage() {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const profileImg = document.getElementById('profileImg');

    console.log('Updating profile image for user:', user);

    if (profileImg) {
        const imageUrl = getProfileImageUrl(user);
        console.log('Setting profile image to:', imageUrl);
        profileImg.src = imageUrl;

        profileImg.onerror = function () {
            console.log('Image failed to load, using default');
            this.src = '../assets/default-avatar.png';
        };
    } else {
        console.error('Profile image element not found - will retry');
        setTimeout(updateProfileImage, 500);
    }
}

// Also add this function to force update the sidebar image
function refreshSidebarProfile() {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const profileImg = document.getElementById('profileImg');

    if (profileImg && user.profile_image) {
        const imageUrl = `http://localhost/book_swap_api/profile_images/${user.profile_image}`;
        console.log('Force refreshing sidebar image to:', imageUrl);
        profileImg.src = imageUrl;
    }
}

// Setup navigation
function setupNavigation() {
    const navItems = document.querySelectorAll('.nav-item[data-page]');

    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            const page = this.dataset.page;

            // Update active state
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');

            // Load the page
            loadPage(page);
        });
    });
}

// Setup search functionality
function setupSearch() {
    const searchInput = document.getElementById('searchInput');

    if (searchInput) {
        searchInput.addEventListener('input', window.debounce(function (e) {
            const searchTerm = e.target.value.toLowerCase();

            // Filter nav items based on search
            const navItems = document.querySelectorAll('.nav-item[data-page]');

            navItems.forEach(item => {
                const title = item.querySelector('.nav-title').textContent.toLowerCase();
                const subtitle = item.querySelector('.nav-subtitle').textContent.toLowerCase();

                if (title.includes(searchTerm) || subtitle.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }, 300));
    }
}

// Load page content
async function loadPage(page) {
    currentPage = page;

    // Update header title
    const titleElement = document.getElementById('currentPageTitle');
    const navItem = document.querySelector(`.nav-item[data-page="${page}"]`);
    if (navItem) {
        const navTitle = navItem.querySelector('.nav-title').textContent;
        titleElement.textContent = navTitle;
    }

    // Show/hide search based on page
    const headerSearch = document.getElementById('headerSearch');
    if (page === 'users' || page === 'manage-admins') {
        headerSearch.style.display = 'block';

        // Update placeholder based on page
        const searchInput = document.getElementById('globalSearchInput');
        if (searchInput) {
            if (page === 'users') {
                searchInput.placeholder = 'Search users by name or email...';
            } else if (page === 'manage-admins') {
                searchInput.placeholder = 'Search admins by name, email or role...';
            } else if (page === 'posts') {
                searchInput.placeholder = 'Search by title, writer or poster...';
            }
            // Clear search input when switching pages
            searchInput.value = '';

            // Remove existing event listeners and add new page-specific one
            searchInput.removeEventListener('input', window.handleUserSearch);
            searchInput.removeEventListener('input', window.handleAdminSearch);
            searchInput.removeEventListener('input', window.handlePostSearch);

            // Add appropriate event listener based on page
            if (page === 'users') {
                searchInput.addEventListener('input', window.handleUserSearch);
            } else if (page === 'manage-admins') {
                searchInput.addEventListener('input', window.handleAdminSearch);
            } else if (page === 'posts') {
                searchInput.addEventListener('input', window.handlePostSearch);
            }
        }
    } else {
        headerSearch.style.display = 'none';
    }

    // Show loading
    const contentArea = document.getElementById('contentArea');
    contentArea.innerHTML = `
        <div class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading ${page}...
        </div>
    `;

    // Load page content based on page type
    try {
        switch (page) {
            case 'dashboard':
                if (window.loadDashboard) await window.loadDashboard();
                break;
            case 'users':
                if (window.loadUsers) await window.loadUsers();
                break;
            case 'posts':
                if (window.loadPosts) await window.loadPosts();
                break;
            case 'manage-admins':
                if (window.loadManageAdmins) await window.loadManageAdmins();
                break;
            case 'settings':
                if (window.loadSettings) await window.loadSettings();
                break;
            default:
                contentArea.innerHTML = '<div class="error">Page not found</div>';
        }
    } catch (error) {
        console.error(`Error loading ${page}:`, error);
        contentArea.innerHTML = `
            <div class="error-message" style="color: #dc3545; text-align: center; padding: 50px;">
                <i class="fas fa-exclamation-circle"></i> Failed to load ${page}. Please try again.
                <br><small>${error.message}</small>
            </div>
        `;
    }
}

// Refresh current page
function refreshCurrentPage() {
    loadPage(currentPage);
}

// Handle logout
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        // Clear all storage
        localStorage.clear();
        sessionStorage.clear();

        // Navigate to login
        navigateToLogin();
    }
}

// Make functions globally available
window.updateUserInfo = updateUserInfo;
window.updateProfileImage = updateProfileImage;
window.refreshSidebarProfile = refreshSidebarProfile;
window.loadPage = loadPage;
window.refreshCurrentPage = refreshCurrentPage;
window.handleLogout = handleLogout;