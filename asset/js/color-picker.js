// Minimal, dependency-free color picker enhancer for Laminas Color inputs
// Attaches to inputs[type=color] and paired hex inputs to provide a swatch and preset palette.
(function(){
  'use strict';

  function initColorPicker(input){
    if (!input || input.dataset.colorPickerAttached) return;
    input.dataset.colorPickerAttached = '1';

    // Create wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'color-picker-wrapper';

    // Create swatch
    const swatch = document.createElement('div');
    swatch.className = 'color-swatch';
    swatch.style.background = input.value || '#000000';

    // Create text input for hex value (sync both directions)
    const text = document.createElement('input');
    text.className = 'color-input';
    text.type = 'text';
    text.value = input.value || '#000000';
    text.placeholder = '#RRGGBB';

    // Create quick palette with brand colors + common neutrals
    const palette = document.createElement('div');
    palette.className = 'color-palette';
    const swatches = [
      '#2C4A6B','#D4AF37','#1a365d','#4a6fa5','#ffffff','#000000',
      '#2c5aa0','#f7c97f','#4a5568','#e2e8f0','#edf2f7','#f7fafc'
    ];
    swatches.forEach((c)=>{
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'color-swatch-btn';
      b.style.background = c;
      b.title = c;
      b.addEventListener('click', ()=>{
        input.value = c;
        text.value = c;
        swatch.style.background = c;
        input.dispatchEvent(new Event('input', {bubbles:true}));
        input.dispatchEvent(new Event('change', {bubbles:true}));
      });
      palette.appendChild(b);
    });

    // Insert wrapper before input and move input inside
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(swatch);
    wrapper.appendChild(text);
    wrapper.appendChild(input);
    wrapper.appendChild(palette);

    // Sync changes from native color input -> text + swatch
    input.addEventListener('input', ()=>{
      const v = input.value;
      if (v && /^#([0-9a-fA-F]{3}){1,2}$/.test(v)) {
        text.value = v;
        swatch.style.background = v;
      }
    });

    // Sync changes from text -> native color input + swatch
    text.addEventListener('input', ()=>{
      const v = text.value.trim();
      if (/^#([0-9a-fA-F]{3}){1,2}$/.test(v)) {
        input.value = v;
        swatch.style.background = v;
      }
    });
  }

  function enhanceAll(){
    // Limit to admin page context only
    if (!location.pathname.includes('/admin')) return;
    // Find Laminas color elements
    const inputs = document.querySelectorAll('input[type="color"]');
    inputs.forEach(initColorPicker);
  }

  document.addEventListener('DOMContentLoaded', enhanceAll);
  document.addEventListener('omeka:form-updated', enhanceAll);
})();

