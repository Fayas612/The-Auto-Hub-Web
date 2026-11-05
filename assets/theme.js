(() => {
  const key = 'auto-hub-theme';
  let selected = 'light';
  try { selected = localStorage.getItem(key) === 'dark' ? 'dark' : 'light'; } catch {}
  const apply = value => {
    selected = value;
    document.documentElement.dataset.theme = value;
    document.documentElement.style.colorScheme = value;
  };
  apply(selected);
  document.addEventListener('DOMContentLoaded', () => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'theme-toggle';
    const sync = () => {
      const dark = selected === 'dark';
      button.textContent = dark ? '☀ Light mode' : '☾ Dark mode';
      button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
      button.setAttribute('aria-pressed', String(dark));
      button.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
    };
    button.addEventListener('click', () => {
      apply(selected === 'dark' ? 'light' : 'dark');
      try { localStorage.setItem(key, selected); } catch {}
      sync();
    });
    const navbar = document.querySelector('header.nav');
    if (navbar) {
      const actions = document.createElement('div');
      actions.className = 'nav-actions';
      const booking = navbar.querySelector(':scope > .button');
      actions.append(button);
      if (booking) actions.append(booking);
      navbar.append(actions);
    } else {
      const host = document.querySelector('.auth-nav, .dashboard-header, .staff-login');
      if (host) host.append(button);
    }
    sync();
    const sectionLinks = [...document.querySelectorAll('header.nav nav a[href^="#"]')];
    sectionLinks.forEach(link => {
      link.addEventListener('click', event => {
        const target = document.getElementById(link.hash.slice(1));
        if (!target) return;
        event.preventDefault();
        target.querySelectorAll('.scroll-reveal').forEach(element => element.classList.add('scroll-visible'));
        if (location.hash !== link.hash) history.pushState(null, '', link.hash);
        target.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' });
        updateNavigation();
      });
    });
    const updateNavigation = () => {
      sectionLinks.forEach(link => {
        if (link.hash === location.hash) link.setAttribute('aria-current', 'location');
        else link.removeAttribute('aria-current');
      });
    };
    window.addEventListener('hashchange', updateNavigation);
    updateNavigation();
    window.addEventListener('storage', event => {
      if (event.key === key) { apply(event.newValue === 'dark' ? 'dark' : 'light'); sync(); }
    });
  });
})();
