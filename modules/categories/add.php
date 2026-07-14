<?php
require '../../includes/auth.php';
require_once '../../config/database.php';
requireLogin();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $category_name = trim($_POST['category_name']);
    $description   = trim($_POST['description']);

    if ($category_name == "") {

        $message = "<div class='alert alert-danger'>
                        Category name is required.
                    </div>";

    } else {

        $stmt = $conn->prepare("
            INSERT INTO categories(category_name, description)
            VALUES(?,?)
        ");

        if($stmt->execute([$category_name,$description])){

            $message = "<div class='alert alert-success'>
                            Category added successfully.
                        </div>";

        }else{

            $message = "<div class='alert alert-danger'>
                            Failed to save category.
                        </div>";

        }

    }

}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>

<div class="content-wrapper">

<section class="content-header">

<div class="container-fluid">

<div class="row mb-2">

<div class="col-sm-6">

<h1>

<i class="fas fa-plus-circle"></i>

Add Category

</h1>

</div>

<div class="col-sm-6 text-right">

<a href="index.php" class="btn btn-secondary">

<i class="fas fa-arrow-left"></i>

Back

</a>

</div>

</div>

</div>

</section>

<section class="content">

<div class="container-fluid">

<?= $message; ?>

<div class="card card-primary">

<div class="card-header">

<h3 class="card-title">

Category Details

</h3>

</div>

<form method="POST">

<div class="card-body">

<div class="form-group">

<label>Category Name</label>

<input
type="text"
name="category_name"
class="form-control"
placeholder="Enter Category Name"
required>

</div>

<div class="form-group">

<label>Description</label>

<textarea
name="description"
rows="4"
class="form-control"
placeholder="Enter Description"></textarea>

</div>

</div>

<div class="card-footer">

<button
type="submit"
class="btn btn-success">

<i class="fas fa-save"></i>

Save Category

</button>

<a href="index.php"
class="btn btn-danger">

Cancel

</a>

</div>

</form>

</div>

</div>

</section>

</div>

<?php include '../../includes/footer.php'; ?>
