<?php
require '../../includes/auth.php';
require_once '../../config/database.php';
requireLogin();

// Check ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

// Fetch Category
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header("Location: index.php");
    exit();
}

$message = "";

// Update Category
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $category_name = trim($_POST['category_name']);
    $description   = trim($_POST['description']);

    if ($category_name == "") {

        $message = "<div class='alert alert-danger'>
                        Category name is required.
                    </div>";

    } else {

        $stmt = $conn->prepare("
            UPDATE categories
            SET category_name=?, description=?
            WHERE id=?
        ");

        if ($stmt->execute([$category_name, $description, $id])) {

            $message = "<div class='alert alert-success'>
                            Category updated successfully.
                        </div>";

            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM categories WHERE id=?");
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

        } else {

            $message = "<div class='alert alert-danger'>
                            Failed to update category.
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

<i class="fas fa-edit"></i>

Edit Category

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

<?= $message ?>

<div class="card card-warning">

<div class="card-header">

<h3 class="card-title">

Update Category

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
value="<?= htmlspecialchars($category['category_name']) ?>"
required>

</div>

<div class="form-group">

<label>Description</label>

<textarea
name="description"
rows="4"
class="form-control"><?= htmlspecialchars($category['description']) ?></textarea>

</div>

</div>

<div class="card-footer">

<button
type="submit"
class="btn btn-warning">

<i class="fas fa-save"></i>

Update Category

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
