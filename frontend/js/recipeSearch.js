document.querySelectorAll('.recipe-card').forEach(card => {
    card.addEventListener('click', () => {
      window.location.href = 'recipe.html';
    });
  });
  
    // Toggle star color
    document.querySelectorAll('.fa-star').forEach(star => {
      star.addEventListener('click', (event) => {
        event.stopPropagation(); // prevent card redirect
        star.classList.toggle('active');
      });
    });