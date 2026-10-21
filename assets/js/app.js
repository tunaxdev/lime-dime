function updateNavCount() {
  var cart  = JSON.parse(localStorage.getItem('ld_cart') || '{}');
  var count = Object.values(cart).reduce(function(s, n) { return s + n; }, 0);
  var el    = document.getElementById('nav-cart-count');
  if (!el) return;
  if (count > 0) {
    el.textContent    = count > 99 ? '99+' : count;
    el.style.display  = '';
  } else {
    el.style.display  = 'none';
  }
}

function toggleFav(pid) {
  var faves = JSON.parse(localStorage.getItem('ld_faves') || '[]');
  var idx   = faves.indexOf(pid);
  if (idx === -1) {
    faves.push(pid);
  } else {
    faves.splice(idx, 1);
  }
  localStorage.setItem('ld_faves', JSON.stringify(faves));
  document.querySelectorAll('[data-fav="' + pid + '"]').forEach(function(btn) {
    btn.classList.toggle('on', faves.indexOf(pid) !== -1);
  });
}

function isFaved(pid) {
  var faves = JSON.parse(localStorage.getItem('ld_faves') || '[]');
  return faves.indexOf(pid) !== -1;
}

function syncFavButtons() {
  var faves = JSON.parse(localStorage.getItem('ld_faves') || '[]');
  document.querySelectorAll('[data-fav]').forEach(function(btn) {
    var pid = btn.getAttribute('data-fav');
    btn.classList.toggle('on', faves.indexOf(pid) !== -1);
  });
}

function initBubbles() {
  var hero = document.querySelector('.hero-c-bubbles');
  if (!hero) return;
  var bubbles = hero.querySelectorAll('.bubble');
  bubbles.forEach(function(b, i) {
    b.style.animationDelay = (i * 0.4) + 's';
  });
}

document.addEventListener('DOMContentLoaded', function() {
  updateNavCount();
  syncFavButtons();
  initBubbles();
});
