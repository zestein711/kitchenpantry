document.getElementById("loginForm").addEventListener("submit", function(e){
    e.preventDefault();
    //This js file does not handle the backend logic to check if the user
    //exists, the login button just sends you to your personal dashboard

    
    window.location.href = "dashboard.html";
});