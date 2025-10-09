
document.getElementById("forgotForm").addEventListener("submit", function(e){
    e.preventDefault(); //Prevent Page from reloading

    //Email request from the backend should be sent here

    //Verification Screen is now shown
    const modal = new bootstrap.Modal(document.getElementById("codeModal"));
    modal.show();
});

document.getElementById("verifyBtn").addEventListener("click",function(){
    const code = document.getElementById("verificationCode").value;
    alert("Code entered " + code);
    //Send code to server to verify...

    // console.log("Verifying code:", code);

    window.location.href = "/html/resetPassword.html";
});