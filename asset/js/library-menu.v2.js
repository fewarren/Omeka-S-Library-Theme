(function($){
  'use strict';
  $(function(){
    // Safe guards against duplicate execution
    if (window.__libraryMenuV2Loaded) return; window.__libraryMenuV2Loaded = true;

    // Version banner so we can see this file is live
    try{ console.log('[library-menu.v2] LIVE at', new Date().toISOString()); }catch(e){}

    // Click behavior moved from logo to tagline; ensure logo is not hijacked by JS
    (function disableLogoClick(){
      try{
        const logo = document.querySelector('header img.site-logo');
        if(logo){
          logo.onclick = null;
          logo.style.pointerEvents = 'none';
          console.log('[library-menu.v2] logo click disabled');
        }
      }catch(e){ console.warn('[library-menu.v2] disableLogoClick warn:', e); }
    })();

    function applyLinkStyling(){
      try{
        const allLinks = document.querySelectorAll('main a');
        let contentFixed = 0, paginationFixed = 0, skipped = 0;
        const rootStyles = getComputedStyle(document.documentElement);
        allLinks.forEach(function(link){
          // Skip links that have already been styled
          if(link.dataset.styled === 'true'){
            skipped++;
            return;
          }
          
          const text = (link.textContent||'').trim();
          if(text === 'Next' || text === 'Prev'){
            const paginationBg = (rootStyles.getPropertyValue('--pagination-background-color')||'').trim() || '#2c5aa0';
            const paginationColor = (rootStyles.getPropertyValue('--pagination-font-color')||'').trim() || '#ffffff';
            const paginationPadding = (rootStyles.getPropertyValue('--pagination-button-padding')||'').trim() || '12px 24px';
            const paginationFontSize = (rootStyles.getPropertyValue('--pagination-button-font-size')||'').trim() || '16px';
            link.style.setProperty('background-color', paginationBg, 'important');
            link.style.setProperty('background', paginationBg, 'important');
            link.style.setProperty('color', paginationColor, 'important');
            link.style.setProperty('padding', paginationPadding, 'important');
            link.style.setProperty('font-size', paginationFontSize, 'important');
            link.style.setProperty('border-radius', '4px', 'important');
            link.style.setProperty('text-decoration', 'none', 'important');
            link.style.setProperty('border', 'none', 'important');
            link.style.setProperty('display', 'inline-block', 'important');
            link.style.setProperty('margin', '0 8px', 'important');
            link.style.setProperty('box-shadow', 'none', 'important');
            
            // Only attach event listeners if not already attached
            const paginationHover = (rootStyles.getPropertyValue('--pagination-hover-color')||'').trim() || '#1a365d';
            const handlePaginationEnter = function(){
              const currentRootStyles = getComputedStyle(document.documentElement);
              const currentHoverColor = (currentRootStyles.getPropertyValue('--pagination-hover-color')||'').trim() || '#1a365d';
              this.style.setProperty('background-color', currentHoverColor, 'important');
              this.style.setProperty('background', currentHoverColor, 'important');
            };
            const handlePaginationLeave = function(){
              const currentRootStyles = getComputedStyle(document.documentElement);
              const currentBgColor = (currentRootStyles.getPropertyValue('--pagination-background-color')||'').trim() || '#2c5aa0';
              this.style.setProperty('background-color', currentBgColor, 'important');
              this.style.setProperty('background', currentBgColor, 'important');
            };
            
            link.addEventListener('mouseenter', handlePaginationEnter);
            link.addEventListener('mouseleave', handlePaginationLeave);
            paginationFixed++;
          } else {
            const tocColor = (rootStyles.getPropertyValue('--toc-text-color')||'').trim() || '#2c4a6b';
            link.style.setProperty('color', tocColor, 'important');
            link.style.setProperty('background-color', 'transparent', 'important');
            link.style.setProperty('background', 'none', 'important');
            link.style.setProperty('text-decoration', 'none', 'important');
            link.style.setProperty('display', 'block', 'important');
            link.style.setProperty('padding', '8px 12px', 'important');
            link.style.setProperty('margin', '4px 0', 'important');
            link.style.setProperty('min-height', '20px', 'important');
            link.style.setProperty('line-height', '1.4', 'important');
            link.style.setProperty('font-size', '16px', 'important');
            link.style.setProperty('border', 'none', 'important');
            link.style.setProperty('box-shadow', 'none', 'important');
            
            // Only attach event listeners if not already attached
            const handleContentEnter = function(){
              const currentRootStyles = getComputedStyle(document.documentElement);
              const currentHoverColor = (currentRootStyles.getPropertyValue('--toc-hover-color')||'').trim() || '#d4af37';
              this.style.setProperty('color', currentHoverColor, 'important');
            };
            const handleContentLeave = function(){
              const currentRootStyles = getComputedStyle(document.documentElement);
              const currentColor = (currentRootStyles.getPropertyValue('--toc-text-color')||'').trim() || '#2c4a6b';
              this.style.setProperty('color', currentColor, 'important');
            };
            
            link.addEventListener('mouseenter', handleContentEnter);
            link.addEventListener('mouseleave', handleContentLeave);
            contentFixed++;
          }
          
          // Mark this link as styled to prevent re-processing
          link.dataset.styled = 'true';
        });
        console.log(`[library-menu.v2] Styled ${contentFixed} content links and ${paginationFixed} pagination buttons (${skipped} already styled)`);
      }catch(e){ console.error('[library-menu.v2] applyLinkStyling error:', e); }
    }

    // Run styling once DOM is ready and again after a short delay
    applyLinkStyling();
    setTimeout(applyLinkStyling, 500);
    setTimeout(applyLinkStyling, 1000);
  });
})(jQuery);

