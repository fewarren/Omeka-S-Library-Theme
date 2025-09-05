/**
 * Library Theme Menu Functionality
 * Handles the hamburger menu for top-level pages
 * Enhanced with memory management and performance optimizations
 */

(function($) {
    'use strict';

    // Performance and memory management utilities
    const PerformanceManager = {
        // Cache for computed styles to avoid repeated calculations
        styleCache: new Map(),
        
        // Debounce utility to prevent excessive function calls
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Get cached computed style or compute and cache
        getCachedStyle: function(element, property) {
            const key = `${element.tagName}-${property}`;
            if (this.styleCache.has(key)) {
                return this.styleCache.get(key);
            }
            
            const style = getComputedStyle(element);
            const value = style.getPropertyValue(property).trim();
            this.styleCache.set(key, value);
            return value;
        },
        
        // Clear style cache when needed
        clearStyleCache: function() {
            this.styleCache.clear();
        },
        
        // Track event listeners for cleanup
        eventListeners: new WeakMap(),
        
        // Add tracked event listener
        addTrackedListener: function(element, event, handler, options) {
            if (!this.eventListeners.has(element)) {
                this.eventListeners.set(element, []);
            }
            
            const listeners = this.eventListeners.get(element);
            listeners.push({ event, handler, options });
            element.addEventListener(event, handler, options);
        },
        
        // Remove all tracked listeners from element
        removeAllListeners: function(element) {
            const listeners = this.eventListeners.get(element);
            if (listeners) {
                listeners.forEach(({ event, handler, options }) => {
                    element.removeEventListener(event, handler, options);
                });
                this.eventListeners.delete(element);
            }
        }
    };

    // State management
    const State = {
        initialized: false,
        verticalBarFixed: false,
        logoSetup: false,
        stylingApplied: false
    };

    $(document).ready(function() {
        if (State.initialized) return;
        State.initialized = true;

        console.log('Library Theme loading...');
        
        // Initialize all components
        initializeComponents();
        
        // Setup cleanup on page unload
        $(window).on('beforeunload', cleanup);
        
        console.log('Library Theme ready');
    });

    function initializeComponents() {
        try {
            // Apply fixes with proper error handling
            removeVerticalBar();
            setupLogo();
            // forceCorrectStyling disabled: styling must come from theme CSS and settings
            initHamburgerMenu();
            setupEventListeners();
        } catch (error) {
            console.error('Error initializing components:', error);
        }
    }

    // Fix vertical bar issue on navigation lists - OPTIMIZED
    function removeVerticalBar() {
        if (State.verticalBarFixed) return;
        
        try {
            console.log('Attempting to fix vertical bar...');

            const navigationElements = document.querySelectorAll('ul.navigation');
            
            // Apply direct inline styles to override any CSS
            navigationElements.forEach(element => {
                element.style.setProperty('border-left', 'none', 'important');
                element.style.setProperty('border', 'none', 'important');
            });

            // Add a high-priority style element only once
            if (!document.getElementById('vertical-bar-fix')) {
                const style = document.createElement('style');
                style.id = 'vertical-bar-fix';
                style.textContent = 'ul.navigation { border-left: none !important; border: none !important; }';
                document.head.appendChild(style);
            }

            console.log('Vertical bar fix applied to', navigationElements.length, 'navigation elements');
            State.verticalBarFixed = true;
        } catch (error) {
            console.error('Error in removeVerticalBar:', error);
        }
    }

    // Make logo clickable - OPTIMIZED
    function setupLogo() {
        if (State.logoSetup) return;
        
        try {
            const logo = document.querySelector('header img[alt*="Library"]');
            if (!logo) return;
            
            // Remove any existing click handlers to prevent duplicates
            PerformanceManager.removeAllListeners(logo);
            
            const clickHandler = function(event) {
                event.preventDefault();
                
                // Build URL dynamically to work with different base paths
                let baseUrl = '';
                
                // Try to get base path from Omeka global if available
                if (window.Omeka && window.Omeka.basePath) {
                    baseUrl = window.Omeka.basePath;
                } else {
                    // Fallback: detect base path from current URL
                    const currentPath = window.location.pathname;
                    const omekaMatch = currentPath.match(/^(.*?\/omeka-s)/);
                    if (omekaMatch) {
                        baseUrl = omekaMatch[1];
                    }
                }
                
                // Construct the library site URL
                const libraryUrl = baseUrl + '/s/library';
                window.location.href = libraryUrl;
                
                console.log('Logo click enabled, redirecting to:', libraryUrl);
            };
            
            PerformanceManager.addTrackedListener(logo, 'click', clickHandler);
            console.log('Logo click enabled');
            State.logoSetup = true;
        } catch (error) {
            console.error('Error in setupLogo:', error);
        }
    }

    // Force correct styling for all links - OPTIMIZED
    function forceCorrectStyling() {
        if (State.stylingApplied) return;
        
        try {
            const allLinks = document.querySelectorAll('main a');
            if (allLinks.length === 0) return;
            
            let contentFixed = 0;
            let paginationFixed = 0;

            // Get root CSS variables fresh (avoid stale cache issues)
            const rootStyles = getComputedStyle(document.documentElement);
            const readVar = (name, fallback) => (rootStyles.getPropertyValue(name).trim() || fallback);
            const paginationBg = readVar('--pagination-background-color', '#2c5aa0');
            const paginationColor = readVar('--pagination-font-color', '#ffffff');
            const paginationPadding = readVar('--pagination-button-padding', '12px 24px');
            const paginationFontSize = readVar('--pagination-button-font-size', '16px');
            const paginationHover = readVar('--pagination-hover-color', '#1a365d');
            const paginationHoverText = readVar('--pagination-hover-text-color', '#ffffff');

            allLinks.forEach((link) => {
                const text = link.textContent.trim();

                if (text === 'Next' || text === 'Prev') {
                    // Apply pagination button styling using cached admin settings
                    const styles = {
                        'background-color': paginationBg,
                        'background': paginationBg,
                        'color': paginationColor,
                        'padding': paginationPadding,
                        'font-size': paginationFontSize,
                        'border-radius': '999px',
                        'text-decoration': 'none',
                        'border': 'none',
                        'display': 'inline-block',
                        'margin': '0 8px',
                        'box-shadow': 'none',
                        'white-space': 'nowrap'
                    };
                    
                    Object.entries(styles).forEach(([property, value]) => {
                        link.style.setProperty(property, value, 'important');
                    });

                    // Add hover effect only if not already attached
                    if (!link.dataset.hoverListenersAttached) {
                        // Remove any existing listeners first
                        PerformanceManager.removeAllListeners(link);
                        
                        const handleMouseEnter = function() {
                            this.style.setProperty('background-color', paginationHover, 'important');
                            this.style.setProperty('background', paginationHover, 'important');
                        };
                        
                        const handleMouseLeave = function() {
                            this.style.setProperty('background-color', paginationBg, 'important');
                            this.style.setProperty('background', paginationBg, 'important');
                        };
                        
                        PerformanceManager.addTrackedListener(link, 'mouseenter', handleMouseEnter);
                        PerformanceManager.addTrackedListener(link, 'mouseleave', handleMouseLeave);
                        
                        // Mark as having listeners attached
                        link.dataset.hoverListenersAttached = 'true';
                    }

                    paginationFixed++;
                } else {
                    // Base content link styling
                    const contentStyles = {
                        'text-decoration': 'none',
                        'display': 'block',
                        'padding': '8px 12px',
                        'margin': '4px 0',
                        'min-height': '20px',
                        'line-height': '1.4',
                        'font-size': '16px',
                        'border': 'none',
                        'box-shadow': 'none'
                    };
                    
                    Object.entries(contentStyles).forEach(([property, value]) => {
                        link.style.setProperty(property, value, 'important');
                    });

                    contentFixed++;
                }
            });

            console.log(`Styled ${contentFixed} content links and ${paginationFixed} pagination buttons`);
            State.stylingApplied = true;
        } catch (error) {
            console.error('Error in forceCorrectStyling:', error);
        }
    }

    // Setup event listeners with proper cleanup
    function setupEventListeners() {
        try {
            // Close menu when clicking outside
            const outsideClickHandler = function(e) {
                if (!$(e.target).closest('.top-level-menu-toggle').length) {
                    closeHamburgerMenu();
                }
            };
            
            // Close menu when pressing Escape key
            const escapeKeyHandler = function(e) {
                if (e.key === 'Escape') {
                    closeHamburgerMenu();
                }
            };
            
            // Keyboard navigation in menu
            const keyboardNavHandler = function(e) {
                const $links = $('.top-level-page-link');
                const currentIndex = $links.index(this);
                
                switch(e.key) {
                    case 'ArrowDown': {
                        e.preventDefault();
                        const nextIndex = (currentIndex + 1) % $links.length;
                        $links.eq(nextIndex).focus();
                        break;
                    }
                        
                    case 'ArrowUp': {
                        e.preventDefault();
                        const prevIndex = currentIndex === 0 ? $links.length - 1 : currentIndex - 1;
                        $links.eq(prevIndex).focus();
                        break;
                    }
                        
                    case 'Home': {
                        e.preventDefault();
                        $links.first().focus();
                        break;
                    }
                        
                    case 'End': {
                        e.preventDefault();
                        $links.last().focus();
                        break;
                    }
                }
            };
            
            // Add event listeners with jQuery (they handle cleanup automatically)
            $(document).off('click.libraryTheme').on('click.libraryTheme', outsideClickHandler);
            $(document).off('keydown.libraryTheme').on('keydown.libraryTheme', escapeKeyHandler);
            $(document).off('keydown.libraryTheme', '.top-level-page-link').on('keydown.libraryTheme', '.top-level-page-link', keyboardNavHandler);
            
        } catch (error) {
            console.error('Error setting up event listeners:', error);
        }
    }

    /**
     * Initialize the hamburger menu functionality - OPTIMIZED
     */
    window.initHamburgerMenu = function initHamburgerMenu() {
        try {
            // Remove existing handlers to prevent duplicates
            $('.hamburger-menu-btn').off('click.hamburger');
            
            $('.hamburger-menu-btn').on('click.hamburger', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $dropdown = $(this).siblings('.top-level-menu-dropdown');
                
                if ($dropdown.hasClass('show')) {
                    closeHamburgerMenu();
                } else {
                    openHamburgerMenu();
                }
            });
        } catch (error) {
            console.error('Error initializing hamburger menu:', error);
        }
    };

    /**
     * Open the hamburger menu
     */
    function openHamburgerMenu() {
        try {
            $('.top-level-menu-dropdown').addClass('show');
            $('.hamburger-menu-btn').attr('aria-expanded', 'true');
            
            // Focus first menu item for accessibility
            setTimeout(function() {
                $('.top-level-page-link:first').focus();
            }, 100);
        } catch (error) {
            console.error('Error opening hamburger menu:', error);
        }
    }

    /**
     * Close the hamburger menu
     */
    function closeHamburgerMenu() {
        try {
            $('.top-level-menu-dropdown').removeClass('show');
            $('.hamburger-menu-btn').attr('aria-expanded', 'false');
        } catch (error) {
            console.error('Error closing hamburger menu:', error);
        }
    }

    /**
     * Cleanup function to prevent memory leaks
     */
    function cleanup() {
        try {
            // Clear performance caches
            PerformanceManager.clearStyleCache();
            
            // Remove tracked event listeners
            document.querySelectorAll('*').forEach(element => {
                PerformanceManager.removeAllListeners(element);
            });
            
            // Remove jQuery event listeners
            $(document).off('.libraryTheme');
            $('.hamburger-menu-btn').off('.hamburger');
            
            // Reset state
            Object.keys(State).forEach(key => {
                State[key] = false;
            });
            
            console.log('Library Theme cleanup completed');
        } catch (error) {
            console.error('Error during cleanup:', error);
        }
    }

    // Expose cleanup function for manual cleanup if needed
    window.libraryThemeCleanup = cleanup;

})(jQuery);
