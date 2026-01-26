(function() {
  'use strict';
  
  document.addEventListener('DOMContentLoaded', function() {
    
    // Extrakcja prvního frame z videa pro náhled
    function generateVideoThumbnails() {
      const videoLinks = document.querySelectorAll('.gallery-video:not([data-thumb-generated])');
      
      console.log('Hledám videa bez náhledů, nalezeno:', videoLinks.length);
      
      videoLinks.forEach(function(link) {
        const videoUrl = link.getAttribute('href');
        const $placeholder = link.querySelector('.gallery-video__placeholder');
        
        console.log('Zpracovávám video:', videoUrl);
        
        // Přeskočit pokud už má obrázek
        if (link.querySelector('img.gallery-video__thumb')) {
          console.log('Má již náhled, přeskakovávám');
          link.setAttribute('data-thumb-generated', 'true');
          return;
        }
        
        // Vytvořit video element pro získání prvního framu
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.crossOrigin = 'anonymous';
        video.src = videoUrl;
        video.style.display = 'none';
        document.body.appendChild(video);
        
        let timeoutId = setTimeout(function() {
          console.warn('Timeout: video se nenačetlo, metadata event se neaktivoval');
          if (document.body.contains(video)) {
            document.body.removeChild(video);
          }
          link.setAttribute('data-thumb-generated', 'true');
        }, 5000);
        
        video.addEventListener('loadedmetadata', function() {
          clearTimeout(timeoutId);
          console.log('Metadata načtena, delka videa:', video.duration);
          // Nastavit čas na 1 sekundu (či 0.1s pokud je video kratší)
          video.currentTime = Math.min(1, video.duration * 0.1);
        });
        
        video.addEventListener('seeked', function() {
          console.log('Video seeknuto, vytvářím náhled');
          try {
            // Vytvořit canvas a nakreslit frame
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            
            // Převést canvas na obrázek
            const thumbUrl = canvas.toDataURL('image/jpeg', 0.7);
            
            // Vytvořit img element
            const img = document.createElement('img');
            img.src = thumbUrl;
            img.alt = 'Video náhled';
            img.className = 'gallery-video__thumb';
            
            // Nahradit placeholder
            if ($placeholder) {
              $placeholder.replaceWith(img);
            } else {
              link.insertBefore(img, link.firstChild);
            }
            
            console.log('Náhled vytvořen');
            link.setAttribute('data-thumb-generated', 'true');
          } catch(e) {
            console.warn('Nelze generovat náhled videa:', e);
          } finally {
            // Smazat video element
            if (document.body.contains(video)) {
              document.body.removeChild(video);
            }
          }
        });
        
        video.addEventListener('error', function(e) {
          clearTimeout(timeoutId);
          console.warn('Chyba při načítání videa:', videoUrl, e);
          if (document.body.contains(video)) {
            document.body.removeChild(video);
          }
          link.setAttribute('data-thumb-generated', 'true');
        });
      });
    }
    
    // Inicializace tooltipů z data atributu
    function initTooltips() {
      const elementsWithTooltip = document.querySelectorAll('[data-tooltip]:not([data-tooltip-initialized])');
      
      elementsWithTooltip.forEach(function(element) {
        const tooltipText = element.getAttribute('data-tooltip');
        if (!tooltipText) return;
        
        // Označit jako inicializovaný
        element.setAttribute('data-tooltip-initialized', 'true');
        
        // Vytvořit tooltip element
        const tooltip = document.createElement('span');
        tooltip.className = 'tooltip';
        tooltip.textContent = tooltipText;
        
        // Přidat tooltip do elementu
        element.appendChild(tooltip);
      });
    }
    
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
    
    // Spustit tooltips při načtení
    initTooltips();
    
    // Spustit při načtení
    initSmoothScroll();
    
    // Generování videa se spouští asynchronně aby neblokoval stránku
    requestAnimationFrame(function() {
      setTimeout(generateVideoThumbnails, 100);
    });
    
    // Re-inicializovat při změnách v DOM (AJAX obsah apod.)
    const observer = new MutationObserver(function() {
      // generateVideoThumbnails();
      initTooltips();
      initSmoothScroll();
    });
    
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  });
})();

