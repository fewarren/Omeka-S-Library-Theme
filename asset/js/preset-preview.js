(function(){
  function onReady(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
  onReady(function(){
    var btn = document.querySelector('[data-action="open-preset-preview"]');
    if(!btn) return;
    btn.addEventListener('click', function(e){
      e.preventDefault();
      var overlay = document.createElement('div');
      overlay.className = 'preset-preview-overlay';
      overlay.innerHTML = '<div class="preset-preview-modal">\n  <button class="close" aria-label="Close">×</button>\n  <iframe src="'+ window.location.pathname +'?presetPreview=1" frameborder="0" class="preset-preview-frame"></iframe>\n</div>';
      document.body.appendChild(overlay);
      overlay.querySelector('.close').addEventListener('click', function(){ overlay.remove(); });
      overlay.addEventListener('click', function(ev){ if(ev.target===overlay){ overlay.remove(); } });
    });
  });
})();

