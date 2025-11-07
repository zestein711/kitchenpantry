document.querySelectorAll('.recipe-card').forEach(card => {
    card.addEventListener('click', () => {
      window.location.href = '../../backend/php/recipe.js';
    });
  });
  
    // Toggle star color
    document.querySelectorAll('.fa-star').forEach(star => {
      star.addEventListener('click', (event) => {
        event.stopPropagation(); // prevent card redirect
        star.classList.toggle('active');
      });
    });
