(() => {
  document.querySelector('.staff-print')?.addEventListener('click', () => window.print());
  const search = document.querySelector('.staff-filters input[name="q"]');
  if (search) {
    const hint = document.createElement('p');
    hint.className = 'staff-shortcut-hint';
    hint.append('Tip: press ');
    const key = document.createElement('kbd');
    key.textContent = '/';
    hint.append(key, ' to focus search.');
    search.closest('.staff-filters').after(hint);
    document.addEventListener('keydown', event => {
      if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) return;
      if (event.target.closest('input, textarea, select, button, [contenteditable="true"]')) return;
      event.preventDefault();search.focus();
    });
  }
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  if (reduced.matches || !('IntersectionObserver' in window)) return;
  const observer = new IntersectionObserver(entries => entries.forEach(entry => {
    if (entry.isIntersecting) {entry.target.classList.add('visible');observer.unobserve(entry.target);}
  }), {threshold:0.05});
  const elements = document.querySelectorAll('.staff-stats,.operations-note,.dashboard-content>.panel');
  elements.forEach(element => {
    if (element.getBoundingClientRect().top < innerHeight) return;
    element.classList.add('staff-reveal');observer.observe(element);
  });
  document.addEventListener('focusin', event => event.target.closest?.('.staff-reveal')?.classList.add('visible'));
  reduced.addEventListener('change', event => {
    if (event.matches) {elements.forEach(element => element.classList.add('visible'));observer.disconnect();}
  });
})();
