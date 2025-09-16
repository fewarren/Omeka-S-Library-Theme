(function(){
  'use strict';
  // Only run when ?debug=captions is present in the URL
  try {
    var params = new URLSearchParams(window.location.search);
    var dbg = (params.get('debug') || '').toLowerCase();
    if (dbg.indexOf('captions') === -1) return;
  } catch (e) { return; }

  function toRGBA(str){
    if (!str) return 'none';
    return str.trim();
  }

  function logCaption(el, idx){
    var cs = getComputedStyle(el);
    var bg = toRGBA(cs.backgroundColor);
    var border = cs.border;
    var shadow = cs.boxShadow;
    console.log('[caption #' + idx + ']', el, { backgroundColor: bg, border: border, boxShadow: shadow });

    // Walk up a few ancestors to see if background is inherited from container
    var parent = el.parentElement, hop = 0;
    while (parent && hop < 4) {
      var pcs = getComputedStyle(parent);
      var pbg = toRGBA(pcs.backgroundColor);
      if (pbg && pbg !== 'rgba(0, 0, 0, 0)' && pbg !== 'transparent') {
        console.log('  ↳ parent[' + hop + ']:', parent, { backgroundColor: pbg });
      }
      parent = parent.parentElement;
      hop++;
    }
  }

  function run(){
    var selectors = [
      '.block.block-asset .caption',
      '.assets .asset .caption',
      'figure figcaption',
      '.media .caption',
      '.media-render .caption',
      '.block.block-videoThumbnail .caption',
      '.block.block-videoThumbnail figcaption',
      '.video-thumbnails .video-thumbnail-item [class*="caption"]'
    ].join(',');

    var nodes = document.querySelectorAll(selectors);
    console.groupCollapsed('[LibraryTheme] Caption Debug: found ' + nodes.length + ' node(s)');
    nodes.forEach(function(el, i){
      try { el.style.outline = '2px dashed magenta'; } catch(_){ }
      logCaption(el, i);
    });
    console.groupEnd();

    // If none found, inspect the video thumbnail items themselves
    if (!nodes.length) {
      var items = document.querySelectorAll('.video-thumbnails .video-thumbnail-item, [data-template="grid"] .video-thumbnail-item');
      console.group('[LibraryTheme] Video Thumbnail Items: ' + items.length + ' found');
      items.forEach(function(it, i){
        try { it.style.outline = '2px dashed orange'; } catch(_){ }
        var cs = getComputedStyle(it);
        console.log('[item #' + i + ']', it, { backgroundColor: toRGBA(cs.backgroundColor) });
        var captionLike = it.querySelectorAll('[class*="caption"], figcaption, .caption');
        console.log('  ↳ caption-like descendants:', captionLike.length);
        captionLike.forEach(function(cn, j){
          try { cn.style.outline = '2px dotted red'; } catch(_){ }
          logCaption(cn, i + ':' + j);
        });
      });
      console.groupEnd();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();

