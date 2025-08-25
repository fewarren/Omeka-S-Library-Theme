(function(){
  'use strict';
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }
  function findBySettingName(name){
    let el = document.querySelector(`[name="${name}"]`);
    if (el) return el;
    el = document.querySelector(`[name$="[${name}]"]`);
    if (el) return el;
    const all = qsa('[name]');
    return all.find(e => e.name === name || e.name.endsWith(`[${name}]`)) || null;
  }
  function setField(name, value){
    const el = findBySettingName(name);
    if (!el) { console.warn('[Preset] Field not found:', name); return; }
    if (el.type === 'checkbox') {
      el.checked = !!(value === 1 || value === '1' || value === true);
    } else {
      el.value = value;
    }
    el.dispatchEvent(new Event('input', {bubbles:true}));
    el.dispatchEvent(new Event('change', {bubbles:true}));
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

  function presetMap(key){ return key === 'modern' ? PRESET_MODERN : PRESET_TRADITIONAL; }

  // Optional: Local save/restore in browser localStorage (admin-side convenience only)
  function saveLocal(){
    const names = qsa('[name]');
    const snapshot = {};
    names.forEach(el => {
      if (!el.name) return;
      if (el.type === 'checkbox') snapshot[el.name] = el.checked ? 1 : 0;
      else snapshot[el.name] = el.value;
    });
    localStorage.setItem('sufism_theme_local_preset', JSON.stringify(snapshot));
    alert('Saved current settings as Local preset in this browser.');
  }
  function restoreLocal(){
    const raw = localStorage.getItem('sufism_theme_local_preset');
    if (!raw){ alert('No Local preset saved.'); return; }
    try {
      const snapshot = JSON.parse(raw);
      Object.keys(snapshot).forEach(k => setField(k, snapshot[k]));
      alert('Restored settings from Local preset. Review and Save.');
    } catch(e){ alert('Failed to parse Local preset.'); }
  }

  function injectControls(anchor){
    // Wire the one-shot apply checkbox
    let apply = (typeof findBySettingName==='function') ? findBySettingName('apply_preset_now') : qs('[name="apply_preset_now"]');
    if (apply){
      apply.addEventListener('change', function(){
        if (!this.checked) return;
        const presetSel = (typeof findBySettingName==='function') ? findBySettingName('style_preset') : qs('[name="style_preset"]');
        const key = presetSel ? presetSel.value : 'traditional';
        const preset = presetMap(key);
        console.log('[Preset] Applying preset:', key, preset);
        Object.keys(preset).forEach(k => setField(k, preset[k]));
        this.checked = false; // one-shot to avoid surprises
        // Notify admin in-page without alert
        const note = document.createElement('div');
        note.textContent = 'Preset applied to fields. Review changes and click Save to persist.';
        note.style.cssText = 'margin:8px 0; padding:6px 10px; background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; border-radius:4px;';
        (presetSel && presetSel.closest('div,fieldset,section,form')||document.body).appendChild(note);
        setTimeout(()=> note.remove(), 5000);
      });
    }
    // Local save/restore buttons
    let bar = document.getElementById('preset-local-bar');
    if (!bar){
      bar = document.createElement('div');
      bar.id = 'preset-local-bar';
      bar.style.cssText = 'margin:8px 0; display:flex; gap:8px;';
      const saveBtn = document.createElement('button'); saveBtn.type='button'; saveBtn.textContent='Save Local Preset'; saveBtn.onclick=saveLocal;
      const loadBtn = document.createElement('button'); loadBtn.type='button'; loadBtn.textContent='Restore Local Preset'; loadBtn.onclick=restoreLocal;
      bar.appendChild(saveBtn); bar.appendChild(loadBtn);
      anchor && anchor.appendChild(bar);
    }
  }

  function init(){
    if (!location.pathname.includes('/admin')) return;
    const presetSel = (typeof findBySettingName==='function') ? findBySettingName('style_preset') : qs('[name="style_preset"]');
    if (!presetSel) return;
    const anchor = presetSel.closest('div,fieldset,section,form');
    injectControls(anchor);
    try { window.__adminPresetLoaded = true; } catch(e) {}
  }
  try { window.__adminPresetLoaded = false; } catch(e) {}
  document.addEventListener('DOMContentLoaded', init);
})();

