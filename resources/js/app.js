// Business Management System JavaScript

// Initialize Alpine.js components
document.addEventListener('alpine:init', () => {
    // Toast notification component
    Alpine.data('toast', () => ({
        show: false,
        message: '',
        type: 'success',
        
        showToast(message, type = 'success') {
            this.message = message;
            this.type = type;
            this.show = true;
            
            setTimeout(() => {
                this.show = false;
            }, 5000);
        }
    }));

    // Modal component
    Alpine.data('modal', () => ({
        show: false,
        
        open() {
            this.show = true;
            document.body.style.overflow = 'hidden';
        },
        
        close() {
            this.show = false;
            document.body.style.overflow = 'auto';
        }
    }));

    // Dropdown component
    Alpine.data('dropdown', () => ({
        open: false,
        
        toggle() {
            this.open = !this.open;
        },
        
        close() {
            this.open = false;
        }
    }));

    // Form validation
    Alpine.data('form', () => ({
        errors: {},
        
        setError(field, message) {
            this.errors[field] = message;
        },
        
        clearError(field) {
            delete this.errors[field];
        },
        
        hasError(field) {
            return this.errors.hasOwnProperty(field);
        }
    }));
});

// Utility functions
window.BMS = {
    // Format currency
    formatCurrency: (amount, currency = 'NGN') => {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: currency,
        }).format(amount);
    },

    // Format date
    formatDate: (date, options = {}) => {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        return new Intl.DateTimeFormat('en-NG', { ...defaultOptions, ...options }).format(new Date(date));
    },

    // Format number
    formatNumber: (number) => {
        return new Intl.NumberFormat('en-NG').format(number);
    },

    // Show loading state
    showLoading: (element) => {
        element.innerHTML = '<div class="spinner"></div>';
        element.disabled = true;
    },

    // Hide loading state
    hideLoading: (element, originalText) => {
        element.innerHTML = originalText;
        element.disabled = false;
    },

    // Show toast notification
    showToast: (message, type = 'success') => {
        const toast = document.getElementById('toast-container');
        if (toast) {
            const toastElement = document.createElement('div');
            toastElement.className = `toast toast-${type}`;
            toastElement.innerHTML = `
                <div class="p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            ${type === 'success' ? 
                                '<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>' :
                                '<svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>'
                            }
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">${message}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <div class="-mx-1.5 -my-1.5">
                                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex bg-white rounded-md p-1.5 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-50 focus:ring-indigo-600">
                                    <span class="sr-only">Dismiss</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            toast.appendChild(toastElement);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toastElement.parentNode) {
                    toastElement.remove();
                }
            }, 5000);
        }
    },

    // Confirm dialog
    confirm: (message, callback) => {
        if (confirm(message)) {
            callback();
        }
    },

    // AJAX helper
    ajax: (url, options = {}) => {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        };
        
        return fetch(url, { ...defaultOptions, ...options })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.bg-green-100, .bg-red-100, .bg-yellow-100');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    });

    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            // Tooltip implementation
        });
    });

    // Initialize form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Form validation logic
        });
    });
});
