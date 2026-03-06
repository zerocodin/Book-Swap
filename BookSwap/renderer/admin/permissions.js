// Permissions helper for role-based access control

// Check if user has permission to perform an action
function hasPermission(action, targetUserRole = null) {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const userRole = currentUser.role;

    // Super admin can do everything
    if (userRole === 'super_admin') {
        return true;
    }

    // Define permissions by role
    switch (userRole) {
        case 'admin':
            return getAdminPermission(action, targetUserRole);
        case 'viewer':
            return getViewerPermission(action, targetUserRole);
        default:
            return false;
    }
}

// Admin permissions
function getAdminPermission(action, targetUserRole) {
    // Admin can view everything
    if (action === 'view') {
        return true;
    }

    // Admin can only edit/view users with role 'user' or 'viewer'
    if (targetUserRole) {
        // Admin cannot edit super_admin or admin
        if (targetUserRole === 'super_admin' || targetUserRole === 'admin') {
            return false;
        }
    }

    // Admin permissions by action
    const adminPermissions = {
        'add_user': true,           // Can add users (but role selection limited)
        'edit_user': true,          // Can edit users (but limited to user/viewer)
        'delete_user': true,         // Can delete users (but limited to user/viewer)
        'add_admin': true,           // Can add admins (but only viewer role)
        'edit_admin': false,         // Cannot edit admins
        'delete_admin': false,       // Cannot delete admins
        'manage_posts': true,        // Can manage posts
        'view_dashboard': true,      // Can view dashboard
        'view_settings': false,      // Cannot view settings
        'manage_permissions': false  // Cannot manage permissions
    };

    return adminPermissions[action] || false;
}

// Viewer permissions
function getViewerPermission(action, targetUserRole) {
    // Viewer can only view dashboard and users/posts
    if (action === 'view') {
        return true;
    }

    // Viewer can only edit/delete users with role 'user'
    if (targetUserRole && targetUserRole !== 'user') {
        return false;
    }

    // Viewer permissions by action
    const viewerPermissions = {
        'add_user': true,           // Can add users (only user role)
        'edit_user': true,          // Can edit users (only user role)
        'delete_user': true,         // Can delete users (only user role)
        'add_admin': false,          // Cannot add admins
        'edit_admin': false,         // Cannot edit admins
        'delete_admin': false,       // Cannot delete admins
        'manage_posts': true,        // Can manage posts
        'view_dashboard': true,      // Can view dashboard
        'view_settings': false,      // Cannot view settings
        'manage_permissions': false,  // Cannot manage permissions
        'view_manage_admins': false   // Cannot view manage admins page
    };

    return viewerPermissions[action] || false;
}

// Get available roles for user creation based on current user's role
function getAvailableRolesForCreation() {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const userRole = currentUser.role;

    switch (userRole) {
        case 'super_admin':
            return ['user', 'viewer', 'admin', 'super_admin'];
        case 'admin':
            return ['user', 'viewer']; // Admin can only create user and viewer
        case 'viewer':
            return ['user']; // Viewer can only create user
        default:
            return [];
    }
}

// Get available roles for admin creation
function getAvailableAdminRolesForCreation() {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const userRole = currentUser.role;

    switch (userRole) {
        case 'super_admin':
            return ['viewer', 'admin', 'super_admin'];
        case 'admin':
            return ['viewer']; // Admin can only create viewer
        case 'viewer':
            return []; // Viewer cannot create admins
        default:
            return [];
    }
}

// Check if user can edit another user based on roles
function canEditUser(targetUserRole) {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const userRole = currentUser.role;

    if (userRole === 'super_admin') {
        return true;
    }

    if (userRole === 'admin') {
        // Admin can edit user and viewer, but not admin or super_admin
        return targetUserRole === 'user' || targetUserRole === 'viewer';
    }

    if (userRole === 'viewer') {
        // Viewer can only edit user
        return targetUserRole === 'user';
    }

    return false;
}

// Check if user can delete another user based on roles
function canDeleteUser(targetUserRole) {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const userRole = currentUser.role;

    if (userRole === 'super_admin') {
        return true;
    }

    if (userRole === 'admin') {
        // Admin can delete user and viewer
        return targetUserRole === 'user' || targetUserRole === 'viewer';
    }

    if (userRole === 'viewer') {
        // Viewer can only delete user
        return targetUserRole === 'user';
    }

    return false;
}

// Check if manage admins should be visible
function canViewManageAdmins() {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const userRole = currentUser.role;
    
    // Super admin and admin can view manage admins
    return userRole === 'super_admin' || userRole === 'admin';
}

// Export functions
window.permissions = {
    hasPermission,
    getAvailableRolesForCreation,
    getAvailableAdminRolesForCreation,
    canEditUser,
    canDeleteUser,
    canViewManageAdmins
};