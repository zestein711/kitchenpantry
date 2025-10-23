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