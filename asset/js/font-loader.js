/**
 * Font Loading Optimization
 * 
 * This script optimizes font loading performance and provides fallbacks
 * when Google Fonts fail to load.
 */

(function() {
    'use strict';
    
    // Font loading configuration
    const FONT_CONFIG = {
        timeout: 3000, // 3 seconds timeout
        families: [
            // Keep aligned with theme presets and CSS fallbacks
            'Cormorant Garamond:400,600,700',
            'Merriweather:400,700',
            'Playfair Display:400,600,700'
        ]
    };
    
    // Font loading states
    const FontLoadingStates = {
        LOADING: 'fonts-loading',
        LOADED: 'fonts-loaded',
        FAILED: 'fonts-failed'
    };
    
    /**
     * Initialize font loading
     */
    function initFontLoading() {
        // Add loading class
        document.documentElement.classList.add(FontLoadingStates.LOADING);
        
        // Check if Web Font Loader is available
        if (typeof WebFont !== 'undefined') {
            loadFontsWithWebFontLoader();
        } else {
            // Fallback to native font loading API
            loadFontsNatively();
        }
    }
    
    /**
     * Load fonts using Web Font Loader
     */
    function loadFontsWithWebFontLoader() {
        WebFont.load({
            google: {
                families: FONT_CONFIG.families
            },
            timeout: FONT_CONFIG.timeout,
            active: function() {
                onFontsLoaded();
            },
            inactive: function() {
                onFontsFailed();
            },
            fontactive: function(familyName, fvd) {
                console.log('Font loaded:', familyName, fvd);
            },
            fontinactive: function(familyName, fvd) {
                console.warn('Font failed to load:', familyName, fvd);
            }
        });
    }
    
    /**
     * Load fonts using native Font Loading API
     */
    function loadFontsNatively() {
        if (!('fonts' in document)) {
            // Font Loading API not supported, assume fonts are loaded
            setTimeout(onFontsLoaded, 100);
            return;
        }
        
        const fontPromises = [];
        
        // Create font face objects for critical fonts
        const criticalFonts = [
            new FontFace('Roboto', 'url(https://fonts.gstatic.com/s/roboto/v30/KFOmCnqEu92Fr1Mu4mxK.woff2)', {
                weight: '400',
                style: 'normal',
                display: 'swap'
            }),
            new FontFace('Open Sans', 'url(https://fonts.gstatic.com/s/opensans/v34/memSYaGs126MiZpBA-UvWbX2vVnXBbObj2OVZyOOSr4dVJWUgsjZ0B4gaVc.woff2)', {
                weight: '400',
                style: 'normal',
                display: 'swap'
            }),
            new FontFace('Merriweather', 'url(https://fonts.gstatic.com/s/merriweather/v30/u-440qyriQwlOrhSvowK_l5-fCZMdeX3rsHo.woff2)', {
                weight: '400',
                style: 'normal',
                display: 'swap'
            }),
            new FontFace('Playfair Display', 'url(https://fonts.gstatic.com/s/playfairdisplay/v30/nuFvD-vYSZviVYUb_rj3ij__anPXJzDwcbmjWBN2PKdFvXDXbtXK-F2qO0isEw.woff2)', {
                weight: '400',
                style: 'normal',
                display: 'swap'
            })
        ];
        
        // Load critical fonts
        criticalFonts.forEach(font => {
            document.fonts.add(font);
            fontPromises.push(font.load());
        });
        
        // Set timeout for font loading
        const timeoutPromise = new Promise((resolve) => {
            setTimeout(() => resolve('timeout'), FONT_CONFIG.timeout);
        });
        
        // Wait for fonts to load or timeout
        Promise.race([
            Promise.all(fontPromises),
            timeoutPromise
        ]).then((result) => {
            if (result === 'timeout') {
                onFontsFailed();
            } else {
                onFontsLoaded();
            }
        }).catch(() => {
            onFontsFailed();
        });
    }
    
    /**
     * Handle successful font loading
     */
    function onFontsLoaded() {
        document.documentElement.classList.remove(FontLoadingStates.LOADING);
        document.documentElement.classList.add(FontLoadingStates.LOADED);
        
        // Trigger custom event
        document.dispatchEvent(new CustomEvent('fontsLoaded'));
        
        console.log('Fonts loaded successfully');
    }
    
    /**
     * Handle font loading failure
     */
    function onFontsFailed() {
        document.documentElement.classList.remove(FontLoadingStates.LOADING);
        document.documentElement.classList.add(FontLoadingStates.FAILED);
        
        // Trigger custom event
        document.dispatchEvent(new CustomEvent('fontsFailed'));
        
        console.warn('Font loading failed, using fallback fonts');
    }
    
    /**
     * Preload critical fonts
     */
    function preloadCriticalFonts() {
        const criticalFontUrls = [
            'https://fonts.gstatic.com/s/roboto/v30/KFOmCnqEu92Fr1Mu4mxK.woff2',
            'https://fonts.gstatic.com/s/opensans/v34/memSYaGs126MiZpBA-UvWbX2vVnXBbObj2OVZyOOSr4dVJWUgsjZ0B4gaVc.woff2',
            'https://fonts.gstatic.com/s/merriweather/v30/u-440qyriQwlOrhSvowK_l5-fCZMdeX3rsHo.woff2',
            'https://fonts.gstatic.com/s/playfairdisplay/v30/nuFvD-vYSZviVYUb_rj3ij__anPXJzDwcbmjWBN2PKdFvXDXbtXK-F2qO0isEw.woff2'
        ];
        
        criticalFontUrls.forEach(url => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'font';
            link.type = 'font/woff2';
            link.crossOrigin = 'anonymous';
            link.href = url;
            document.head.appendChild(link);
        });
    }
    
    /**
     * Check if fonts are already cached
     */
    function checkFontCache() {
        if ('caches' in window) {
            caches.open('font-cache-v1').then(cache => {
                return cache.keys();
            }).then(keys => {
                if (keys.length > 0) {
                    console.log('Fonts found in cache');
                    onFontsLoaded();
                } else {
                    initFontLoading();
                }
            }).catch(() => {
                initFontLoading();
            });
        } else {
            initFontLoading();
        }
    }
    
    /**
     * Initialize when DOM is ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                preloadCriticalFonts();
                checkFontCache();
            });
        } else {
            preloadCriticalFonts();
            checkFontCache();
        }
    }
    
    // Start initialization
    init();
    
    // Expose API for manual control
    window.FontLoader = {
        init: initFontLoading,
        preload: preloadCriticalFonts,
        states: FontLoadingStates
    };
    
})();
