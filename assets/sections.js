(() => {
  const select = document.querySelector('#priority');
  if (!select) return;
  const group = document.createElement('div');
  group.className = 'finish-choices';
  group.setAttribute('role', 'group');
  group.setAttribute('aria-label', 'Choose your vehicle care priority');
  const icons = { windscreen: '◈', nano: '✧', headlights: '☼' };
  const buttons = [];
  for (const option of select.options) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'finish-choice';
    const icon = document.createElement('span');
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = icons[option.value] || '✧';
    const label = document.createElement('span');
    label.textContent = option.textContent;
    const indicator = document.createElement('span');
    indicator.setAttribute('aria-hidden', 'true');
    button.append(icon, label, indicator);
    button.setAttribute('aria-pressed', String(select.value === option.value));
    button.addEventListener('click', () => {
      select.value = option.value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      buttons.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    });
    buttons.push(button);
    group.append(button);
  }
  select.insertAdjacentElement('afterend', group);
  select.hidden = true;
  // Keep the visible heading without pointing to the now-hidden fallback select.
  const heading = document.querySelector('label[for="priority"]');
  if (heading) heading.removeAttribute('for');
  document.querySelector('#recommend')?.setAttribute('aria-live', 'polite');
  document.querySelector('.recommend')?.setAttribute('aria-live', 'polite');
})();
