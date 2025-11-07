// Javascript for User Profile
const recipeGrid = document.getElementById("recipeGrid");
const viewAllBtn = document.getElementById("editBtn");
let editMode = false;

// Toggle "edit mode" simulate removing saved recipes:
editBtn.addEventListener("click", () =>{
    editMode = !editMode;
    editBtn.textContent = editMode ? "Done" : "Edit";
    toggleEditMode(editMode);
});

recipeGrid.addEventListener("click", (e) => {
    const deleteBtn = e.target.closest(".delete-btn");
    const box = e.target.closest(".recipe-box");
  
    // If clicked outside any recipe box, ignore
    if (!box) return;
  
    // If clicking delete button
    if (deleteBtn) {
      e.stopPropagation(); // prevent recipe navigation
      box.remove(); // delete from DOM (or send AJAX)
      return;
    }
    // Otherwise, it's a normal recipe click
    if (editMode) return; // disable navigation in edit mode
  
    // Navigate to recipe
    const recipeId = box.dataset.recipeId;
    window.location.href = "../../backend/php/recipe.php";
  });

//When user triggers the event
function toggleEditMode(isEditing){
    const box = recipeGrid.querySelectorAll(".recipe-box");
    box.forEach(box => {
        //Remove exisiting delete button if toggling off
        const exisitingX= box.querySelector(".delete-btn");
        if(exisitingX) exisitingX.remove();

        if(isEditing){
            const deleteBtn = document.createElement("button");
            deleteBtn.classList.add(
                //Boostrap in Javascript
                "btn", "btn-sm", "btn-danger", "delete-btn",
                "position-absolute", "top-0", "end-0", "m-1", "rounded-circle"
            );
            deleteBtn.innerHTML = "&times;";
            deleteBtn.onclick = () => box.remove();
            box.appendChild(deleteBtn);
        }
    });

}
