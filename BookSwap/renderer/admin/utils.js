// Shared utility functions

// Debounce helper
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Modal functions
function showModal(title, content, onSave, saveButtonText = 'Save') {
    const modalContainer = document.getElementById('modalContainer');

    modalContainer.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3>${title}</h3>
                <button class="close-modal" onclick="window.closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
            ${onSave ? `
                <div class="modal-footer">
                    <button class="secondary-btn" onclick="window.closeModal()">Cancel</button>
                    <button class="primary-btn" onclick="window.saveModal()">${saveButtonText}</button>
                </div>
            ` : ''}
        </div>
    `;

    modalContainer.classList.add('show');

    // Store save callback
    if (onSave) {
        window.saveModal = () => {
            onSave();
        };
    }
}

function closeModal() {
    const modalContainer = document.getElementById('modalContainer');
    modalContainer.classList.remove('show');
    delete window.saveModal;
}

// Fetch helper with auth
async function fetchWithAuth(url, options = {}) {
    const token = localStorage.getItem('token');
    return fetch(url, {
        ...options,
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            ...options.headers
        }
    });
}

// Make functions globally available
window.debounce = debounce;
window.showModal = showModal;
window.closeModal = closeModal;
window.fetchWithAuth = fetchWithAuth;