/**
 * Social Share Popup Handler
 * 
 * Handles social media sharing popup windows without inline JavaScript
 * for Content Security Policy compliance and better security.
 */

(function() {
    'use strict';
    
    /**
     * Initialize social share popup handlers
     */
    function initSocialSharePopups() {
        // Find all social share links with popup data
        const shareLinks = document.querySelectorAll('.social-share-popup[data-popup]');
        
        shareLinks.forEach(function(link) {
            link.addEventListener('click', handleSocialShareClick);
        });
    }
    
    /**
     * Handle click on social share links
     * 
     * @param {Event} event Click event
     */
    function handleSocialShareClick(event) {
        event.preventDefault();
        
        const link = event.currentTarget;
        const href = link.getAttribute('href');
        const popupOptions = link.getAttribute('data-popup');
        
        if (!href || !popupOptions) {
            // Fallback: open in new tab if data is missing
            window.open(href, '_blank');
            return;
        }
        
        try {
            // Open popup window with specified options
            const popup = window.open(href, '', popupOptions);
            
            // Focus the popup window if it was successfully opened
            if (popup) {
                popup.focus();
            } else {
                // Popup was blocked, fallback to new tab
                window.open(href, '_blank');
            }
        } catch (error) {
            // Error opening popup, fallback to new tab
            console.warn('Failed to open social share popup:', error);
            window.open(href, '_blank');
        }
    }
    
    /**
     * Initialize when DOM is ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSocialSharePopups);
        } else {
            initSocialSharePopups();
        }
    }
    
    // Initialize the module
    init();
    
    // Expose for manual initialization if needed
    window.SocialSharePopups = {
        init: initSocialSharePopups,
        handleClick: handleSocialShareClick
    };
    
})();
