document.querySelector("form").addEventListener("submit", function(e){
    e.preventDefault();

    //Obtaining password values
    const password = document.querySelector('input[placeholder="Password"]').value;
    const verifyPassword = document.querySelector('input[placeholder="Re-enter Password"]').value;

    //Check if they match
    if (password !== verifyPassword){
        alert("Passwords do not match, try again.");
        return; //Stop
    }

    window.location.href = "../../backend/php/login.php";
});