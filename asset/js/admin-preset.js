(function(){
  'use strict';
  
  // Security utilities
  const Security = {
    // Sanitize text content to prevent XSS
    sanitizeText: function(text) {
      if (typeof text !== 'string') return '';
      return text.replace(/[<>&"']/g, function(match) {
        const escapeMap = {
          '<': '&lt;',
          '>': '&gt;',
          '&': '&amp;',
          '"': '&quot;',
          "'": '&#x27;'
        };
        return escapeMap[match];
      });
    },
    
    // Validate color format
    isValidColor: function(color) {
      return typeof color === 'string' && /^#[0-9A-Fa-f]{6}$/.test(color);
    },
    
    // Validate preset data structure
    validatePresetData: function(data) {
      if (!data || typeof data !== 'object') return false;
      
      // Check for required fields and validate types
      const requiredFields = ['primary_color', 'h1_font_family', 'body_font_family'];
      for (const field of requiredFields) {
        if (!(field in data)) return false;
      }
      
      // Validate color fields
      const colorFields = ['primary_color', 'sacred_gold', 'warm_earth', 'soft_sage', 'warm_cream'];
      for (const field of colorFields) {
        if (data[field] && !this.isValidColor(data[field])) return false;
      }
      
      return true;
    },
    
    // Sanitize localStorage data
    sanitizeStorageData: function(data) {
      const sanitized = {};
      for (const [key, value] of Object.entries(data)) {
        // Only allow alphanumeric keys with underscores
        if (!/^[a-zA-Z0-9_]+$/.test(key)) continue;
        
        // Sanitize values based on type
        if (typeof value === 'string') {
          sanitized[key] = this.sanitizeText(value);
        } else if (typeof value === 'number' || typeof value === 'boolean') {
          sanitized[key] = value;
        }
      }
      return sanitized;
    }
  };
  
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }
  
  function findBySettingName(name){
    // Sanitize input
    if (typeof name !== 'string' || !/^[a-zA-Z0-9_\[\]]+$/.test(name)) {
      console.warn('[Preset] Invalid setting name:', name);
      return null;
    }
    
    let el = document.querySelector(`[name="${CSS.escape(name)}"]`);
    if (el) return el;
    el = document.querySelector(`[name$="[${CSS.escape(name)}]"]`);
    if (el) return el;
    const all = qsa('[name]');
    return all.find(e => e.name === name || e.name.endsWith(`[${name}]`)) || null;
  }
  
  function setField(name, value){
    const el = findBySettingName(name);
    if (!el) { 
      console.warn('[Preset] Field not found:', Security.sanitizeText(name)); 
      return; 
    }
    
    // Validate and sanitize value
    let sanitizedValue = value;
    if (typeof value === 'string') {
      sanitizedValue = Security.sanitizeText(value);
      
      // Additional validation for color fields
      if (name.includes('color') && !Security.isValidColor(value)) {
        console.warn('[Preset] Invalid color value:', Security.sanitizeText(value));
        return;
      }
    }
    
    try {
      if (el.type === 'checkbox') {
        el.checked = !!(sanitizedValue === 1 || sanitizedValue === '1' || sanitizedValue === true);
      } else {
        el.value = sanitizedValue;
      }
      el.dispatchEvent(new Event('input', {bubbles:true}));
      el.dispatchEvent(new Event('change', {bubbles:true}));
    } catch (error) {
      console.error('[Preset] Error setting field:', error);
    }
  }

  // Traditional (Default): conservative library-friendly
  const PRESET_TRADITIONAL = {
    // Branding
    logo_link_page: '/',
    site_tagline: 'Menu',
    tagline_font_family: 'georgia', tagline_font_color: '#666666', tagline_font_weight: '400', tagline_font_style: 'italic',
    logo_height: '100px', header_height: '100px', header_layout: 'logo_with_tagline',

    // Typography
    h1_font_family: 'georgia', h1_font_size: '2rem',   h1_font_color: '#2c4a6b', h1_font_weight: '600', h1_font_style: 'normal',
    h2_font_family: 'georgia', h2_font_size: '1.5rem', h2_font_color: '#2c4a6b', h2_font_weight: '600', h2_font_style: 'normal',
    h3_font_family: 'georgia', h3_font_size: '1.25rem', h3_font_color: '#2c4a6b', h3_font_weight: '500', h3_font_style: 'normal',
    body_font_family: 'helvetica', body_font_size: '1rem', body_font_color: '#333333', body_font_weight: '400', body_font_style: 'normal',

    // Color palette
    primary_color: '#2c4a6b', sacred_gold: '#d4af37', warm_earth: '#8B4513', soft_sage: '#9CAF88', warm_cream: '#F5F5DC',

    // TOC
    toc_font_family: 'helvetica', toc_font_size: 'normal', toc_font_weight: '400', toc_text_color: '#2c4a6b', toc_hover_color: '#d4af37',
    toc_background_color: '#ffffff', toc_border_color: '#d4af37', toc_border_width: '2px', toc_border_radius: '8px',

    // Pagination
    pagination_font_color: '#ffffff', pagination_background_color: '#2c5aa0', pagination_hover_color: '#1a365d',
    pagination_font_size: '1rem', pagination_button_size: 'extra_small',

    // Footer
    footer_background_color: '#4a6fa5', footer_text_color: '#ffffff',
    footer_banner_height: 'standard',

    // Toggles
    enable_toc: 1, enable_pagination: 1,

    // Search
    search_enable: 0, search_position: 'none',
    search_background_color: '#ffffff', search_text_color: '#000000',
    search_font_family: 'helvetica', search_font_weight: '400',
    search_button_label: 'Go', search_button_icon: 'none',

    // Foundation defaults
    stylesheet: 'default', nav_layout: 'dropdown', browse_layout: 'grid', show_layout: 'stack'
  };

  // Modern (Sufism): mimic sufismreoriented.org
  const PRESET_MODERN = {
    // Branding
    logo_link_page: '/',
    site_tagline: 'Menu',
    tagline_font_family: 'georgia', tagline_font_color: '#f7c97f', tagline_font_weight: '400', tagline_font_style: 'normal',
    logo_height: '100px', header_height: '100px', header_layout: 'logo_with_tagline',

    // Typography
    h1_font_family: 'cormorant', h1_font_size: '2.5rem', h1_font_color: '#111111', h1_font_weight: '600', h1_font_style: 'normal',
    h2_font_family: 'cormorant', h2_font_size: '2rem',   h2_font_color: '#2c4a6b', h2_font_weight: '600', h2_font_style: 'normal',
    h3_font_family: 'georgia',   h3_font_size: '1.5rem', h3_font_color: '#2c4a6b', h3_font_weight: '500', h3_font_style: 'normal',
    body_font_family: 'helvetica', body_font_size: '1.125rem', body_font_color: '#111111', body_font_weight: '400', body_font_style: 'normal',

    // Color palette
    primary_color: '#2c4a6b', sacred_gold: '#d4af37', warm_earth: '#8B4513', soft_sage: '#9CAF88', warm_cream: '#F5F5DC',

    // TOC
    toc_font_family: 'helvetica', toc_font_size: 'normal', toc_font_weight: '700', toc_text_color: '#2c4a6b', toc_hover_color: '#d4af37',
    toc_background_color: '#ffffff', toc_border_color: '#d4af37', toc_border_width: '2px', toc_border_radius: '8px',

    // Pagination
    pagination_font_color: '#ffffff', pagination_background_color: '#2c5aa0', pagination_hover_color: '#1a365d',
    pagination_font_size: '1rem', pagination_button_size: 'small',

    // Footer
    footer_background_color: '#2C4A6B', footer_text_color: '#ffffff',
    footer_banner_height: 'compact',

    // Toggles
    enable_toc: 1, enable_pagination: 1,

    // Search (visible in header right)
    search_enable: 1, search_position: 'header_right',
    search_background_color: '#2C4A6B', search_text_color: '#ffffff',
    search_font_family: 'helvetica', search_font_weight: '400',
    search_button_label: 'Go', search_button_icon: 'none',

    // Foundation defaults (safe baseline)
    stylesheet: 'default', nav_layout: 'dropdown', browse_layout: 'grid', show_layout: 'stack'
  };

  function presetMap(key){ 
    const sanitizedKey = Security.sanitizeText(key);
    return sanitizedKey === 'modern' ? PRESET_MODERN : PRESET_TRADITIONAL; 
  }

  // Secure local save/restore with validation
  function saveLocal(){
    try {
      // Check if existing preset exists and prompt for confirmation
      const existingPreset = localStorage.getItem('sufism_theme_local_preset');
      if (existingPreset) {
        const confirmOverwrite = window.confirm(
          'A Local preset already exists in this browser. Do you want to overwrite it with the current settings?\n\n' +
          'Click "OK" to overwrite, or "Cancel" to abort saving.'
        );
        if (!confirmOverwrite) {
          showNotification('Save cancelled. Your existing Local preset was not modified.', 'info');
          return;
        }
      }
      
      const names = qsa('[name]');
      const snapshot = {};
      names.forEach(el => {
        if (!el.name || !/^[a-zA-Z0-9_\[\]]+$/.test(el.name)) return;
        if (el.type === 'checkbox') snapshot[el.name] = el.checked ? 1 : 0;
        else snapshot[el.name] = Security.sanitizeText(el.value || '');
      });
      
      // Validate before saving
      if (!Security.validatePresetData(snapshot)) {
        showNotification('Invalid preset data. Save cancelled.', 'error');
        return;
      }
      
      localStorage.setItem('sufism_theme_local_preset', JSON.stringify(snapshot));
      showNotification('Saved current settings as Local preset in this browser.', 'success');
    } catch (error) {
      console.error('[Preset] Error saving local preset:', error);
      showNotification('Error saving preset. Please try again.', 'error');
    }
  }
  
  function restoreLocal(){
    try {
      const raw = localStorage.getItem('sufism_theme_local_preset');
      if (!raw){ 
        showNotification('No Local preset saved.', 'info'); 
        return; 
      }
      
      const snapshot = JSON.parse(raw);
      
      // Validate and sanitize loaded data
      if (!Security.validatePresetData(snapshot)) {
        showNotification('Invalid preset data found. Restore cancelled.', 'error');
        return;
      }
      
      const sanitizedSnapshot = Security.sanitizeStorageData(snapshot);
      Object.keys(sanitizedSnapshot).forEach(k => setField(k, sanitizedSnapshot[k]));
      showNotification('Restored settings from Local preset. Review and Save.', 'success');
    } catch(error) { 
      console.error('[Preset] Error restoring local preset:', error);
      showNotification('Failed to parse Local preset.', 'error'); 
    }
  }

  // Secure notification system
  function showNotification(message, type = 'info') {
    const note = document.createElement('div');
    note.textContent = Security.sanitizeText(message);
    
    const typeStyles = {
      success: 'background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7;',
      error: 'background:#ffebee; color:#c62828; border:1px solid #ef9a9a;',
      info: 'background:#e3f2fd; color:#1565c0; border:1px solid #90caf9;'
    };
    
    note.style.cssText = `margin:8px 0; padding:6px 10px; border-radius:4px; ${typeStyles[type] || typeStyles.info}`;
    
    // Find safe insertion point
    const presetSel = findBySettingName('style_preset');
    const container = presetSel?.closest('div,fieldset,section,form') || document.body;
    container.appendChild(note);
    
    setTimeout(() => {
      if (note.parentNode) {
        note.parentNode.removeChild(note);
      }
    }, 5000);
  }

  function injectControls(anchor){
    // Wire the one-shot apply checkbox with security improvements
    let apply = findBySettingName('apply_preset_now');
    if (apply){
      // Remove any existing listeners to prevent duplicates
      const newApply = apply.cloneNode(true);
      apply.parentNode.replaceChild(newApply, apply);
      apply = newApply;
      
      apply.addEventListener('change', function(){
        if (!this.checked) return;
        
        // Disable the control to prevent re-clicks during processing
        const originalDisabled = this.disabled;
        this.disabled = true;
        
        const presetSel = findBySettingName('style_preset');
        const key = presetSel ? Security.sanitizeText(presetSel.value) : 'traditional';
        const preset = presetMap(key);
        console.log('[Preset] Applying preset:', key);
        
        try {
          // Apply preset fields asynchronously with error handling
          const applyPresetAsync = () => {
            return new Promise((resolve, reject) => {
              try {
                // Validate preset before applying
                if (!Security.validatePresetData(preset)) {
                  reject(new Error('Invalid preset data'));
                  return;
                }
                
                Object.keys(preset).forEach(k => setField(k, preset[k]));
                resolve();
              } catch (error) {
                reject(error);
              }
            });
          };
          
          applyPresetAsync()
            .then(() => {
              // Success: Reset checkbox and show success message
              this.checked = false;
              this.disabled = originalDisabled;
              showNotification('Preset applied to fields. Review changes and click Save to persist.', 'success');
            })
            .catch((error) => {
              // Error: Reset checkbox, restore state, and show error message
              console.error('[Preset] Error applying preset:', error);
              this.checked = false;
              this.disabled = originalDisabled;
              showNotification('Error applying preset. Please try again or apply settings manually.', 'error');
            });
        } catch (error) {
          // Immediate error: Reset state and show error
          console.error('[Preset] Immediate error applying preset:', error);
          this.checked = false;
          this.disabled = originalDisabled;
          showNotification('Error applying preset. Please try again.', 'error');
        }
      });
    }
    
    // Local save/restore buttons with CSP-compliant event handlers
    let bar = document.getElementById('preset-local-bar');
    if (!bar && anchor){
      bar = document.createElement('div');
      bar.id = 'preset-local-bar';
      bar.style.cssText = 'margin:8px 0; display:flex; gap:8px;';
      
      const saveBtn = document.createElement('button'); 
      saveBtn.type = 'button'; 
      saveBtn.textContent = 'Save Local Preset';
      saveBtn.addEventListener('click', saveLocal);
      
      const loadBtn = document.createElement('button'); 
      loadBtn.type = 'button'; 
      loadBtn.textContent = 'Restore Local Preset';
      loadBtn.addEventListener('click', restoreLocal);
      
      bar.appendChild(saveBtn); 
      bar.appendChild(loadBtn);
      anchor.appendChild(bar);
    }
  }

  function init(){
    try {
      if (!location.pathname.includes('/admin')) return;
      const presetSel = findBySettingName('style_preset');
      if (!presetSel) return;
      const anchor = presetSel.closest('div,fieldset,section,form');
      injectControls(anchor);
      window.__adminPresetLoaded = true;
    } catch(error) {
      console.error('[Preset] Initialization error:', error);
      window.__adminPresetLoaded = false;
    }
  }
  
  window.__adminPresetLoaded = false;
  document.addEventListener('DOMContentLoaded', init);
})();
