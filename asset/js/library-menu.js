/**
 * Library Theme Menu Functionality
 * Handles the hamburger menu for top-level pages
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Fix vertical bar issue on navigation lists - SIMPLIFIED
        function removeVerticalBar() {
            try {
                console.log('Attempting to fix vertical bar...');

                // Apply direct inline styles to override any CSS
                $('ul.navigation').each(function() {
                    this.style.setProperty('border-left', 'none', 'important');
                    this.style.setProperty('border', 'none', 'important');
                });

                // Add a high-priority style element
                if (!document.getElementById('vertical-bar-fix')) {
                    var style = document.createElement('style');
                    style.id = 'vertical-bar-fix';
                    style.textContent = 'ul.navigation { border-left: none !important; border: none !important; }';
                    document.head.appendChild(style);
                }

                console.log('Vertical bar fix applied to', $('ul.navigation').length, 'navigation elements');
            } catch (error) {
                console.error('Error in removeVerticalBar:', error);
            }
        }

        // Apply vertical bar fix immediately and with delays
        removeVerticalBar();
        setTimeout(removeVerticalBar, 100);
        setTimeout(removeVerticalBar, 500);

        // Initialize hamburger menu
        initHamburgerMenu();
        
        // Close menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.top-level-menu-toggle').length) {
                closeHamburgerMenu();
            }
        });
        
        // Close menu when pressing Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHamburgerMenu();
            }
        });
    });

    /**
     * Initialize the hamburger menu functionality
     */
    window.initHamburgerMenu = function initHamburgerMenu() {
        $('.hamburger-menu-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $dropdown = $(this).siblings('.top-level-menu-dropdown');
            
            if ($dropdown.hasClass('show')) {
                closeHamburgerMenu();
            } else {
                openHamburgerMenu();
            }
        });
    };

    /**
     * Open the hamburger menu
     */
    function openHamburgerMenu() {
        $('.top-level-menu-dropdown').addClass('show');
        $('.hamburger-menu-btn').attr('aria-expanded', 'true');
        
        // Focus first menu item for accessibility
        setTimeout(function() {
            $('.top-level-page-link:first').focus();
        }, 100);
    }

    /**
     * Close the hamburger menu
     */
    function closeHamburgerMenu() {
        $('.top-level-menu-dropdown').removeClass('show');
        $('.hamburger-menu-btn').attr('aria-expanded', 'false');
    }

    /**
     * Handle keyboard navigation in menu
     */
    $(document).on('keydown', '.top-level-page-link', function(e) {
        var $links = $('.top-level-page-link');
        var currentIndex = $links.index(this);
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                var nextIndex = (currentIndex + 1) % $links.length;
                $links.eq(nextIndex).focus();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                var prevIndex = currentIndex === 0 ? $links.length - 1 : currentIndex - 1;
                $links.eq(prevIndex).focus();
                break;
                
            case 'Home':
                e.preventDefault();
                $links.first().focus();
                break;
                
            case 'End':
                e.preventDefault();
                $links.last().focus();
                break;
        }
    });

})(jQuery);

// LIBRARY THEME - PRODUCTION VERSION
jQuery(document).ready(function($) {
    console.log('Library Theme loading...');

    // Make logo clickable
    function setupLogo() {
        const logo = document.querySelector('header img[alt*="Library"]');
        if (logo) {
            logo.onclick = function() {
                window.location.href = '/omeka-s/s/library';
            };
            console.log('Logo click enabled');
        }
    }

    // Force correct styling for all links
    function forceCorrectStyling() {
        const allLinks = document.querySelectorAll('main a');
        let contentFixed = 0;
        let paginationFixed = 0;

        // Compute once so both branches can use it safely
        const rootStyles = getComputedStyle(document.documentElement);

        allLinks.forEach((link) => {
            const text = link.textContent.trim();

            if (text === 'Next' || text === 'Prev') {
                // Get admin settings from CSS variables
                const paginationBg = rootStyles.getPropertyValue('--pagination-background-color').trim() || '#2c5aa0';
                const paginationColor = rootStyles.getPropertyValue('--pagination-font-color').trim() || '#ffffff';
                const paginationPadding = rootStyles.getPropertyValue('--pagination-button-padding').trim() || '12px 24px';
                const paginationFontSize = rootStyles.getPropertyValue('--pagination-button-font-size').trim() || '16px';

                // Apply pagination button styling using admin settings
                link.style.setProperty('background-color', paginationBg, 'important');
                link.style.setProperty('background', paginationBg, 'important');
                link.style.setProperty('color', paginationColor, 'important');
                link.style.setProperty('padding', paginationPadding, 'important');
                link.style.setProperty('font-size', paginationFontSize, 'important');
                link.style.setProperty('border-radius', '4px', 'important');
                link.style.setProperty('text-decoration', 'none', 'important');
                link.style.setProperty('border', 'none', 'important');
                link.style.setProperty('display', 'inline-block', 'important');
                link.style.setProperty('margin', '0 8px', 'important');
                link.style.setProperty('box-shadow', 'none', 'important');

                // Add hover effect using admin settings
                const paginationHover = rootStyles.getPropertyValue('--pagination-hover-color').trim() || '#1a365d';
                link.addEventListener('mouseenter', function() {
                    this.style.setProperty('background-color', paginationHover, 'important');
                    this.style.setProperty('background', paginationHover, 'important');
                });
                link.addEventListener('mouseleave', function() {
                    this.style.setProperty('background-color', paginationBg, 'important');
                    this.style.setProperty('background', paginationBg, 'important');
                });

                paginationFixed++;
            } else {
                // Base content link styling; leave colors/hover effects to CSS
                link.style.setProperty('text-decoration', 'none', 'important');
                link.style.setProperty('display', 'block', 'important');
                link.style.setProperty('padding', '8px 12px', 'important');
                link.style.setProperty('margin', '4px 0', 'important');
                link.style.setProperty('min-height', '20px', 'important');
                link.style.setProperty('line-height', '1.4', 'important');
                link.style.setProperty('font-size', '16px', 'important');
                link.style.setProperty('border', 'none', 'important');
                link.style.setProperty('box-shadow', 'none', 'important');

                contentFixed++;
            }
        });

        console.log(`Styled ${contentFixed} content links and ${paginationFixed} pagination buttons`);
    }

    // Apply all fixes immediately and after a delay
    setupLogo();
    forceCorrectStyling();

    // Reapply styling after page loads completely
    setTimeout(forceCorrectStyling, 500);
    setTimeout(forceCorrectStyling, 1000);

    console.log('Library Theme ready');
});

