<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit;
}

// Include the database connection class
require_once('Database_Connection.php');

$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $db->escapeString($_POST['name']);
        $description = $db->escapeString($_POST['description']);
        $price = floatval($_POST['price']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $min_quantity = intval($_POST['min_quantity']);
        $category = $db->escapeString($_POST['category']);
        
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/products/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check if image file is a actual image or fake image
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false && move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
            }
        }

        try {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock_quantity, min_quantity, category, status, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, CURRENT_TIMESTAMP)");
            $stmt->bind_param("ssdiiss", $name, $description, $price, $stock_quantity, $min_quantity, $category, $image_url);

            if ($stmt->execute()) {
                $success_message = "Product added successfully!";
        } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $error_message = "Error adding product: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_product'])) {
        $id = intval($_POST['product_id']);
        $name = $db->escapeString($_POST['name']);
        $description = $db->escapeString($_POST['description']);
        $price = floatval($_POST['price']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $min_quantity = intval($_POST['min_quantity']);
        $status = $db->escapeString($_POST['status']);
        
        try {
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ?, min_quantity = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("ssdiisi", $name, $description, $price, $stock_quantity, $min_quantity, $status, $id);

            if ($stmt->execute()) {
                // Handle image update if new image is uploaded
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $target_dir = "uploads/products/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                        // Update image URL in database
                        $stmt = $conn->prepare("UPDATE products SET image_url = ? WHERE id = ?");
                        $stmt->bind_param("si", $target_file, $id);
                        $stmt->execute();
                    }
                }
                $success_message = "Product updated successfully!";
        } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $error_message = "Error updating product: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_product'])) {
        $id = intval($_POST['product_id']);
        
        try {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $success_message = "Product deleted successfully!";
    } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $error_message = "Error deleting product: " . $e->getMessage();
        }
    }
}

// Fetch all products
$products = [];
try {
    $result = $conn->query("SELECT * FROM products ORDER BY name");
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} catch (Exception $e) {
    $error_message = "Error fetching products: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Inventory - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="admin-styles.css" rel="stylesheet">
</head>
<body class="bg-light">
  <?php include('admin-navbar.php'); ?>
  
  <main class="py-5">
    <div class="container">
      <h2>Manage Inventory</h2>
      
      <?php if ($success_message): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>
      
      <?php if ($error_message): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>

      <!-- Add Product Form -->
      <div class="card mb-4 card-hover">
          <div class="card-header">
              <h5 class="mb-0">Add New Product</h5>
          </div>
          <div class="card-body">
              <form method="POST" enctype="multipart/form-data">
                  <div class="row">
                      <div class="col-md-6 mb-3">
                          <label for="name" class="form-label">Product Name</label>
                          <input type="text" class="form-control" id="name" name="name" required>
                      </div>
                      <div class="col-md-6 mb-3">
                          <label for="category" class="form-label">Category</label>
                          <select class="form-select" id="category" name="category" required>
                              <option value="Cleaning">Cleaning</option>
                              <option value="Polish">Polish</option>
                              <option value="Interior">Interior</option>
                              <option value="Accessories">Accessories</option>
                          </select>
                      </div>
                  </div>
                  <div class="mb-3">
                      <label for="description" class="form-label">Description</label>
                      <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                  </div>
                  <div class="row">
                      <div class="col-md-4 mb-3">
                          <label for="price" class="form-label">Price (LKR)</label>
                          <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                      </div>
                      <div class="col-md-4 mb-3">
                          <label for="stock_quantity" class="form-label">Stock Quantity</label>
                          <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                      </div>
                      <div class="col-md-4 mb-3">
                          <label for="min_quantity" class="form-label">Minimum Quantity</label>
                          <input type="number" class="form-control" id="min_quantity" name="min_quantity" required>
                      </div>
                  </div>
                  <div class="mb-3">
                      <label for="image" class="form-label">Product Image</label>
                      <input type="file" class="form-control" id="image" name="image" accept="image/*">
                  </div>
                  <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
              </form>
          </div>
      </div>

      <!-- Products List -->
      <div class="card card-hover">
          <div class="card-header">
              <h5 class="mb-0">Current Inventory</h5>
          </div>
          <div class="card-body">
              <div class="table-responsive">
                  <table class="table table-striped">
                      <thead>
                          <tr>
                              <th>Name</th>
                              <th>Category</th>
                              <th>Price</th>
                              <th>Stock</th>
                              <th>Status</th>
                              <th class="table-actions">Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach ($products as $product): ?>
                              <tr>
                                  <td><?php echo htmlspecialchars($product['name']); ?></td>
                                  <td><?php echo htmlspecialchars($product['category']); ?></td>
                                  <td>LKR <?php echo number_format($product['price'], 2); ?></td>
                                  <td>
                                      <?php 
                                      $stock_class = $product['stock_quantity'] <= $product['min_quantity'] ? 'text-danger' : 'text-success';
                                      echo "<span class='{$stock_class}'>" . $product['stock_quantity'] . "</span>";
                                      ?>
                                  </td>
                                  <td>
                                      <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'danger'; ?>">
                                          <?php echo ucfirst($product['status']); ?>
                                      </span>
                                  </td>
                                  <td>
                                      <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $product['id']; ?>">
                                          <i class="bi bi-pencil"></i>
                                      </button>
                                      <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $product['id']; ?>">
                                          <i class="bi bi-trash"></i>
                                      </button>
                                  </td>
                              </tr>
                              
                              <!-- Edit Modal -->
                              <div class="modal fade" id="editModal<?php echo $product['id']; ?>" tabindex="-1">
                                  <div class="modal-dialog">
                                      <div class="modal-content">
                                          <div class="modal-header">
                                              <h5 class="modal-title">Edit Product</h5>
                                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                          </div>
                                          <form method="POST" enctype="multipart/form-data">
                                              <div class="modal-body">
                                                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                  <div class="mb-3">
                                                      <label for="edit_name<?php echo $product['id']; ?>" class="form-label">Name</label>
                                                      <input type="text" class="form-control" id="edit_name<?php echo $product['id']; ?>" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                                  </div>
                                                  <div class="mb-3">
                                                      <label for="edit_description<?php echo $product['id']; ?>" class="form-label">Description</label>
                                                      <textarea class="form-control" id="edit_description<?php echo $product['id']; ?>" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                                  </div>
                                                  <div class="row">
                                                      <div class="col-md-6 mb-3">
                                                          <label for="edit_price<?php echo $product['id']; ?>" class="form-label">Price (LKR)</label>
                                                          <input type="number" class="form-control" id="edit_price<?php echo $product['id']; ?>" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                                                      </div>
                                                      <div class="col-md-6 mb-3">
                                                          <label for="edit_stock<?php echo $product['id']; ?>" class="form-label">Stock</label>
                                                          <input type="number" class="form-control" id="edit_stock<?php echo $product['id']; ?>" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>" required>
                                                      </div>
                                                  </div>
                                                  <div class="mb-3">
                                                      <label for="edit_min_quantity<?php echo $product['id']; ?>" class="form-label">Minimum Quantity</label>
                                                      <input type="number" class="form-control" id="edit_min_quantity<?php echo $product['id']; ?>" name="min_quantity" value="<?php echo $product['min_quantity']; ?>" required>
                                                  </div>
                                                  <div class="mb-3">
                                                      <label for="edit_status<?php echo $product['id']; ?>" class="form-label">Status</label>
                                                      <select class="form-select" id="edit_status<?php echo $product['id']; ?>" name="status" required>
                                                          <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                          <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                      </select>
                                                  </div>
                                                  <div class="mb-3">
                                                      <label for="edit_image<?php echo $product['id']; ?>" class="form-label">Update Image</label>
                                                      <input type="file" class="form-control" id="edit_image<?php echo $product['id']; ?>" name="image" accept="image/*">
                                                      <?php if ($product['image_url']): ?>
                                                          <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="mt-2" style="max-width: 100px;" alt="Current image">
                                                      <?php endif; ?>
                                                  </div>
                                              </div>
                                              <div class="modal-footer">
                                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                  <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
                                              </div>
                                          </form>
                                      </div>
                                  </div>
                              </div>

                              <!-- Delete Modal -->
                              <div class="modal fade" id="deleteModal<?php echo $product['id']; ?>" tabindex="-1">
                                  <div class="modal-dialog">
                                      <div class="modal-content">
                                          <div class="modal-header">
                                              <h5 class="modal-title">Delete Product</h5>
                                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                          </div>
                                          <div class="modal-body">
                                              <p>Are you sure you want to delete "<?php echo htmlspecialchars($product['name']); ?>"?</p>
                                          </div>
                                          <div class="modal-footer">
                                              <form method="POST">
                                                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                  <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                                              </form>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
    </div>
  </main>

  <?php include('admin-footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
