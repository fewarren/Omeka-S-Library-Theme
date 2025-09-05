(function(){
/* Live updates: reload iframe when inputs change */

  function onReady(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
  function getSiteFrontUrl(){
    var p = window.location.pathname;
    // Expect admin path like /admin/site/{slug}/theme or similar
    var m = p.match(/\/admin\/site\/([^\/]+)/);
    if(m && m[1]){
      return window.location.origin + '/s/' + m[1];
    }
    return window.location.origin + '/';
  }
  onReady(function(){
    var isAdmin = window.location.pathname.indexOf('/admin') !== -1;
    var onThemePage = /\/admin\/site\/[^\/]+\/theme/.test(window.location.pathname) || /\/admin\/site\/[^\/]+\/setting/.test(window.location.pathname);
    if(!isAdmin || !onThemePage) return;

    var select = document.querySelector('#style_preset, [name="style_preset"]');
    // Place preview near the preset field or at top of the form
    var insertAfter = select ? (select.closest('.field') || select.closest('div') || select) : null;
    var container = document.createElement('div');
    container.className = 'admin-inline-preview';
    container.innerHTML = '<div class="admin-inline-preview__head">Live preset preview</div>' +
      '<div class="admin-inline-preview__body">' +
      '  <iframe class="admin-inline-preview__frame" src="' + getSiteFrontUrl() + '?presetPreview=1" frameborder="0" loading="lazy"></iframe>' +
      '</div>';

    if(insertAfter && insertAfter.parentNode){
      insertAfter.parentNode.insertBefore(container, insertAfter.nextSibling);
    } else {
      var form = document.querySelector('form');
      if(form){ form.insertBefore(container, form.firstChild); }
      else { document.body.appendChild(container); }
    }

    var frame = container.querySelector('.admin-inline-preview__frame');
    function buildOverrideQS(){
      var params = new URLSearchParams();
      // map of field name => query key in preview
      var map = {
        'primary_color': 'override_primary',
        'sacred_gold': 'override_gold',
        'body_font_color': 'override_body_color',
        'h1_font_family': 'override_h1_font',
        'body_font_family': 'override_body_font'
      };
      Object.keys(map).forEach(function(name){
        var el = document.querySelector('[name="'+name+'"]');
        if(!el) return;
        var val = (el.type==='color') ? el.value : (el.value || '');
        if(val){ 
          // Only add '#' prefix for color fields, pass other values as-is
          var isColorField = el.type === 'color' || name.includes('_color') || name === 'primary_color' || name === 'sacred_gold';
          var processedVal = isColorField ? val.replace(/^#?/, '#') : val.trim();
          params.set(map[name], processedVal);
        }
      });
      return params.toString();
    }
    function reloadPreview(){
      var base = getSiteFrontUrl() + '?presetPreview=1';
      var qs = buildOverrideQS();
      frame.src = qs ? (base + '&' + qs) : base;
    }

    // Live update on change for relevant controls
    ['primary_color','sacred_gold','body_font_color','h1_font_family','body_font_family'].forEach(function(name){
      var el = document.querySelector('[name="'+name+'"]');
      if(el){ el.addEventListener('input', reloadPreview); el.addEventListener('change', reloadPreview); }
    });

    // Optional: reload preview after saving theme settings (detect submit)
    var formEl = document.querySelector('form');
    if(formEl){
      formEl.addEventListener('submit', function(){
        // After save, page reload will refresh iframe. No action here.
      });
    }
  });
})();

