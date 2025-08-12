<?php
session_start();
include('Database_Connection.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_service'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $vehicle_types = json_encode($_POST['vehicle_types']); // Changed to array of vehicle types

        $stmt = $conn->prepare("INSERT INTO services (name, description, price, duration, vehicle_types, status) 
                               VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssdis", $name, $description, $price, $duration, $vehicle_types);

        if ($stmt->execute()) {
            $success_message = "Service added successfully!";
        } else {
            $error_message = "Error adding service: " . $conn->error;
        }
    } elseif (isset($_POST['update_service'])) {
        $id = $_POST['service_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $vehicle_types = json_encode($_POST['vehicle_types']); // Changed to array of vehicle types
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, price = ?, 
                               duration = ?, vehicle_types = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssdissi", $name, $description, $price, $duration, $vehicle_types, $status, $id);

        if ($stmt->execute()) {
            $success_message = "Service updated successfully!";
        } else {
            $error_message = "Error updating service: " . $conn->error;
        }
    } elseif (isset($_POST['delete_service'])) {
        $id = $_POST['service_id'];

        // Check if service is used in any bookings
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE service_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            // Service is in use, just mark it as inactive
            $stmt = $conn->prepare("UPDATE services SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_message = "Service marked as inactive as it has existing bookings.";
            } else {
                $error_message = "Error updating service status: " . $conn->error;
            }
        } else {
            // Service not in use, safe to delete
            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_message = "Service deleted successfully!";
            } else {
                $error_message = "Error deleting service: " . $conn->error;
            }
        }
    }
}

// Get all services
$services_query = "SELECT * FROM services ORDER BY name";
$services_result = mysqli_query($conn, $services_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
    <style>
        .vehicle-type {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .duration {
            font-size: 0.875rem;
            color: #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('admin-navbar.php'); ?>

    <main class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Service Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="bi bi-plus-lg"></i> Add New Service
                </button>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php
                if ($services_result && mysqli_num_rows($services_result) > 0) {
                    while ($service = mysqli_fetch_assoc($services_result)) {
                        $statusClass = 'secondary';
                        switch ($service['status']) {
                            case 'active':
                                $statusClass = 'success';
                                break;
                            case 'inactive':
                                $statusClass = 'danger';
                                break;
                        }
                        
                        // Decode vehicle types
                        $vehicle_types = json_decode($service['vehicle_types'], true);
                        if (!is_array($vehicle_types)) {
                            $vehicle_types = [];
                        }
                        ?>
                        <div class="col-md-4">
                            <div class="card card-hover h-100">
                                <div class="card-body">
                                    <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                    <h5 class="card-title mb-3"><?php echo htmlspecialchars($service['name']); ?></h5>
                                    <p class="card-text text-muted mb-2"><?php echo htmlspecialchars($service['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">LKR <?php echo number_format($service['price'], 2); ?></h6>
                                        <span class="duration">
                                            <i class="bi bi-clock"></i> <?php echo $service['duration']; ?> mins
                                        </span>
                                    </div>
                                    <div class="vehicle-type mb-3">
                                        <i class="bi bi-car-front"></i> 
                                        <?php 
                                        if (!empty($vehicle_types)) {
                                            echo implode(', ', array_map('ucfirst', $vehicle_types));
                                        } else {
                                            echo 'No vehicle types specified';
                                        }
                                        ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary flex-grow-1" 
                                                onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-outline-danger" 
                                                onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['name']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12"><div class="alert alert-info">No services found.</div></div>';
                }
                ?>
            </div>
        </div>
    </main>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Service Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vehicle Types</label>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="vehicle_car" name="vehicle_types[]" value="car">
                                <label class="form-check-label" for="vehicle_car">Car</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="vehicle_suv" name="vehicle_types[]" value="suv">
                                <label class="form-check-label" for="vehicle_suv">SUV</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="vehicle_van" name="vehicle_types[]" value="van">
                                <label class="form-check-label" for="vehicle_van">Van</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="vehicle_bike" name="vehicle_types[]" value="bike">
                                <label class="form-check-label" for="vehicle_bike">Bike</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (LKR)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="duration" name="duration" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" id="edit_service_id" name="service_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Service Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vehicle Types</label>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_vehicle_car" name="vehicle_types[]" value="car">
                                <label class="form-check-label" for="edit_vehicle_car">Car</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_vehicle_suv" name="vehicle_types[]" value="suv">
                                <label class="form-check-label" for="edit_vehicle_suv">SUV</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_vehicle_van" name="vehicle_types[]" value="van">
                                <label class="form-check-label" for="edit_vehicle_van">Van</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_vehicle_bike" name="vehicle_types[]" value="bike">
                                <label class="form-check-label" for="edit_vehicle_bike">Bike</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price" class="form-label">Price (LKR)</label>
                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="edit_duration" name="duration" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_service" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Service Modal -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" id="delete_service_id" name="service_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong id="delete_service_name"></strong>?</p>
                        <p class="text-danger mb-0">If the service has any existing bookings, it will be marked as inactive instead of being deleted.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_service" class="btn btn-danger">Delete Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('admin-footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editService(service) {
            document.getElementById('edit_service_id').value = service.id;
            document.getElementById('edit_name').value = service.name;
            document.getElementById('edit_description').value = service.description;
            document.getElementById('edit_price').value = service.price;
            document.getElementById('edit_duration').value = service.duration;
            document.getElementById('edit_status').value = service.status;
            
            // Clear all checkboxes first
            document.querySelectorAll('input[name="vehicle_types[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check the appropriate vehicle types
            const vehicleTypes = JSON.parse(service.vehicle_types || '[]');
            vehicleTypes.forEach(type => {
                const checkbox = document.getElementById('edit_vehicle_' + type);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            
            new bootstrap.Modal(document.getElementById('editServiceModal')).show();
        }

        function deleteService(id, name) {
            document.getElementById('delete_service_id').value = id;
            document.getElementById('delete_service_name').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteServiceModal')).show();
        }
    </script>
</body>
</html>
<?php
$db->closeConnection();
?>
