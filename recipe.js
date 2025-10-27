// Collapsible review section
    const collapseBtn = document.querySelector(".collapse-toggle");
    const reviewBox = document.getElementById("reviewBox");

    collapseBtn.addEventListener("click", () => {
      if (reviewBox.style.display === "none") {
        reviewBox.style.display = "block";
        collapseBtn.textContent = "▲";
      } else {
        reviewBox.style.display = "none";
        collapseBtn.textContent = "▼";
      }
    });