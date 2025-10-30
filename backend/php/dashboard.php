<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Pantry Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/3c108498cb.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
        }
        .header {
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        .header .icons {
            display: flex;
            gap: 15px;
        }
        .header .icons i {
            cursor: pointer;
            font-size: 1.2em;
        }
        .search-section {
            background: white;
            padding: 10px 20px;
            margin-top: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-section label {
            margin: 0;
            font-weight: bold;
        }
        .search-section .input-group {
            width: 300px;
        }

.content {
  padding: 20px;
  background: white;
  margin-top: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
} 

/* Shelf styling */
.shelf {
  display: flex;
  justify-content: space-between;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 50px;
  margin-bottom: 20px;
  box-shadow: inset 0 0 5px rgba(0,0,0,0.1);
}

/* Food item styling */
.food-item {
  background-color: white;
  border: 2px solid transparent;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  width: 100px;
  height:100px;
  cursor: pointer;
  transition: 0.3s;
  font-size: 1.1em;
}

.food-item:hover {
  background-color: #f1f1f1;
  transform: translateY(-2px);
}

.food-item.selected {
  border-color: #0d6efd;
  background-color: #e7f1ff;
  font-weight: bold;
}

/* Responsive shelves */
@media (max-width: 768px) {
  .shelf {
    flex-wrap: wrap;
    justify-content: center;
  }
  .food-item {
    width: 100px;
    margin: 10px;
  }
}
    </style>

</head>
<body>
    <!-- Header -->
    <div class="header">
       <a href="dashboard.php"> <h2>Kitchen Pantry</h2></a>
        <div class="icons">
            <a href="profile.php"><i class="fa-solid fa-user" id="profile-btn"></i></a>
            <i class="fa-solid fa-gear" id="settings-btn"></i>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <label>My Virtual Kitchen</label>
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Search" aria-label="Search" id="search-input">
            <a href="recipeSearch.html"><button class="btn btn-outline-secondary" type="button" id="search-btn"><i class="fa-solid fa-search"></i></button></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h4>My Pantry</h4>

        <!-- There are 4 "shelves" each "shelf" can hold 5 "food-item"-->
         <!-- I thought about adding different sections like fridge/pantry/freezer/spice rack, but I 
          Decided to leave it as just this for now. -->
        <div class="pantry-grid">

            <!-- Shelf 1 -->
            <div class="shelf">
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
            </div>

            <!-- Shelf 2 -->
            <div class="shelf">
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
            </div>

            <!-- Shelf 3 -->
            <div class="shelf">
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
            </div>

            <!-- Shelf 4 -->
            <div class="shelf">
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
                <div class="food-item" data-ingredient="">Food Item</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/dashboard.js"></script>
</body>
</html>
