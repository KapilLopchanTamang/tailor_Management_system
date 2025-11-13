/**
 * Global Search JavaScript
 * Tailoring Management System
 */

(function() {
    'use strict';
    
    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '/TMS/';
    let searchTimeout = null;
    
    // Initialize search on page load
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('globalSearchInput');
        const searchDropdown = document.getElementById('globalSearchDropdown');
        
        if (!searchInput || !searchDropdown) {
            return;
        }
        
        // Handle input
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Hide dropdown if query is too short
            if (query.length < 2) {
                searchDropdown.innerHTML = '';
                searchDropdown.style.display = 'none';
                return;
            }
            
            // Debounce search
            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, 300);
        });
        
        // Handle focus
        searchInput.addEventListener('focus', function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                performSearch(query);
            }
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const isClickInside = searchInput.contains(e.target) || 
                                  searchDropdown.contains(e.target) ||
                                  (e.target.closest && e.target.closest('.input-group')?.contains(searchInput));
            if (!isClickInside) {
                searchDropdown.style.display = 'none';
            }
        });
        
        // Prevent form submission on Enter
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const query = searchInput.value.trim();
                if (query.length >= 2) {
                    performSearch(query);
                }
            });
        }
        
        // Handle escape key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchDropdown.style.display = 'none';
                this.blur();
            }
        });
    });
    
    /**
     * Perform search
     */
    function performSearch(query) {
        const searchDropdown = document.getElementById('globalSearchDropdown');
        if (!searchDropdown) {
            return;
        }
        
        // Show loading state
        searchDropdown.innerHTML = '<div class="dropdown-item text-muted"><i class="bi bi-hourglass-split"></i> Searching...</div>';
        searchDropdown.style.display = 'block';
        
        // Fetch results
        fetch(baseUrl + 'api/search.php?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.results, query);
                } else {
                    searchDropdown.innerHTML = '<div class="dropdown-item text-danger"><i class="bi bi-exclamation-triangle"></i> ' + (data.message || 'Search failed') + '</div>';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                searchDropdown.innerHTML = '<div class="dropdown-item text-danger"><i class="bi bi-exclamation-triangle"></i> Error performing search</div>';
            });
    }
    
    /**
     * Display search results
     */
    function displayResults(results, query) {
        const searchDropdown = document.getElementById('globalSearchDropdown');
        if (!searchDropdown) {
            return;
        }
        
        let html = '';
        let hasResults = false;
        
        // Orders
        if (results.orders && results.orders.length > 0) {
            html += '<h6 class="dropdown-header"><i class="bi bi-bag"></i> Orders</h6>';
            results.orders.forEach(item => {
                html += `
                    <a class="dropdown-item" href="${item.url}">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${escapeHtml(item.title)}</strong><br>
                                <small class="text-muted">${escapeHtml(item.description)}</small>
                            </div>
                            <span class="badge bg-${getStatusColor(item.status)} ms-2">${item.status}</span>
                        </div>
                    </a>
                `;
            });
            hasResults = true;
        }
        
        // Customers (Admin only)
        if (results.customers && results.customers.length > 0) {
            html += '<h6 class="dropdown-header"><i class="bi bi-people"></i> Customers</h6>';
            results.customers.forEach(item => {
                html += `
                    <a class="dropdown-item" href="${item.url}">
                        <div>
                            <strong>${escapeHtml(item.title)}</strong><br>
                            <small class="text-muted">${escapeHtml(item.description)}</small>
                        </div>
                    </a>
                `;
            });
            hasResults = true;
        }
        
        // Inventory
        if (results.inventory && results.inventory.length > 0) {
            html += '<h6 class="dropdown-header"><i class="bi bi-box-seam"></i> Inventory</h6>';
            results.inventory.forEach(item => {
                html += `
                    <a class="dropdown-item" href="${item.url}">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${escapeHtml(item.title)}</strong><br>
                                <small class="text-muted">${escapeHtml(item.description)}</small>
                            </div>
                            <span class="badge bg-${item.status === 'available' ? 'success' : 'secondary'} ms-2">${item.status}</span>
                        </div>
                    </a>
                `;
            });
            hasResults = true;
        }
        
        if (!hasResults) {
            html = '<div class="dropdown-item text-muted"><i class="bi bi-search"></i> No results found for "' + escapeHtml(query) + '"</div>';
        }
        
        searchDropdown.innerHTML = html;
        searchDropdown.style.display = 'block';
    }
    
    /**
     * Get status color for badge
     */
    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'in-progress': 'info',
            'completed': 'success',
            'delivered': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();

