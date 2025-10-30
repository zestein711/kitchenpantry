document.addEventListener("DOMContentLoaded", function() {
     // --- Star toggle ---
     const star = document.querySelector(".fa-star");
     if (star) {
         star.addEventListener("click", function() {
             star.classList.toggle("active");
         });
     }
 
     // --- Collapsible review section ---
     const collapseBtn = document.querySelector(".collapse-toggle");
     const reviewBox = document.getElementById("reviewBox");
 
     if (collapseBtn && reviewBox) {
         collapseBtn.addEventListener("click", () => {
             if (reviewBox.style.display === "none") {
                 reviewBox.style.display = "block";
                 collapseBtn.textContent = "▲";
             } else {
                 reviewBox.style.display = "none";
                 collapseBtn.textContent = "▼";
             }
         });
     }
 });
