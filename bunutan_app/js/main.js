/**
 * Bunutan - Christmas Gift Exchange
 * Main JavaScript functionality
 * Global utility functions for use across admin.php and index.php
 */

// Toast Notification System
window.toast = function(message, type = 'info', duration = 5000) {
    const container = getOrCreateToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <div class="toast-content">
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    container.appendChild(toast);
    
    // Auto remove after duration
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 300);
    }, duration);
    
    return toast;
};

function getOrCreateToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
}

// Legacy showMessage for backward compatibility
window.showMessage = function(elementId, message, type) {
    const element = document.getElementById(elementId);
    if (!element) {
        // Fallback to toast if element doesn't exist
        toast(message, type === 'success' ? 'success' : 'error');
        return;
    }
    
    element.textContent = message;
    element.className = `message ${type}`;
    element.style.display = 'block';
    
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
};

// Utility function to make API calls
window.apiCall = async function(action, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(`api.php?action=${action}`, options);
        return await response.json();
    } catch (error) {
        console.error('API call error:', error);
        throw error;
    }
};

// Smooth scroll utility
window.smoothScroll = function(target) {
    const element = document.querySelector(target);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
};

// Copy to clipboard utility with visual feedback
window.copyToClipboard = function(text, buttonElement = null) {
    const promise = navigator.clipboard && navigator.clipboard.writeText
        ? navigator.clipboard.writeText(text)
        : new Promise((resolve, reject) => {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                document.body.removeChild(textArea);
                resolve();
            } catch (err) {
                document.body.removeChild(textArea);
                reject(err);
            }
        });
    
    return promise.then(() => {
        if (buttonElement) {
            const originalText = buttonElement.textContent;
            buttonElement.textContent = '✓ Copied!';
            buttonElement.style.background = 'var(--success-color)';
            setTimeout(() => {
                buttonElement.textContent = originalText;
                buttonElement.style.background = '';
            }, 2000);
        } else {
            toast('Copied to clipboard!', 'success', 2000);
        }
    }).catch(() => {
        toast('Failed to copy to clipboard', 'error');
    });
};

// Format date utility
window.formatDate = function(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Validate input
window.validateInput = function(value, minLength = 1) {
    return value && value.trim().length >= minLength;
};

// Add animation class
window.addAnimation = function(element, animationClass) {
    element.classList.add(animationClass);
    element.addEventListener('animationend', () => {
        element.classList.remove(animationClass);
    }, { once: true });
};

// Confetti Animation
window.createConfetti = function() {
    const container = document.createElement('div');
    container.className = 'confetti-container';
    document.body.appendChild(container);
    
    const colors = ['#2d5f3f', '#c41e3a', '#28a745', '#ffc107', '#17a2b8', '#6f42c1'];
    const confettiCount = 50;
    
    for (let i = 0; i < confettiCount; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
        confetti.style.animationDelay = Math.random() * 0.5 + 's';
        container.appendChild(confetti);
    }
    
    setTimeout(() => {
        container.remove();
    }, 5000);
};

// Dark Mode Toggle
window.toggleDarkMode = function() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Update button icon
    const toggleBtn = document.querySelector('.theme-toggle');
    if (toggleBtn) {
        toggleBtn.textContent = newTheme === 'dark' ? '☀️' : '🌙';
    }
    
    return newTheme;
};

// Initialize theme from localStorage
window.initTheme = function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Update button icon
    const toggleBtn = document.querySelector('.theme-toggle');
    if (toggleBtn) {
        toggleBtn.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
    }
};

// Form Validation
window.validateForm = function(input, minLength = 1) {
    const value = input.value.trim();
    const isValid = value.length >= minLength;
    
    input.classList.remove('input-error', 'input-success');
    const errorMsg = input.parentElement.querySelector('.error-message');
    
    if (errorMsg) {
        errorMsg.classList.remove('show');
    }
    
    if (value.length > 0) {
        if (isValid) {
            input.classList.add('input-success');
        } else {
            input.classList.add('input-error');
            if (errorMsg) {
                errorMsg.textContent = `Minimum ${minLength} character(s) required`;
                errorMsg.classList.add('show');
            }
        }
    }
    
    return isValid;
};

// Initialize tooltips or other UI enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme
    initTheme();
    
    // Add focus styles to inputs
    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
        
        // Real-time validation
        if (input.hasAttribute('required')) {
            input.addEventListener('input', function() {
                validateForm(this);
            });
        }
    });
    
    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});
