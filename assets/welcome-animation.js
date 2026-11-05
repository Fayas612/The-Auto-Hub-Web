(() => {
  const hero = document.querySelector('.hero');
  if (!hero || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'hero-motion-button';
  button.textContent = 'Ⅱ Pause animation';
  button.setAttribute('aria-pressed', 'false');
  button.addEventListener('click', () => {
    const paused = hero.classList.toggle('motion-paused');
    button.textContent = paused ? '▷ Play animation' : 'Ⅱ Pause animation';
    button.setAttribute('aria-pressed', String(paused));
  });
  hero.append(button);
})();
