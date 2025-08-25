(function(){
  'use strict';
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }

  function gatherSettings(){
    const data = {};
    qsa('[name]').forEach(el => {
      if (!el.name) return;
      let key = el.name;
      // normalize settings[n] -> n
      const m = key.match(/\[(.+?)\]$/);
      if (m) key = m[1];
      if (el.type === 'checkbox') data[key] = el.checked ? 1 : 0;
      else data[key] = el.value;
    });
    return data;
  }

  function injectDebug(anchor){
    let bar = document.getElementById('admin-debug-bar');
    if (bar) return;
    bar = document.createElement('div');
    bar.id = 'admin-debug-bar';
    bar.style.cssText = 'margin:10px 0; display:flex; gap:8px; align-items:center; flex-wrap:wrap;';

    const btnDump = document.createElement('button');
    btnDump.type='button'; btnDump.textContent='Debug: Show current form values';
    btnDump.onclick = function(){
      const data = gatherSettings();
      const pre = document.createElement('pre');
      pre.style.cssText = 'background:#111; color:#0f0; padding:10px; overflow:auto; max-height:300px;';
      pre.textContent = JSON.stringify(data, null, 2);
      bar.appendChild(pre);
      console.log('[Admin Debug] Current form values:', data);
    };

    const btnCheckPreset = document.createElement('button');
    btnCheckPreset.type='button'; btnCheckPreset.textContent='Debug: Check preset wiring';
    btnCheckPreset.onclick = function(){
      const hasPresetLoaded = !!window.__adminPresetLoaded;
      const apply = document.querySelector('[name="apply_preset_now"], [name$="[apply_preset_now]"]');
      const preset = document.querySelector('[name="style_preset"], [name$="[style_preset]"]');
      const msg = {
        hasPresetLoaded,
        applyFound: !!apply,
        presetFound: !!preset,
        presetValue: preset ? preset.value : null
      };
      alert('Preset wiring: ' + JSON.stringify(msg, null, 2));
      console.log('[Admin Debug] Preset wiring:', msg);
    };

    bar.appendChild(btnDump);
    bar.appendChild(btnCheckPreset);
    anchor && anchor.appendChild(bar);
  }

  function init(){
    if (!location.pathname.includes('/admin')) return;
    const presetSel = document.querySelector('[name="style_preset"], [name$="[style_preset]"]');
    if (!presetSel) return;
    const anchor = presetSel.closest('div,fieldset,section,form') || document.body;
    injectDebug(anchor);
  }
  document.addEventListener('DOMContentLoaded', init);
})();

