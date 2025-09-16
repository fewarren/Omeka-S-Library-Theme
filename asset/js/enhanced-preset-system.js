/**
 * Enhanced Preset System for Omeka S Theme
 * Addresses UX issues by updating visible form fields when presets are applied
 */

(function() {
    'use strict';
    
    // Only run in admin interface
    if (!window.location.pathname.includes('/admin')) return;
    
    const __DBG = new URLSearchParams(location.search).has('debug');
    const __log = (...a)=>{ if(__DBG) try{ console.log(...a);}catch(e){} };
    const __warn = (...a)=>{ if(__DBG) try{ console.warn(...a);}catch(e){} };
    const __error = (...a)=>{ if(__DBG) try{ console.error(...a);}catch(e){} };
    __log('Enhanced Preset System: Initializing...');

    // Preset definitions - prefer server-provided JSON if present
    let PRESETS = (function(){
        try {
            if (window.LibraryThemePresets) return window.LibraryThemePresets;
            const el = document.getElementById('library-theme-presets');
            if (el) return JSON.parse(el.textContent);
        } catch(e) { /* ignore */ }
        return null;
    })();
    if (!PRESETS) PRESETS = {
        traditional: {
            // Typography
            h1_font_family: 'georgia',
            h1_font_size: '2rem',
            h1_font_color: '#2c4a6b',
            h1_font_weight: '600',
            h2_font_family: 'georgia',
            h2_font_size: '1.5rem',
            h2_font_color: '#2c4a6b',
            h2_font_weight: '600',
            h3_font_family: 'georgia',
            h3_font_size: '1.25rem',
            h3_font_color: '#2c4a6b',
            h3_font_weight: '500',
            body_font_family: 'helvetica',
            body_font_size: '1rem',
            body_font_color: '#333333',
            body_font_weight: '400',
            tagline_font_family: 'georgia',
            tagline_font_color: '#666666',
            tagline_font_weight: '400',
            tagline_font_style: 'italic',
            
            // Page title
            page_title_font_family: 'georgia',
            page_title_font_size: '2.5rem',
            page_title_font_color: '#2c4a6b',
            page_title_font_weight: '600',
            
            // Colors
            primary_color: '#2C4A6B',
            accent_color: '#D4AF37',
            
            // TOC
            toc_background_color: '#ffffff',
            toc_border_color: '#D4AF37',
            toc_text_color: '#2c4a6b',
            
            // Pagination
            pagination_background_color: '#2c4a6b',
            pagination_font_color: '#ffffff',
            pagination_hover_color: '#1a365d',
            pagination_button_size: 'medium'
        },
        
        modern: {
            // Typography
            h1_font_family: 'cormorant',
            h1_font_size: '2.5rem',
            h1_font_color: '#111111',
            h1_font_weight: '600',
            h2_font_family: 'cormorant',
            h2_font_size: '2rem',
            h2_font_color: '#2c4a6b',
            h2_font_weight: '600',
            h3_font_family: 'georgia',
            h3_font_size: '1.5rem',
            h3_font_color: '#2c4a6b',
            h3_font_weight: '500',
            body_font_family: 'helvetica',
            body_font_size: '1.125rem',
            body_font_color: '#111111',
            body_font_weight: '400',
            tagline_font_family: 'georgia',
            tagline_font_color: '#f7c97f',
            tagline_font_weight: '400',
            tagline_font_style: 'normal',
            
            // Page title
            page_title_font_family: 'cormorant',
            page_title_font_size: '3rem',
            page_title_font_color: '#111111',
            page_title_font_weight: '700',
            
            // Colors
            primary_color: '#b37c05',
            accent_color: '#D4AF37',

            // TOC
            toc_background_color: '#ffffff',
            toc_border_color: '#D4AF37',
            toc_text_color: '#b37c05',
            toc_hover_text_color: '#ffffff',
            toc_hover_background_color: '#f3d491',

            // Pagination
            pagination_background_color: '#f3d491',
            pagination_font_color: '#b37c05',
            pagination_hover_color: '#1a365d',
            pagination_font_color: '#ffffff',
            pagination_hover_color: '#f7c97f',
            pagination_button_size: 'large'
        }
    };
    
    // Utility functions
    function findField(name) {
        return document.querySelector(`[name="${name}"]`) || 
               document.querySelector(`[name$="[${name}]"]`);
    }
    
    function setFieldValue(name, value) {
        // Input validation
        if (typeof name !== 'string' || name.trim() === '') {
            __error('Enhanced Preset: Invalid field name provided');
            return false;
        }
        
        if (value === null || value === undefined) {
            console.warn(`Enhanced Preset: Null/undefined value for field: ${name}`);
            return false;
        }
        
        const field = findField(name);
        if (!field) {
            __warn(`Enhanced Preset: Field not found: ${name}`);
            return false;
        }
        
        // Sanitize value based on field type
        let sanitizedValue = value;
        if (field.type === 'checkbox') {
            field.checked = !!(value === 1 || value === '1' || value === true);
        } else if (field.type === 'color') {
            // Validate color format
            if (typeof value === 'string' && /^#[0-9A-Fa-f]{6}$/.test(value)) {
                field.value = value;
            } else {
                __warn(`Enhanced Preset: Invalid color format for ${name}: ${value}`);
                return false;
            }
        } else {
            // Sanitize string values
            sanitizedValue = String(value).replace(/[<>'"]/g, '');
            field.value = sanitizedValue;
        }
        
        // Trigger events to notify other scripts
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        
        __log(`Enhanced Preset: Set ${name} = ${sanitizedValue}`);
        return true;
    }
    
    function applyPreset(presetName) {
        const preset = PRESETS[presetName];
        if (!preset) {
            __error(`Enhanced Preset: Unknown preset: ${presetName}`);
            return;
        }
        
        __log(`Enhanced Preset: Applying ${presetName} preset...`);
        
        let appliedCount = 0;
        let totalCount = 0;
        
        Object.keys(preset).forEach(fieldName => {
            totalCount++;
            if (setFieldValue(fieldName, preset[fieldName])) {
                appliedCount++;
            }
        });
        
        __log(`Enhanced Preset: Applied ${appliedCount}/${totalCount} settings`);
        
        // Show user feedback
        showNotification(`Applied ${presetName} preset: ${appliedCount}/${totalCount} settings updated`, 'success');
        
        return appliedCount;
    }
    
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelectorAll('.preset-notification');
        existing.forEach(el => el.remove());
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `preset-notification preset-notification--${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
            color: ${type === 'success' ? '#155724' : '#721c24'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};
            border-radius: 4px;
            padding: 12px 16px;
            max-width: 400px;
            z-index: 10000;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    function createPresetControls() {
        const stylePresetField = findField('style_preset');
        const presetModeField = findField('preset_mode');
        const applyPresetField = findField('apply_preset_now');
        
        if (!stylePresetField) {
            console.warn('Enhanced Preset: style_preset field not found');
            return;
        }
        
        // Create enhanced controls container
        const container = document.createElement('div');
        container.className = 'enhanced-preset-controls';
        container.style.cssText = `
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 16px;
            margin: 16px 0;
        `;
        
        container.innerHTML = `
            <h4 style="margin: 0 0 12px 0; color: #495057;">Preset Controls</h4>
            <div style="margin-bottom: 12px;">
                <button type="button" id="apply-traditional-preset" style="margin-right: 8px; padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Apply Traditional Preset
                </button>
                <button type="button" id="apply-modern-preset" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Apply Modern Preset
                </button>
            </div>
            <div style="font-size: 12px; color: #6c757d;">
                These buttons will load preset values into the form fields below, allowing you to see and modify them before saving.
            </div>
        `;
        
        // Insert after the preset mode field or style preset field
        const insertAfter = presetModeField?.closest('.field') || 
                           stylePresetField?.closest('.field') || 
                           stylePresetField;
        
        if (insertAfter && insertAfter.parentNode) {
            insertAfter.parentNode.insertBefore(container, insertAfter.nextSibling);
        }
        
        // Wire up button events
        const traditionalBtn = container.querySelector('#apply-traditional-preset');
        const modernBtn = container.querySelector('#apply-modern-preset');
        
        traditionalBtn?.addEventListener('click', () => {
            if (stylePresetField) stylePresetField.value = 'traditional';
            applyPreset('traditional');
        });
        
        modernBtn?.addEventListener('click', () => {
            if (stylePresetField) stylePresetField.value = 'modern';
            applyPreset('modern');
        });
        
        // Auto-apply when apply_preset_now checkbox is checked
        if (applyPresetField) {
            let isApplying = false; // Flag to prevent re-entrancy
            
            applyPresetField.addEventListener('change', function() {
                if (this.checked && !isApplying) {
                    // Set flag and disable checkbox to prevent concurrent applications
                    isApplying = true;
                    const originalDisabled = this.disabled;
                    this.disabled = true;
                    
                    try {
                        const selectedPreset = stylePresetField.value || 'traditional';
                        
                        // Create async wrapper for applyPreset to handle completion properly
                        const applyPresetAsync = () => {
                            return new Promise((resolve, reject) => {
                                try {
                                    const result = applyPreset(selectedPreset);
                                    // Small delay to ensure all DOM updates complete
                                    setTimeout(() => resolve(result), 100);
                                } catch (error) {
                                    reject(error);
                                }
                            });
                        };
                        
                        applyPresetAsync()
                            .then((result) => {
                                __log(`Enhanced Preset: Successfully applied ${selectedPreset} preset`);
                            })
                            .catch((error) => {
                                __error(`Enhanced Preset: Error applying ${selectedPreset} preset:`, error);
                                showNotification(`Error applying ${selectedPreset} preset. Please try again.`, 'error');
                            })
                            .finally(() => {
                                // Always reset state in finally block
                                this.checked = false;
                                this.disabled = originalDisabled;
                                isApplying = false;
                            });
                            
                    } catch (error) {
                        // Handle immediate errors
                        __error('Enhanced Preset: Immediate error:', error);
                        this.checked = false;
                        this.disabled = originalDisabled;
                        isApplying = false;
                        showNotification('Error applying preset. Please try again.', 'error');
                    }
                }
            });
        }
        
        console.log('Enhanced Preset: Controls created successfully');
    }
    
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        console.log('Enhanced Preset: DOM ready, creating controls...');
        createPresetControls();
    }
    
    // Initialize
    init();
    
    // Expose for debugging
    window.EnhancedPresetSystem = {
        applyPreset,
        PRESETS,
        findField,
        setFieldValue
    };
    
})();
