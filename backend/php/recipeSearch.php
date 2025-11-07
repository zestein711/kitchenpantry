<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <script src= "https://kit.fontawesome.com/3c108498cb.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../../frontend/css/recipeSearch.css">

</head>

<body>

    <!-- Recipe Search Results -->
     <div class="recipe-results-container">

          <!-- Header -->
    <div class="header">
        <a href="dashboard.php"><h2>Kitchen Pantry</h2></a>
        <div class="icons">
            <a href="profile.html"><i class="fa-solid fa-user" id="profile-btn"></i></a>
            <i class="fa-solid fa-gear" id="settings-btn"></i>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <label>My Virtual Kitchen</label>
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Search" aria-label="Search" id="search-input">
            <button class="btn btn-outline-secondary" type="button" id="search-btn"><i class="fa-solid fa-search"></i></button>
        </div>
    </div>

        <!-- Filters -->
         <div class="recipe-filters">
            <label><input type="checkbox"> Gluten-Free</label>
            <label><input type="checkbox"> Vegan</label>
            <label><input type="checkbox"> Vegetarian</label>
            <label><input type="checkbox"> Peanut / Treenut Allergy</label>
            <label><input type="checkbox"> Shellfish Allergy</label>
            <label><input type="checkbox"> Lactose Intolerant</label>
         </div>

         <!-- Recipe Grid -->
          <div class="recipe-grid">

            <!-- Card 1 -->
            <div class="recipe-card">
                <div class="recipe-image"></div>
                <div class="recipe-info">
                    <h6>Recipe Name</h6>
                    <p>Time to make</p>
                </div>
                <i class="fa-solid fa-star favorite"></i>
            </div>

            <!-- Card 2 -->
            <div class="recipe-card">
                <div class="recipe-image"></div>
                <div class="recipe-info">
                    <h6>Recipe Name</h6>
                    <p>Time to make</p>
                </div>
                <i class="fa-solid fa-star"></i>
            </div>

            <!--Card 3 -->
            <div class="recipe-card">
                <div class="recipe-image"></div>
                <div class="recipe-info">
                    <h6>Recipe Name</h6>
                    <p>Time to make</p>
                </div>
                <i class="fa-solid fa-star"></i>
            </div>

            <!-- Card 4 -->
            <div class="recipe-card">
                <div class="recipe-image"></div>
                <div class="recipe-info">
                    <h6>Recipe Name</h6>
                    <p>Time to make</p>
                </div>
                <i class="fa-solid fa-star"></i>
            </div>
          </div>
     </div>

    <script src="../../frontend/js/recipeSearch.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

