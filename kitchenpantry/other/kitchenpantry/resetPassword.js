document.querySelector("form").addEventListener("submit", function(e){
    //e.preventDefault();

    //Obtaining password values
    const password = document.querySelector('input[placeholder="New Password"]').value;
    const verifyPassword = document.querySelector('input[placeholder="Verify Password"]').value;

    //Check if they match
    if (password !== verifyPassword){
        alert("Passwords do not match, try again.");
        return; //Stop
    }

    alert("Password successfully changed!")

    window.location.href = "/kitchenpantry/login.html";
})
