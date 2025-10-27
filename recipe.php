<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kitchen Pantry</title>

    <!-- Boostrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <script src= "https://kit.fontawesome.com/3c108498cb.js" crossorigin="anonymous"></script>

    <!-- recipe.css file -->
    <link rel="stylesheet" href="../recipe.css">

</head>
<body>
    <!-- Header -->
    <div class="header">
        <a href="dashboard.html"><h2>Kitchen Pantry</h2></a>
        <div class="icons">
            <a href="profile.html"> <i class="fa-solid fa-user" id="profile-btn"></i></a>
            <i class="fa-solid fa-gear" id="settings-btn"></i>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row g-3">
            <!--Left Column -->
            <div class="col-md-8">
                <!-- Image -->
                 <div class="food-image mb-3">
                    Image of Food
                    <i class="fa-solid fa-star"></i>
                 </div>

                 <!-- Ingredients -->
                  <div class="card p-3 mb-3">
                    <h4>Ingredients Needed</h4>
                    <p id="ingredients-list">
                        <!--List of ingredients goes here-->
                    </p>
                  </div>

                  <!-- How to Make -->
                   <div class="card p-3">
                    <h4>How to Make</h4>
                    <p>
                        <!--Steps to make recipe goes here -->
                    </p>
                   </div>
            </div>

            <!-- Right Column -->
             <div class="col-md-4">
                <div class="card p-3">
                    <h4>Reviews</h4>
                    <div class="collaspsible">
                        <input
                        type="text"
                        class="form-control"
                        placeholder="Write a review..."
                        >
                        <button class="collaspe-toggle">▲</button>
                    </div>
                    <div id="reviewBox" class="mt-2">
                        <p>
                            <!--Review content here-->
                        </p>
                    </div>
                </div>
             </div>
        </div>
    </div>

    
    <script src="../recipe.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
