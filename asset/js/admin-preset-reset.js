// Admin helper: reset style fields to match chosen preset when admin clicks a button
(function(){
  'use strict';
  function bySel(sel, root){ return (root||document).querySelector(sel); }
  function allSel(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }

  function applyPreset(name){
    const PRESETS = {
      modern: {
        // Fonts
        'h1_font_family': 'cormorant', 'h1_font_size': '2.5rem', 'h1_font_color': '#b37c05', 'h1_font_weight': '600',
        'h2_font_family': 'cormorant', 'h2_font_size': '2rem',   'h2_font_color': '#b37c05', 'h2_font_weight': '600',
        'h3_font_family': 'georgia',   'h3_font_size': '1.5rem', 'h3_font_color': '#b37c05', 'h3_font_weight': '500',
        'body_font_family': 'helvetica','body_font_size': '1.125rem','body_font_color': '#b37c05','body_font_weight': '400',
        // Colors
        'primary_color': '#2C4A6B','sacred_gold': '#D4AF37',
        // TOC
        'toc_font_family': 'helvetica','toc_font_size': 'normal','toc_font_weight': '700','toc_font_style': 'normal','toc_text_color': '#2c4a6b','toc_hover_text_color': '#ffffff','toc_hover_background_color': '#d4af37','toc_background_color': '#ffffff','toc_border_color': '#D4AF37','toc_border_width': '2px','toc_border_radius': '8px',
        // Menu/footer/pagination
        'menu_background_color': '#2C4A6B','menu_text_color': '#ffffff','menu_font_family': 'helvetica',
        'footer_background_color': '#ffffff','footer_text_color': '#000000',
        'pagination_font_color': '#ffffff','pagination_background_color': '#2c5aa0','pagination_hover_color': '#1a365d',
        // Header/logo
        'header_height': '100','logo_height': '100'
      },
      traditional: {
        'h1_font_family': 'georgia', 'h1_font_size': '2rem',   'h1_font_color': '#1F3A5F', 'h1_font_weight': '600',
        'h2_font_family': 'georgia', 'h2_font_size': '1.5rem', 'h2_font_color': '#1F3A5F', 'h2_font_weight': '600',
        'h3_font_family': 'georgia', 'h3_font_size': '1.25rem','h3_font_color': '#1F3A5F', 'h3_font_weight': '500',
        'body_font_family': 'helvetica', 'body_font_size': '1rem', 'body_font_color': '#2F3542', 'body_font_weight': '400',
        'tagline_font_family': 'georgia', 'tagline_font_weight': '400', 'tagline_font_style': 'italic', 'tagline_font_color': '#5A6470',
        'tagline_hover_text_color': '#ffffff', 'tagline_hover_background_color': '#7A1E3A',
        'primary_color': '#1F3A5F', 'sacred_gold': '#7A1E3A',
        'toc_font_family': 'helvetica','toc_font_size': 'normal','toc_font_weight': '400','toc_font_style': 'normal','toc_text_color': '#1F3A5F','toc_hover_text_color': '#ffffff','toc_hover_background_color': '#7A1E3A','toc_background_color': '#ffffff','toc_border_color': '#7A1E3A','toc_border_width': '2px','toc_border_radius': '8px',
        'pagination_font_color': '#ffffff','pagination_background_color': '#1F3A5F','pagination_hover_color': '#7A1E3A',
        'menu_background_color': '#1F3A5F','menu_text_color': '#ffffff','menu_font_family': 'helvetica',
        'footer_background_color': '#f7f8fa','footer_text_color': '#111111',
        'header_height': '100','logo_height': '100'
      }
    };
    const map = PRESETS[name];
    if (!map) return;
    Object.keys(map).forEach((field)=>{
      const el = bySel(`[name="${field}"]`);
      if (!el) return;
      el.value = map[field];
      el.dispatchEvent(new Event('input', {bubbles:true}));
      el.dispatchEvent(new Event('change', {bubbles:true}));
    });
    // Also update the style_preset select to reflect the applied preset
    const presetSel = bySel('[name="style_preset"]');
    if (presetSel) { presetSel.value = name === 'modern' ? 'modern' : 'traditional'; }
  }

  function onPresetChange(){
    const presetSel = bySel('[name="style_preset"]');
    if (!presetSel) return;
    const anchor = presetSel.closest('div,fieldset,section') || presetSel.parentElement || document.body;
    // Controls: two buttons for clarity
    let btnModern = bySel('#apply-modern-preset');
    let btnTraditional = bySel('#apply-traditional-preset');
    if (!btnModern) {
      btnModern = document.createElement('button');
      btnModern.type = 'button';
      btnModern.id = 'apply-modern-preset';
      btnModern.className = 'apply-style-preset';
      btnModern.textContent = 'Apply Modern Preset';
    }
    if (!btnTraditional) {
      btnTraditional = document.createElement('button');
      btnTraditional.type = 'button';
      btnTraditional.id = 'apply-traditional-preset';
      btnTraditional.className = 'apply-style-preset';
      btnTraditional.textContent = 'Apply Traditional Preset';
    }
    // Insert description and buttons once
    if (!bySel('#preset-help-text')){
      const info = document.createElement('div');
      info.id = 'preset-help-text';
      info.className = 'preset-help-text';
      info.style.cssText = 'margin: 6px 0 10px; color: #555; font-size: 0.9em;';
      info.textContent = 'Apply a preset to populate the form fields. You can tweak values and then Save.';
      const wrap = document.createElement('div');
      wrap.style.cssText = 'display:flex; gap:8px; margin:6px 0 12px;';
      wrap.appendChild(btnTraditional);
      wrap.appendChild(btnModern);
      anchor.appendChild(info);
      anchor.appendChild(wrap);
    }
    btnModern.addEventListener('click', ()=> applyPreset('modern'));
    btnTraditional.addEventListener('click', ()=> applyPreset('traditional'));
  }

  function init(){

	  // If the admin checks "Apply Preset to Settings" and submits, copy preset values into fields
	  function hookFormSubmit(){
	    if (!location.pathname.includes('/admin')) return;
	    const form = document.querySelector('form');
	    if (!form) return;
	    form.addEventListener('submit', function(){
	      const apply = bySel('[name="apply_preset_to_settings"]', form);
	      if (!apply || !apply.checked) return;
	      const presetSel = bySel('[name="style_preset"]', form);
	      const key = presetSel ? presetSel.value : 'traditional';
	      // Apply synchronously so posted values are from the preset
	      applyPreset(key === 'modern' ? 'modern' : 'traditional');
	      // Also clear the one-shot checkbox to avoid repeated application
	      apply.checked = false;
	    }, { capture: true });
	  }

    onPresetChange();
    hookFormSubmit();
  }

  document.addEventListener('DOMContentLoaded', init);
})();

