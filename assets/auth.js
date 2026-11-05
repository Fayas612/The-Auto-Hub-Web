const visibilityButton = document.querySelector('.password-visibility');
visibilityButton?.addEventListener('click', () => {
  const show = visibilityButton.getAttribute('aria-pressed') !== 'true';
  document.querySelectorAll('input[name="password"], input[name="confirm_password"]').forEach(input => {
    input.type = show ? 'text' : 'password';
  });
  visibilityButton.setAttribute('aria-pressed', String(show));
  visibilityButton.textContent = show ? 'Hide passwords' : 'Show passwords';
});
