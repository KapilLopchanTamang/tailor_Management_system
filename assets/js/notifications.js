/**
 * Notifications JavaScript
 * Tailoring Management System
 */

let notificationPollInterval = null;

// Get base URL - try to get from a meta tag or use default
let baseUrl = '/TMS/';
if (document.querySelector('meta[name="base-url"]')) {
    baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');
} else if (typeof window.baseUrl !== 'undefined') {
    baseUrl = window.baseUrl;
}
// Ensure baseUrl ends with /
if (!baseUrl.endsWith('/')) {
    baseUrl += '/';
}

// Initialize notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    updateNotificationCount();
    loadNotifications();
    
    // Start polling for notifications every 30 seconds
    notificationPollInterval = setInterval(function() {
        updateNotificationCount();
    }, 30000);
    
    // Setup notification modal
    const notificationsModal = document.getElementById('notificationsModal');
    if (notificationsModal) {
        notificationsModal.addEventListener('show.bs.modal', function() {
            loadNotifications();
        });
    }
});

/**
 * Update notification count
 */
function updateNotificationCount() {
    fetch(baseUrl + 'api/notifications.php?action=count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error fetching notification count:', error);
        });
}

/**
 * Load notifications list
 */
function loadNotifications() {
    fetch(baseUrl + 'api/notifications.php?action=list&limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
            }
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
}

/**
 * Display notifications in modal
 */
function displayNotifications(notifications) {
    const container = document.getElementById('notificationsList');
    if (!container) return;
    
    if (notifications.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">No notifications</div>';
        return;
    }
    
    let html = '<div class="list-group">';
    notifications.forEach(function(notification) {
        const readClass = notification.is_read ? '' : 'list-group-item-primary';
        const readIcon = notification.is_read ? '' : '<i class="bi bi-circle-fill text-primary"></i> ';
        
        html += `
            <div class="list-group-item ${readClass} notification-item" data-id="${notification.id}">
                <div class="d-flex w-100 justify-content-between">
                    <div class="mb-1">
                        ${readIcon}
                        <strong>${escapeHtml(notification.message)}</strong>
                    </div>
                    <small>${notification.time_ago}</small>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    ${!notification.is_read ? `<button class="btn btn-sm btn-outline-primary me-2" onclick="markAsRead(${notification.id})">Mark as Read</button>` : ''}
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification(${notification.id})">Delete</button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

/**
 * Mark notification as read
 */
function markAsRead(notificationId) {
    const formData = new FormData();
    formData.append('action', 'read');
    formData.append('notification_id', notificationId);
    
    fetch(baseUrl + 'api/notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationCount();
            loadNotifications();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        alert('An error occurred. Please try again.');
    });
}

/**
 * Delete notification
 */
function deleteNotification(notificationId) {
    if (!confirm('Are you sure you want to delete this notification?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('notification_id', notificationId);
    
    fetch(baseUrl + 'api/notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationCount();
            loadNotifications();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting notification:', error);
        alert('An error occurred. Please try again.');
    });
}

/**
 * Mark all notifications as read
 */
function markAllAsRead() {
    const formData = new FormData();
    formData.append('action', 'read_all');
    
    fetch(baseUrl + 'api/notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationCount();
            loadNotifications();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        alert('An error occurred. Please try again.');
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Cleanup interval on page unload
window.addEventListener('beforeunload', function() {
    if (notificationPollInterval) {
        clearInterval(notificationPollInterval);
    }
});
