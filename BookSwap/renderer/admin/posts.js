// Posts Management Module
let allPosts = []; // Store all posts for searching and filtering
let currentFilter = 'all'; // 'all', 'available', 'unavailable'
let currentSearchTerm = '';

async function loadPosts() {
    const contentArea = document.getElementById('contentArea');
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');

    try {
        const token = localStorage.getItem('token');
        const response = await fetch('http://localhost/book_swap_api/admin/api/posts.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch posts');
        }

        allPosts = await response.json();
        console.log('Posts loaded:', allPosts);
        
        // Show search in header
        showHeaderSearch();
        
        // Render the posts with filter
        renderPostsWithFilter();

    } catch (error) {
        console.error('Posts error:', error);
        contentArea.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Failed to load posts. Please try again.
                <br><small>${error.message}</small>
            </div>
        `;
    }
}

// Show search input in header
function showHeaderSearch() {
    const headerSearch = document.getElementById('headerSearch');
    const globalSearchInput = document.getElementById('globalSearchInput');
    
    if (headerSearch) {
        headerSearch.style.display = 'block';
        
        // Update placeholder for posts search
        const searchInput = document.querySelector('.header-search .search-input');
        if (searchInput) {
            searchInput.placeholder = 'Search by title, author or poster...';
        }
        
        // Remove any existing event listener and add new one
        globalSearchInput.removeEventListener('input', handlePostSearch);
        globalSearchInput.addEventListener('input', handlePostSearch);
    }
}

// Handle post search
function handlePostSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    currentSearchTerm = searchTerm;
    renderPostsWithFilter();
}

// Clear search
function clearPostSearch() {
    currentSearchTerm = '';
    const globalSearchInput = document.getElementById('globalSearchInput');
    if (globalSearchInput) {
        globalSearchInput.value = '';
    }
    renderPostsWithFilter();
}

// Set filter and render
function setPostFilter(filter) {
    currentFilter = filter;
    
    // Update active button styles
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active-filter');
    });
    document.getElementById(`filter-${filter}`).classList.add('active-filter');
    
    renderPostsWithFilter();
}

// Render posts with current filter and search
function renderPostsWithFilter() {
    // First filter by availability (is_donated: 0 = available, 1 = unavailable)
    let filteredPosts = allPosts.filter(post => {
        if (currentFilter === 'all') return true;
        if (currentFilter === 'available') return post.is_donated == 0;
        if (currentFilter === 'unavailable') return post.is_donated == 1;
        return true;
    });
    
    // Then filter by search term
    if (currentSearchTerm) {
        filteredPosts = filteredPosts.filter(post => {
            const title = (post.title || '').toLowerCase();
            const author = (post.author || '').toLowerCase(); // book author/writer
            const posterName = (post.author_name || '').toLowerCase(); // user who posted
            
            return title.includes(currentSearchTerm) ||
                   author.includes(currentSearchTerm) ||
                   posterName.includes(currentSearchTerm);
        });
    }
    
    renderPostsTable(filteredPosts);
}

// Render posts table
function renderPostsTable(posts) {
    const contentArea = document.getElementById('contentArea');
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    
    // Check permissions for post actions
    const canManagePosts = window.permissions.hasPermission('manage_posts');
    
    contentArea.innerHTML = `
        <div style="margin-bottom: 20px;">
            <h3>Posts Management</h3>
        </div>
        
        <!-- Filter Buttons -->
        <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
            <span style="color: #667781; font-size: 14px;">Filter by:</span>
            <button class="filter-btn ${currentFilter === 'all' ? 'active-filter' : ''}" 
                    id="filter-all" 
                    onclick="window.setPostFilter('all')"
                    style="padding: 8px 16px; border: 1px solid #e9edef; background: ${currentFilter === 'all' ? '#00a884' : 'white'}; color: ${currentFilter === 'all' ? 'white' : '#111b21'}; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.2s;">
                All Posts
            </button>
            <button class="filter-btn ${currentFilter === 'available' ? 'active-filter' : ''}" 
                    id="filter-available" 
                    onclick="window.setPostFilter('available')"
                    style="padding: 8px 16px; border: 1px solid #e9edef; background: ${currentFilter === 'available' ? '#00a884' : 'white'}; color: ${currentFilter === 'available' ? 'white' : '#111b21'}; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.2s;">
                Available
            </button>
            <button class="filter-btn ${currentFilter === 'unavailable' ? 'active-filter' : ''}" 
                    id="filter-unavailable" 
                    onclick="window.setPostFilter('unavailable')"
                    style="padding: 8px 16px; border: 1px solid #e9edef; background: ${currentFilter === 'unavailable' ? '#00a884' : 'white'}; color: ${currentFilter === 'unavailable' ? 'white' : '#111b21'}; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.2s;">
                Unavailable
            </button>
        </div>
        
        ${currentSearchTerm ? `
            <div class="search-results-info">
                <i class="fas fa-search"></i> Found ${posts.length} post${posts.length !== 1 ? 's' : ''} matching "${currentSearchTerm}"
                <span class="clear-search" onclick="window.clearPostSearch()">Clear search</span>
            </div>
        ` : ''}
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author/Writer</th>
                        <th>Posted by</th>
                        <th>Type</th>
                        <th>Availability</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${posts && posts.length > 0 ?
                        posts.map(post => `
                            <tr>
                                <td>#${post.id}</td>
                                <td>
                                    <strong>${post.title || 'Untitled'}</strong>
                                    ${post.book_name ? `<br><small style="color: #667781;">${post.book_name}</small>` : ''}
                                </td>
                                <td>${post.author || 'N/A'}</td>
                                <td>${post.author_name || 'Unknown'}</td>
                                <td>
                                    <span class="status-badge" style="background: ${post.type === 'SELL' ? '#e3f2fd' : '#f3e5f5'}; 
                                        color: ${post.type === 'SELL' ? '#1565c0' : '#7b1fa2'};">
                                        ${post.type || 'GIVE'}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge ${post.is_donated == 0 ? 'active' : 'suspended'}">
                                        ${post.is_donated == 0 ? 'Available' : 'Unavailable'}
                                    </span>
                                </td>
                                <td>${new Date(post.created_at).toLocaleDateString()}</td>
                                <td>
                                    <button class="action-btn" onclick="window.viewPost(${post.id})" style="width: 30px; height: 30px;" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    ${canManagePosts ? `
                                        <button class="action-btn" onclick="window.toggleAvailability(${post.id}, ${post.is_donated})" style="width: 30px; height: 30px; color: ${post.is_donated == 0 ? '#dc3545' : '#00a884'};" title="${post.is_donated == 0 ? 'Mark as Unavailable' : 'Mark as Available'}">
                                            <i class="fas ${post.is_donated == 0 ? 'fa-times-circle' : 'fa-check-circle'}"></i>
                                        </button>
                                        <button class="action-btn" onclick="window.deletePost(${post.id}, '${post.title.replace(/'/g, "\\'")}')" style="width: 30px; height: 30px; color: #dc3545;" title="Delete Post">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : ''}
                                </td>
                            </tr>
                        `).join('') :
                        '<tr><td colspan="8" style="text-align: center; padding: 50px;">No posts found</td></tr>'
                    }
                </tbody>
            </table>
        </div>
    `;
}

// View post details
async function viewPost(postId) {
    try {
        const token = localStorage.getItem('token');
        
        // Show loading modal
        window.showModal('View Post', '<div style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top: 15px;">Loading post details...</p></div>', null, false);
        
        // Find post in allPosts
        const post = allPosts.find(p => p.id == postId);
        
        if (post) {
            window.closeModal();
            
            window.showModal('Post Details', `
                <div style="max-height: 70vh; overflow-y: auto; padding-right: 10px;">
                    ${post.image ? `
                        <div style="text-align: center; margin-bottom: 20px;">
                            <img src="http://localhost/book_swap_api/post_images/${post.image}" 
                                 style="max-width: 100%; max-height: 200px; border-radius: 8px; object-fit: cover;"
                                 onerror="this.style.display='none'">
                        </div>
                    ` : ''}
                    
                    <div style="margin-bottom: 15px;">
                        <h4 style="color: #111b21; margin-bottom: 5px;">${post.title || 'Untitled'}</h4>
                        ${post.book_name ? `<p style="color: #667781; font-size: 14px;">Book: ${post.book_name}</p>` : ''}
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                        <div>
                            <div style="font-size: 12px; color: #667781;">Author/Writer</div>
                            <div style="font-weight: 500;">${post.author || 'N/A'}</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #667781;">Posted by</div>
                            <div style="font-weight: 500;">${post.author_name || 'Unknown'}</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #667781;">Type</div>
                            <div><span class="status-badge" style="background: ${post.type === 'SELL' ? '#e3f2fd' : '#f3e5f5'}; color: ${post.type === 'SELL' ? '#1565c0' : '#7b1fa2'};">${post.type || 'GIVE'}</span></div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #667781;">Availability</div>
                            <div><span class="status-badge ${post.is_donated == 0 ? 'active' : 'suspended'}">${post.is_donated == 0 ? 'Available' : 'Unavailable'}</span></div>
                        </div>
                        ${post.price ? `
                        <div>
                            <div style="font-size: 12px; color: #667781;">Price</div>
                            <div style="font-weight: 500;">৳${post.price}</div>
                        </div>
                        ` : ''}
                        <div>
                            <div style="font-size: 12px; color: #667781;">Created</div>
                            <div style="font-size: 13px;">${new Date(post.created_at).toLocaleString()}</div>
                        </div>
                    </div>
                    
                    ${post.description ? `
                        <div style="margin-top: 15px;">
                            <div style="font-size: 12px; color: #667781; margin-bottom: 5px;">Description</div>
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; font-size: 14px; color: #111b21;">${post.description}</div>
                        </div>
                    ` : ''}
                    
                    ${post.location ? `
                        <div style="margin-top: 10px;">
                            <div style="font-size: 12px; color: #667781; margin-bottom: 5px;">Location</div>
                            <div style="font-size: 14px;">${post.location}</div>
                        </div>
                    ` : ''}
                    
                    ${post.contact_number ? `
                        <div style="margin-top: 10px;">
                            <div style="font-size: 12px; color: #667781; margin-bottom: 5px;">Contact</div>
                            <div style="font-size: 14px;">${post.contact_number}</div>
                        </div>
                    ` : ''}
                </div>
            `);
        } else {
            window.closeModal();
            alert('Post not found');
        }
        
    } catch (error) {
        console.error('Error viewing post:', error);
        window.closeModal();
        alert('Error loading post details');
    }
}

// Toggle post availability
async function toggleAvailability(postId, currentDonatedStatus) {
    // currentDonatedStatus: 0 = available, 1 = unavailable
    const newStatus = currentDonatedStatus == 0 ? 1 : 0; // Toggle between 0 and 1
    const statusText = newStatus == 0 ? 'available' : 'unavailable';
    
    if (confirm(`Are you sure you want to mark this post as ${statusText}?`)) {
        try {
            const token = localStorage.getItem('token');
            
            // Show loading on button
            const btn = event?.target?.closest('button');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
            }
            
            const response = await fetch('http://localhost/book_swap_api/admin/api/update-post-availability.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    post_id: postId,
                    is_available: newStatus == 0 ? 1 : 0 // Send 1 for available, 0 for unavailable
                })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                alert(`Post marked as ${statusText} successfully!`);
                
                // Update local data
                const postIndex = allPosts.findIndex(p => p.id == postId);
                if (postIndex !== -1) {
                    allPosts[postIndex].is_donated = newStatus;
                }
                
                // Refresh display
                renderPostsWithFilter();
            } else {
                alert(result.message || 'Failed to update availability');
                if (btn) {
                    btn.innerHTML = `<i class="fas ${currentDonatedStatus == 0 ? 'fa-times-circle' : 'fa-check-circle'}"></i>`;
                    btn.disabled = false;
                }
            }
            
        } catch (error) {
            console.error('Error updating availability:', error);
            alert('Error updating availability. Please check your connection.');
            
            const btn = event?.target?.closest('button');
            if (btn) {
                btn.innerHTML = `<i class="fas ${currentDonatedStatus == 0 ? 'fa-times-circle' : 'fa-check-circle'}"></i>`;
                btn.disabled = false;
            }
        }
    }
}

// Delete post function
function deletePost(postId, postTitle) {
    if (confirm(`Are you sure you want to delete "${postTitle}"? This action cannot be undone.`)) {
        console.log('Deleting post:', postId);
        
        const token = localStorage.getItem('token');
        
        // Show loading state
        const deleteBtn = event?.target?.closest('button');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteBtn.disabled = true;
        }
        
        fetch('http://localhost/book_swap_api/admin/api/posts.php', {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: postId })
        })
        .then(async response => {
            const result = await response.json();
            
            if (response.ok) {
                alert('Post deleted successfully');
                
                // Remove from local array
                allPosts = allPosts.filter(p => p.id != postId);
                
                // Refresh display
                renderPostsWithFilter();
            } else {
                alert(result.message || 'Failed to delete post');
                if (deleteBtn) {
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteBtn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error deleting post:', error);
            alert('Error deleting post. Please check your connection.');
            if (deleteBtn) {
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                deleteBtn.disabled = false;
            }
        });
    }
}

// Make functions available globally
window.loadPosts = loadPosts;
window.handlePostSearch = handlePostSearch;
window.clearPostSearch = clearPostSearch;
window.setPostFilter = setPostFilter;
window.viewPost = viewPost;
window.toggleAvailability = toggleAvailability;
window.deletePost = deletePost;