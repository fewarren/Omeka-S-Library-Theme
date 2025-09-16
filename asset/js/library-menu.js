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
        stylingApplied: false,
        isMenuOpen: false,
        prevFocus: null
    };

    $(document).ready(function() {
        if (State.initialized) return;
        State.initialized = true;

        console.log('Library Theme loading...');
        
        // Initialize all components
        if (typeof window.initHamburgerMenu === 'function') {
            initializeComponents();
        }
        
        // Setup cleanup on page unload
        $(window).on('beforeunload', cleanup);
        
        console.log('Library Theme ready');
    });

    function initializeComponents() {
        try {
            // Apply fixes with proper error handling
            // Removed DOM overrides; styling comes from theme CSS and settings
            if (typeof window.initHamburgerMenu === 'function') {
                window.initHamburgerMenu();
            }
            setupEventListeners();
        } catch (error) {
            console.error('Error initializing components:', error);
        }
    }

    // Setup event listeners with proper cleanup
    function setupEventListeners() {
        try {
            // Close menu when clicking outside
            const outsideClickHandler = function(e) {
                // Only close if click is outside both the toggle and the dropdown
                const $target = $(e.target);
                const insideToggle = $target.closest('.top-level-menu-toggle').length > 0;
                const insideDropdown = $target.closest('.top-level-menu-dropdown').length > 0;
                if (!insideToggle && !insideDropdown) {
                    closeHamburgerMenu();
                }
            };
            
            // Close menu when pressing Escape key (handled in unified keydown handler)
            // const escapeKeyHandler = function(e) {
            //     if (e.key === 'Escape') {
            //         closeHamburgerMenu();
            //     }
            // };
            
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
            
            // Add event listeners with jQuery (and namespaced for cleanup)
            $(document)
                .off('click.libraryTheme')
                .on('click.libraryTheme', outsideClickHandler)
                .off('keydown.libraryTheme')
                .on('keydown.libraryTheme', function(e){
                    // Focus trap while menu is open
                    if (State.isMenuOpen && (e.key === 'Tab')) {
                        const $links = $('.top-level-menu-dropdown .top-level-page-link');
                        if (!$links.length) return;
                        const first = $links[0];
                        const last = $links[$links.length - 1];
                        if (e.shiftKey && document.activeElement === first) {
                            e.preventDefault(); last.focus();
                        } else if (!e.shiftKey && document.activeElement === last) {
                            e.preventDefault(); first.focus();
                        }
                    }
                    // ESC closes
                    if (e.key === 'Escape') closeHamburgerMenu();
                })
                .off('keydown.libraryTheme', '.top-level-page-link')
                .on('keydown.libraryTheme', '.top-level-page-link', keyboardNavHandler);
            
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
            const $dropdown = $('.top-level-menu-dropdown');
            if (!$dropdown.length) return;
            State.isMenuOpen = true;
            State.prevFocus = document.activeElement || null;

            $dropdown.addClass('show');
            $('.hamburger-menu-btn').attr('aria-expanded', 'true');
            $dropdown.attr({ role: 'menu', 'aria-hidden': 'false' });

            // Focus first menu item for accessibility
            setTimeout(function() {
                const $first = $('.top-level-page-link:first');
                if ($first.length) $first.focus();
            }, 50);
        } catch (error) {
            console.error('Error opening hamburger menu:', error);
        }
    }

    /**
     * Close the hamburger menu
     */
    function closeHamburgerMenu() {
        try {
            const $dropdown = $('.top-level-menu-dropdown');
            if (!$dropdown.length) return;
            $dropdown.removeClass('show').attr('aria-hidden', 'true');
            $('.hamburger-menu-btn').attr('aria-expanded', 'false');

            // Restore focus to trigger, if we had it
            if (State.prevFocus && typeof State.prevFocus.focus === 'function') {
                setTimeout(() => { try { State.prevFocus.focus(); } catch(_){} }, 0);
            }
            State.isMenuOpen = false;
            State.prevFocus = null;
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
