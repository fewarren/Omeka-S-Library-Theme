(function(){
  'use strict';

  function hasDebugFlag(){
    try { return /(?:^|[?&])debug=([^&]+)/i.test(location.search) && /captions|dev/i.test(RegExp.$1); } catch(e){ return false; }
  }
  var DBG = hasDebugFlag();
  function log(){ if (DBG) try { console.log.apply(console, arguments); } catch(_){} }

  function run(){
    try {
      var items = document.querySelectorAll('.video-thumbnails .video-thumbnail-item, [data-template="grid"] .video-thumbnail-item');
      if (items.length) log('[caption-fix] Found video thumbnail items:', items.length);
      items.forEach(function(it){
        try { it.style.setProperty('background-color', '#ffffff', 'important'); } catch(_){ }
      });

      var caps = document.querySelectorAll([
        '.video-thumbnails .video-thumbnail-item [class*="caption"]',
        '.block.block-videoThumbnail .caption',
        'figure figcaption',
        '.media .caption',
        '.media-render .caption'
      ].join(','));
      if (caps.length) log('[caption-fix] Found caption-like nodes:', caps.length);
      caps.forEach(function(c){
        try {
          c.style.setProperty('background-color', '#ffffff', 'important');
          c.style.setProperty('border', 'none', 'important');
          c.style.setProperty('box-shadow', 'none', 'important');
          c.style.setProperty('padding', '0.25rem 0', 'important');
        } catch(_){ }
      });
    } catch(e){ log('[caption-fix] error:', e); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();

