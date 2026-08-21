// Alpine.js component
document.addEventListener('alpine:init', () => {
    Alpine.data('app', () => ({
        dark: false, // Initial dark mode state
        mm: false,   // Mobile menu state
        sc: false,   // Scrolled state
        s: '',       // Current section (for nav underline)
        
        init() {
            // Check system preference for dark mode
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                this.dark = true;
            }

            // Handle scroll events
            window.addEventListener('scroll', () => {
                this.sc = window.scrollY > 20;
                
                // Simple scrollspy
                const sections = document.querySelectorAll('section');
                let current = '';
                sections.forEach(sec => {
                    const sectionTop = sec.offsetTop;
                    if (scrollY >= sectionTop - 100) {
                        current = sec.getAttribute('id');
                    }
                });
                this.s = current;
            });
        }
    }))
})

// Reveal on scroll (Intersection Observer)
document.addEventListener("DOMContentLoaded", function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in');
            }
        });
    }, {
        threshold: 0.1
    });

    document.querySelectorAll('.reveal').forEach((el) => {
        observer.observe(el);
    });
});
