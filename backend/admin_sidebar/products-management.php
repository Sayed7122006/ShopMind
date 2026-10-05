<?php

session_start();

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

$db_connection = __DIR__ . '/../fun/db_connection.php';

if (!file_exists($db_connection)) {
    die('Database connection file not found: ' . htmlspecialchars($db_connection, ENT_QUOTES, 'UTF-8'));
}

require_once $db_connection;

/*
|--------------------------------------------------------------------------
| CHECK DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection is not available.');
}

/*
|--------------------------------------------------------------------------
| CHECK LOGIN & ADMIN ROLE
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'vendor'], true)) {
    header('Location: ../account/login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
if ($role !== 'admin') {
    http_response_code(403);
    exit('Forbidden: Admin access only.');
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function resolve_product_image(int $id, mixed $img_data): string
{
    // 1. If file path
    if (!empty($img_data) && is_string($img_data) && strlen($img_data) < 255) {
        $clean = ltrim($img_data, '/');
        if (file_exists(__DIR__ . '/../../frontend/' . $clean)) {
            return '/ShopMind/frontend/' . $clean;
        }
    }

    // 2. If BLOB
    if (!empty($img_data)) {
        $blob = $img_data;
        $mime = null;
        if (str_starts_with($blob, "\x89PNG")) {
            $mime = 'image/png';
        } elseif (str_starts_with($blob, "\xFF\xD8\xFF")) {
            $mime = 'image/jpeg';
        } elseif (str_starts_with($blob, "RIFF") && str_contains(substr($blob, 8, 8), "WEBP")) {
            $mime = 'image/webp';
        } elseif (str_starts_with($blob, "GIF8")) {
            $mime = 'image/gif';
        }
        if ($mime) {
            return "data:{$mime};base64," . base64_encode($blob);
        }
    }

    // 3. Fallback to existing disk images
    $idx = $id - 1;
    if (file_exists(__DIR__ . "/../../frontend/img/product/{$idx}.png")) {
        return "/ShopMind/frontend/img/product/{$idx}.png";
    }
    if (file_exists(__DIR__ . "/../../frontend/img/product/{$id}.png")) {
        return "/ShopMind/frontend/img/product/{$id}.png";
    }

    return "/ShopMind/frontend/img/logo.png";
}

/*
|--------------------------------------------------------------------------
| HANDLE ACTIONS (ADD, EDIT, DELETE)
|--------------------------------------------------------------------------
*/

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD PRODUCT
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $vendor_id = !empty($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : (int)$_SESSION['user_id'];

        if ($name === '' || $category === '' || $price <= 0) {
            $message = 'Please provide a valid product name, category, and positive price.';
            $message_type = 'error';
        } else {
            $imagePath = null;
            if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $targetDir = __DIR__ . '/../../frontend/img/product/';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $filename = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['img']['tmp_name'], $targetDir . $filename)) {
                        $imagePath = 'img/product/' . $filename;
                    }
                }
            }

            $stmt = $conn->prepare("INSERT INTO products (name, category, price, img, vendor_id) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssdsi", $name, $category, $price, $imagePath, $vendor_id);
                if ($stmt->execute()) {
                    $newId = $stmt->insert_id;
                    $message = "Product #{$newId} ({$name}) added successfully!";
                    $message_type = 'success';
                } else {
                    $message = 'Database error: ' . $conn->error;
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }

    // 2. EDIT PRODUCT
    elseif ($action === 'edit') {
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = (float)($_POST['price'] ?? 0);

        if ($edit_id <= 0 || $name === '' || $category === '' || $price <= 0) {
            $message = 'Please enter valid details for editing the product.';
            $message_type = 'error';
        } else {
            $imagePath = null;
            $hasNewImg = false;

            if (isset($_FILES['edit_img']) && $_FILES['edit_img']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['edit_img']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $targetDir = __DIR__ . '/../../frontend/img/product/';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $filename = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['edit_img']['tmp_name'], $targetDir . $filename)) {
                        $imagePath = 'img/product/' . $filename;
                        $hasNewImg = true;
                    }
                }
            }

            if ($hasNewImg) {
                $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, img = ? WHERE id = ?");
                $stmt->bind_param("ssdsi", $name, $category, $price, $imagePath, $edit_id);
            } else {
                $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ? WHERE id = ?");
                $stmt->bind_param("ssdi", $name, $category, $price, $edit_id);
            }

            if ($stmt && $stmt->execute()) {
                $message = "Product #{$edit_id} updated successfully!";
                $message_type = 'success';
            } else {
                $message = 'Failed to update product: ' . $conn->error;
                $message_type = 'error';
            }
            if ($stmt) $stmt->close();
        }
    }

    // 3. DELETE PRODUCT
    elseif (isset($_POST['delete_id'])) {
        $del_id = (int)$_POST['delete_id'];
        if ($del_id > 0) {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $del_id);
                if ($stmt->execute()) {
                    $message = "Product #{$del_id} deleted successfully.";
                    $message_type = 'success';
                } else {
                    $message = 'Unable to delete product: ' . $conn->error;
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS, CATEGORIES, & VENDORS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.vendor_id,
        p.name,
        p.price,
        p.category,
        p.img,
        COALESCE(u.Name, 'Admin') AS vendor_name
    FROM products p
    LEFT JOIN users u ON u.id = p.vendor_id
    ORDER BY p.id DESC
";

$result = $conn->query($sql);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['resolved_img'] = resolve_product_image((int)$row['id'], $row['img']);
        $products[] = $row;
    }
    $result->free();
}

// Fetch distinct categories for autocomplete / dropdown
$cats_res = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' UNION SELECT name FROM categories ORDER BY category ASC");
$all_categories = [];
if ($cats_res) {
    while ($c = $cats_res->fetch_row()) {
        if (!empty($c[0])) $all_categories[] = $c[0];
    }
    $cats_res->free();
}

// Fetch vendors
$vendors_res = $conn->query("SELECT id, Name, role FROM users WHERE role IN ('vendor', 'admin') ORDER BY Name ASC");
$all_vendors = [];
if ($vendors_res) {
    while ($v = $vendors_res->fetch_assoc()) {
        $all_vendors[] = $v;
    }
    $vendors_res->free();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Products Management - ShopMind</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body { overflow-x: hidden; background: #080b18 !important; color: #e2e8f0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        #wrapper, #content-wrapper, #content { background: #080b18 !important; min-height: 100vh; }
        .shopmind-content { padding: 30px; background: #080b18; min-height: calc(100vh - 80px); }
        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .shopmind-title { font-size: 26px; font-weight: 800; color: #ffffff; margin-bottom: 5px; }
        .shopmind-subtitle { color: #9ca3af; font-size: 14px; margin: 0; }
        
        .btn-add-product { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: #ffffff; border: none; padding: 12px 22px; border-radius: 10px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.35); }
        .btn-add-product:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(139, 92, 246, 0.5); color: #fff; text-decoration: none; }

        .shopmind-card { background: #0d1122; border: 1px solid #252b45; border-radius: 12px; overflow: hidden; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
        .shopmind-card-header { padding: 20px 24px; border-bottom: 1px solid #252b45; display: flex; align-items: center; justify-content: space-between; }
        .shopmind-card-title { color: #ffffff; font-size: 18px; font-weight: 700; margin: 0; }
        .shopmind-message { padding: 12px 18px; margin: 20px 24px 0 24px; border-radius: 8px; font-size: 14px; font-weight: 500; }
        .shopmind-message.success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; }
        .shopmind-message.error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; }

        .table-responsive { padding: 20px 24px; }
        .shopmind-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .shopmind-table th { background: transparent; color: #94a3b8; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 14px 16px; border-bottom: 1px solid #252b45; text-align: left; }
        .shopmind-table td { padding: 16px; border-bottom: 1px solid #1a2236; color: #f1f5f9; font-size: 14px; vertical-align: middle; }
        .shopmind-table tr:hover td { background: rgba(255, 255, 255, 0.02); }

        .prod-cell { display: flex; align-items: center; gap: 14px; }
        .prod-thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: contain; background: #13192e; padding: 3px; border: 1px solid #252b45; }
        .prod-name { font-weight: 600; color: #ffffff; font-size: 14px; }
        .prod-id { font-size: 12px; color: #8b5cf6; font-weight: 600; margin-top: 2px; }

        .category-badge { display: inline-block; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #93c5fd; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .prod-price { font-weight: 700; color: #10b981; font-size: 15px; }
        .vendor-badge { color: #cbd5e1; font-size: 13px; }

        .action-flex { display: flex; align-items: center; gap: 8px; }
        .btn-edit { background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa; padding: 7px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-edit:hover { background: #3b82f6; color: #fff; }
        .btn-delete { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 7px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; }
        .btn-delete:hover { background: #ef4444; color: #fff; }

        /* Modal Styles */
        .modal-custom { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal-custom.active { display: flex; }
        .modal-box { background: #0d1122; border: 1px solid #252b45; border-radius: 14px; width: 90%; max-width: 580px; max-height: 90vh; overflow-y: auto; padding: 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8); }
        .modal-box h4 { margin: 0 0 20px 0; color: #fff; font-size: 20px; font-weight: 700; border-bottom: 1px solid #252b45; padding-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 7px; }
        .form-control-custom { width: 100%; background: #13192e; border: 1px solid #252b45; border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-control-custom:focus { border-color: #8b5cf6; }
        .preview-box { margin-top: 10px; display: none; align-items: center; gap: 12px; background: #13192e; padding: 8px 12px; border-radius: 8px; border: 1px solid #252b45; }
        .preview-img { width: 60px; height: 60px; object-fit: contain; border-radius: 6px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; border-top: 1px solid #252b45; padding-top: 16px; }
        .btn-cancel { background: #1e293b; color: #94a3b8; border: 1px solid #334155; padding: 9px 18px; border-radius: 8px; font-size: 14px; cursor: pointer; }
        .btn-cancel:hover { background: #334155; color: #fff; }
        .btn-submit { background: #8b5cf6; color: #fff; border: none; padding: 9px 22px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-submit:hover { background: #7c3aed; }
    </style>
</head>

<body id="page-top">
<div id="wrapper">

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <!-- TOPBAR -->
            <?php include __DIR__ . '/../includes/topbar.php'; ?>

            <div class="shopmind-content">
                <div class="page-header-flex">
                    <div>
                        <div class="shopmind-title">Products Management</div>
                        <p class="shopmind-subtitle">Add, edit, upload pictures, and manage all store products.</p>
                    </div>
                    <button type="button" class="btn-add-product" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add New Product
                    </button>
                </div>

                <div class="shopmind-card">
                    <div class="shopmind-card-header">
                        <h6 class="shopmind-card-title">All Products (<?= count($products) ?>)</h6>
                    </div>

                    <?php if ($message): ?>
                        <div class="shopmind-message <?= h($message_type) ?>">
                            <?= h($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="shopmind-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Vendor</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $p): ?>
                                    <?php
                                        $pid = (int)$p['id'];
                                        $pname = (string)$p['name'];
                                        $pcat = (string)$p['category'];
                                        $pprice = (float)$p['price'];
                                        $pimg = (string)$p['resolved_img'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="prod-cell">
                                                <img src="<?= h($pimg) ?>" alt="<?= h($pname) ?>" class="prod-thumb" onerror="this.src='/ShopMind/frontend/img/logo.png'">
                                                <div>
                                                    <div class="prod-name"><?= h($pname) ?></div>
                                                    <div class="prod-id">ID: #<?= $pid ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="category-badge"><?= h($pcat) ?></span>
                                        </td>
                                        <td>
                                            <span class="prod-price">$<?= number_format($pprice, 2) ?></span>
                                        </td>
                                        <td>
                                            <span class="vendor-badge"><?= h($p['vendor_name']) ?></span>
                                        </td>
                                        <td>
                                            <div class="action-flex">
                                                <button type="button" class="btn-edit" 
                                                    onclick="openEditModal(<?= $pid ?>, '<?= addslashes(h($pname)) ?>', '<?= addslashes(h($pcat)) ?>', <?= $pprice ?>, '<?= addslashes(h($pimg)) ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>

                                                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete product #<?= $pid ?> (<?= addslashes(h($pname)) ?>)?');">
                                                    <input type="hidden" name="delete_id" value="<?= $pid ?>">
                                                    <button type="submit" class="btn-delete" title="Delete Product">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fas fa-box-open" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                        No products found in the catalog.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: ADD NEW PRODUCT
========================================================================= -->
<div id="modalAddProduct" class="modal-custom">
    <div class="modal-box">
        <h4>
            <span><i class="fas fa-plus-circle" style="color: #8b5cf6; margin-right: 8px;"></i>Add New Product</span>
            <button type="button" style="background:none;border:none;color:#94a3b8;font-size:24px;cursor:pointer;" onclick="closeAddModal()">&times;</button>
        </h4>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name" class="form-control-custom" placeholder="e.g. Apple iPhone 15 Pro Max 256GB" required>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <input type="text" name="category" list="categoryList" class="form-control-custom" placeholder="Select or type category (e.g. mobiles, electronics, appliances)" required>
                <datalist id="categoryList">
                    <?php foreach ($all_categories as $c): ?>
                        <option value="<?= h($c) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label>Price ($) *</label>
                <input type="number" step="0.01" min="0.01" name="price" class="form-control-custom" placeholder="e.g. 999.00" required>
            </div>

            <div class="form-group">
                <label>Product Image (JPG, PNG, WEBP)</label>
                <input type="file" name="img" id="addImageInput" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control-custom" onchange="previewImage(this, 'addPreviewBox', 'addPreviewImg')">
                <div id="addPreviewBox" class="preview-box">
                    <img id="addPreviewImg" src="" class="preview-img">
                    <span style="font-size: 13px; color: #94a3b8;">Image selected for upload</span>
                </div>
            </div>

            <div class="form-group">
                <label>Vendor / Assigned To</label>
                <select name="vendor_id" class="form-control-custom">
                    <?php foreach ($all_vendors as $v): ?>
                        <option value="<?= (int)$v['id'] ?>" <?= ((int)$v['id'] === (int)$_SESSION['user_id']) ? 'selected' : '' ?>>
                            <?= h($v['Name']) ?> (<?= h($v['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn-submit">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL: EDIT PRODUCT
========================================================================= -->
<div id="modalEditProduct" class="modal-custom">
    <div class="modal-box">
        <h4>
            <span><i class="fas fa-edit" style="color: #3b82f6; margin-right: 8px;"></i>Edit Product</span>
            <button type="button" style="background:none;border:none;color:#94a3b8;font-size:24px;cursor:pointer;" onclick="closeEditModal()">&times;</button>
        </h4>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="edit_id" id="editProductId">

            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name" id="editProductName" class="form-control-custom" required>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <input type="text" name="category" id="editProductCat" list="categoryList" class="form-control-custom" required>
            </div>

            <div class="form-group">
                <label>Price ($) *</label>
                <input type="number" step="0.01" min="0.01" name="price" id="editProductPrice" class="form-control-custom" required>
            </div>

            <div class="form-group">
                <label>Replace Image (Leave blank to keep existing image)</label>
                <input type="file" name="edit_img" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control-custom" onchange="previewImage(this, 'editPreviewBox', 'editPreviewImg')">
                <div id="editPreviewBox" class="preview-box" style="display: flex;">
                    <img id="editPreviewImg" src="" class="preview-img">
                    <span id="editPreviewText" style="font-size: 13px; color: #94a3b8;">Current Product Image</span>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-submit" style="background: #3b82f6;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalAddProduct').classList.add('active');
    }
    function closeAddModal() {
        document.getElementById('modalAddProduct').classList.remove('active');
    }

    function openEditModal(id, name, cat, price, img) {
        document.getElementById('editProductId').value = id;
        document.getElementById('editProductName').value = name;
        document.getElementById('editProductCat').value = cat;
        document.getElementById('editProductPrice').value = price;
        document.getElementById('editPreviewImg').src = img;
        document.getElementById('editPreviewText').textContent = "Current Product Image";
        document.getElementById('modalEditProduct').classList.add('active');
    }
    function closeEditModal() {
        document.getElementById('modalEditProduct').classList.remove('active');
    }

    function previewImage(input, boxId, imgId) {
        const box = document.getElementById(boxId);
        const img = document.getElementById(imgId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                box.style.display = 'flex';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-custom')) {
            e.target.classList.remove('active');
        }
    });
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

</body>
</html>
