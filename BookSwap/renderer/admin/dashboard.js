// Dashboard Module
let statsData = null;

async function loadDashboard() {
    const contentArea = document.getElementById('contentArea');

    try {
        const token = localStorage.getItem('token');
        console.log('Fetching dashboard with token:', token);

        const response = await fetch('http://localhost/book_swap_api/admin/api/dashboard-stats.php', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        console.log('Dashboard response status:', response.status);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`Failed to fetch dashboard data: ${response.status}`);
        }

        const stats = await response.json();
        console.log('Dashboard stats:', stats);

        statsData = stats;

        // Update badges with counts
        document.getElementById('usersBadge').textContent = stats.total_users || 0;
        document.getElementById('postsBadge').textContent = stats.total_posts || 0;
        document.getElementById('adminsBadge').textContent = stats.total_admins || 0;

        // Render dashboard with 4 stat boxes
        contentArea.innerHTML = `
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-title">Total Users</div>
                    <div class="stat-value">${stats.total_users || 0}</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                    <div class="stat-title">Total Posts</div>
                    <div class="stat-value">${stats.total_posts || 0}</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-title">Today's Posts</div>
                    <div class="stat-value">${stats.today_posts || 0}</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                    <div class="stat-title">Total Admins</div>
                    <div class="stat-value">${stats.total_admins || 0}</div>
                </div>
            </div>
            
            <div class="table-container">
                <h3 style="margin-bottom: 20px;">Recent Activity</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Type</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${stats.recent_activity && stats.recent_activity.length > 0 ?
                stats.recent_activity.map(activity => `
                                <tr>
                                    <td>${activity.description || 'N/A'}</td>
                                    <td><span class="status-badge ${activity.type}">${activity.type ? activity.type.replace('_', ' ') : 'activity'}</span></td>
                                    <td>${activity.created_at ? new Date(activity.created_at).toLocaleString() : 'N/A'}</td>
                                </tr>
                            `).join('') :
                '<tr><td colspan="3" style="text-align: center;">No recent activity</td></tr>'
            }
                    </tbody>
                </table>
            </div>
        `;

    } catch (error) {
        console.error('Dashboard error:', error);
        contentArea.innerHTML = `
            <div class="error-message" style="color: #dc3545; text-align: center; padding: 50px;">
                <i class="fas fa-exclamation-circle"></i> Failed to load dashboard. 
                <br><small>${error.message}</small>
                <br><br>
                <button class="primary-btn" onclick="window.refreshCurrentPage()">Try Again</button>
            </div>
        `;
    }
}

// Make functions available
window.loadDashboard = loadDashboard;