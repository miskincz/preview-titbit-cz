(function() {
  'use strict';
  
  document.addEventListener('DOMContentLoaded', function() {
    
    // Univerzální smooth scroll pro všechny hash linky
    function initSmoothScroll() {
      // Najít všechny odkazy začínající na #
      const hashLinks = document.querySelectorAll('a[href^="#"]');
      
      hashLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
          const targetHash = this.getAttribute('href');
          
          // Ignorovat prázdné nebo jen # odkazy
          if (!targetHash || targetHash === '#') return;
          
          e.preventDefault();
          
          const targetId = targetHash.substring(1);
          let targetElement = document.getElementById(targetId);
          
          // Pokud neexistuje přímý element, zkus najít tlačítko záložky
          if (!targetElement) {
            const tabButton = document.getElementById('tab-btn-' + targetId);
            if (tabButton) {
              // Aktivovat záložku
              tabButton.click();
              
              // Počkat a scrollnout k sekci
              setTimeout(function() {
                const section = document.querySelector('.produktDetail__sections');
                if (section) {
                  scrollToElement(section);
                }
              }, 200);
              return;
            }
          }
          
          // Standardní scroll k elementu
          if (targetElement) {
            scrollToElement(targetElement);
          }
        });
      });
    }
    
    // Univerzální scroll funkce
    function scrollToElement(element, offset) {
      if (!offset) offset = -100; // Defaultní offset
      
      const y = element.getBoundingClientRect().top + window.pageYOffset + offset;
      
      window.scrollTo({
        top: y,
        behavior: 'smooth'
      });
    }
    
    // Spustit při načtení
    initSmoothScroll();
    
    // Re-inicializovat při změnách v DOM (AJAX obsah apod.)
    const observer = new MutationObserver(function() {
      initSmoothScroll();
    });
    
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  });
})();
