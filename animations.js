document.addEventListener("DOMContentLoaded", function () {
    // Nastavení Intersection Observeru
    const observerOptions = {
        root: null, // Sledujeme viewport
        rootMargin: '0px',
        threshold: 0.1 // Spustí se, když je 10% prvku vidět
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Přidáme třídu, která spustí CSS animaci
                entry.target.classList.add('visible');
                
                // Přestaneme tento prvek sledovat (animace proběhne jen jednou)
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // 1. Najdeme všechny prvky s třídou .reveal a začneme je sledovat
    const revealElements = document.querySelectorAll('.reveal');
    revealElements.forEach(el => observer.observe(el));

    // 2. Speciální logika pro galerii (postupné načítání - stagger efekt)
    const galleryGrids = document.querySelectorAll('.gallery-grid');
    
    galleryGrids.forEach(grid => {
        const images = grid.querySelectorAll('img, .gallery-item');
        images.forEach((img, index) => {
            // Přidáme třídu reveal každému obrázku
            img.classList.add('reveal');
            
            // Nastavíme zpoždění podle pořadí (indexu)
            // Každý další obrázek se opozdí o 100ms
            img.style.transitionDelay = `${index * 100}ms`;
            
            observer.observe(img);
        });
    });
});