import './bootstrap';

// SECURITY FIX: Setup CSRF token for all AJAX requests
document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token from meta tag
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (token) {
        // Setup Axios defaults
        if (window.axios) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
        
        // Setup jQuery defaults if jQuery is available
        if (window.$ && window.$.ajaxSetup) {
            window.$.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token
                }
            });
        }
        
        // Setup fetch defaults
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            options.headers = {
                ...options.headers,
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
            return originalFetch(url, options);
        };
    }
});

// Alpine.js components for AJAX functionality
document.addEventListener('alpine:init', () => {
    Alpine.data('ajaxHandler', () => ({
        async submitForm(formData, url, method = 'POST') {
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('AJAX Error:', error);
                throw error;
            }
        },
        
        async deleteResource(url) {
            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('Delete Error:', error);
                throw error;
            }
        },
        
        async patchResource(url, data = {}) {
            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('PATCH Error:', error);
                throw error;
            }
        }
    }));
});