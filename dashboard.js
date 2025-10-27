// Settings gear click handler
document.getElementById('settings-btn').addEventListener('click', () => {
    alert('Settings clicked! Implement settings logic here.');
});

// Profile button click handler (placeholder for dropdown or modal)
document.getElementById('profile-btn').addEventListener('click', () => {
    alert('Profile clicked! Add user info logic here.');
});

// Search button click handler
document.getElementById('search-btn').addEventListener('click', () => {
    const searchTerm = document.getElementById('search-input').value;
    alert(`Searching for: ${searchTerm}`);
    // Add logic to filter shelves or fetch data

});

  const selectedIngredients = new Set();

  // Make items clickable
  document.querySelectorAll('.food-item').forEach(item => {
    item.addEventListener('click', () => {
      const ingredient = item.dataset.ingredient;
      if (selectedIngredients.has(ingredient)) {
        selectedIngredients.delete(ingredient);
        item.classList.remove('selected');
      } else {
        selectedIngredients.add(ingredient);
        item.classList.add('selected');
      }
    });
  });
