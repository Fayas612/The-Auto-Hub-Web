(() => {
  const list = document.querySelector('.appointment-list');
  if (list) {
    const filters = document.createElement('div');
    filters.className = 'appointment-filters';
    filters.setAttribute('role', 'group');
    filters.setAttribute('aria-label', 'Filter your appointments');
    const empty = document.createElement('p');
    empty.className = 'filtered-empty';
    empty.textContent = 'No appointments with this status yet.';
    empty.hidden = true;
    empty.setAttribute('role', 'status');
    list.after(empty);
    const buttons = [];
    const cards = [...list.querySelectorAll('.appointment-item')];
    for (const [status, name] of Object.entries({all:'All requests',pending:'Pending',confirmed:'Confirmed',completed:'Completed',cancelled:'Cancelled'})) {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = name;
      button.setAttribute('aria-pressed', String(status === 'all'));
      button.addEventListener('click', () => {
        let shown = 0;
        cards.forEach(card => {
          card.hidden = status !== 'all' && !card.querySelector(`.status-${status}`);
          if (!card.hidden) shown++;
        });
        empty.hidden = shown > 0;
        buttons.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
      });
      buttons.push(button);
      filters.append(button);
    }
    list.before(filters);
  }
  if (matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
  const observer = new IntersectionObserver(entries => entries.forEach(entry => {
    if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
  }), {threshold:0.05});
  document.querySelectorAll('.customer-stats,.dashboard-grid,.appointments-panel,.dashboard-services,.care-note').forEach(element => {
    if (element.getBoundingClientRect().top < innerHeight) return;
    element.classList.add('customer-reveal');
    observer.observe(element);
  });
  document.addEventListener('focusin', event => event.target.closest?.('.customer-reveal')?.classList.add('is-visible'));
})();
