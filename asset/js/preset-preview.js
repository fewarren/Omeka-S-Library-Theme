(function(){
  'use strict';
  
  function onReady(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
  
  // Track active overlay to prevent multiple instances
  let activeOverlay = null;
  
  function createModal() {
    // Prevent multiple overlays
    if (activeOverlay) {
      return;
    }
    
    // Safe URL construction using URL API
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('presetPreview', '1');
    const safePreviewUrl = currentUrl.toString();
    
    // Create overlay with proper DOM methods (no innerHTML)
    const overlay = document.createElement('div');
    overlay.className = 'preset-preview-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', 'preset-modal-title');
    
    // Create modal container
    const modal = document.createElement('div');
    modal.className = 'preset-preview-modal';
    
    // Create close button with proper accessibility
    const closeButton = document.createElement('button');
    closeButton.className = 'close';
    closeButton.setAttribute('type', 'button');
    closeButton.setAttribute('aria-label', 'Close preset preview');
    closeButton.textContent = '×';
    
    // Create hidden title for screen readers
    const title = document.createElement('h2');
    title.id = 'preset-modal-title';
    title.className = 'sr-only';
    title.textContent = 'Preset Preview';
    title.style.cssText = 'position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;';
    
    // Create iframe with safe URL
    const iframe = document.createElement('iframe');
    iframe.className = 'preset-preview-frame';
    iframe.setAttribute('src', safePreviewUrl);
    iframe.setAttribute('frameborder', '0');
    iframe.setAttribute('title', 'Preset preview');
    
    // Assemble modal
    modal.appendChild(title);
    modal.appendChild(closeButton);
    modal.appendChild(iframe);
    overlay.appendChild(modal);
    
    // Close modal function
    function closeModal() {
      if (activeOverlay) {
        document.body.removeChild(activeOverlay);
        activeOverlay = null;
        // Restore focus to trigger element if possible
        const lastTrigger = document.querySelector('[data-action="open-preset-preview"]:focus');
        if (lastTrigger) {
          lastTrigger.focus();
        }
      }
    }
    
    // Event listeners
    closeButton.addEventListener('click', closeModal);
    
    // Close on overlay click (but not modal content)
    overlay.addEventListener('click', function(ev) {
      if (ev.target === overlay) {
        closeModal();
      }
    });
    
    // Close on Escape key
    function handleKeydown(ev) {
      if (ev.key === 'Escape') {
        ev.preventDefault();
        closeModal();
        document.removeEventListener('keydown', handleKeydown);
      }
    }
    document.addEventListener('keydown', handleKeydown);
    
    // Add to DOM and track
    document.body.appendChild(overlay);
    activeOverlay = overlay;
    
    // Focus management - focus the close button initially
    setTimeout(() => {
      closeButton.focus();
    }, 100);
  }
  
  onReady(function(){
    // Support multiple triggers with the same data attribute
    const triggers = document.querySelectorAll('[data-action="open-preset-preview"]');
    
    triggers.forEach(function(trigger) {
      trigger.addEventListener('click', function(e) {
        e.preventDefault();
        createModal();
      });
    });
  });
})();

