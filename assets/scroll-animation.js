(() => {
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  if (reducedMotion.matches || !('IntersectionObserver' in window)) return;
  const elements = document.querySelectorAll(
    '#services .section-heading, #services .service-card, #approach .section-heading, #approach .steps article, #approach .process-outcome, #match > div, #booking .booking-grid > div, #booking #booking-form'
  );
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('scroll-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08 });
  elements.forEach(element => {
    // Leave the current viewport visible when arriving via an anchor link.
    if (element.getBoundingClientRect().top < innerHeight) return;
    element.classList.add('scroll-reveal');
    if (element.matches('.service-card, .steps article')) {
      const index = [...element.parentElement.children].indexOf(element);
      element.style.setProperty('--reveal-delay', `${index * 85}ms`);
    }
    observer.observe(element);
  });
  // Keyboard navigation must never land on visually hidden content.
  document.addEventListener('focusin', event => {
    event.target.closest?.('.scroll-reveal')?.classList.add('scroll-visible');
  });
  reducedMotion.addEventListener('change', event => {
    if (event.matches) {
      elements.forEach(element => element.classList.add('scroll-visible'));
      observer.disconnect();
    }
  });
})();
