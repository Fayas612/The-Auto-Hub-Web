(() => {
  const select = document.querySelector('#reviews #review-rating');
  if (select) {
    const stars = document.createElement('div');
    stars.className = 'review-rating-picker';
    stars.setAttribute('role', 'group');
    stars.setAttribute('aria-label', 'Choose your review rating');
    const buttons = [];
    const sync = () => buttons.forEach((button, index) => {
      button.classList.toggle('filled', index < Number(select.value));
      button.setAttribute('aria-pressed', String(index + 1 === Number(select.value)));
    });
    for (let i = 1; i <= 5; i++) {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = '★';
      button.setAttribute('aria-label', `${i} ${i === 1 ? 'star' : 'stars'}`);
      button.addEventListener('click', () => {select.value = String(i); sync();});
      buttons.push(button);
      stars.append(button);
    }
    select.after(stars);
    select.hidden = true;
    document.querySelector('label[for="review-rating"]')?.removeAttribute('for');
    sync();
  }
  document.querySelectorAll('#reviews .review-card .review-comment').forEach((comment, index) => {
    if (comment.textContent.length < 350) return;
    comment.id = `public-review-comment-${index}`;
    comment.classList.add('review-collapsed');
    const button = document.createElement('button');
    button.className = 'review-read-more';
    button.type = 'button';
    button.textContent = 'Read full review';
    button.setAttribute('aria-controls', comment.id);
    button.setAttribute('aria-expanded', 'false');
    button.addEventListener('click', () => {
      const expanded = button.getAttribute('aria-expanded') !== 'true';
      button.setAttribute('aria-expanded', String(expanded));
      comment.classList.toggle('review-collapsed', !expanded);
      button.textContent = expanded ? 'Show less' : 'Read full review';
    });
    comment.after(button);
  });
})();
