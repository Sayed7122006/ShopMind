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
| CHECK DATABASE CONNECTION & ADMIN AUTH
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection is not available.');
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../account/login.php');
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| HANDLE ACTIONS
|--------------------------------------------------------------------------
*/

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD NEW USER / ADMIN
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'customer';

        if ($name === '' || $email === '' || $password === '') {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please provide a valid email address.';
            $message_type = 'error';
        } elseif (!in_array($role, ['admin', 'vendor', 'customer'], true)) {
            $message = 'Invalid user role selected.';
            $message_type = 'error';
        } else {
            // Check if email already exists
            $check = $conn->prepare("SELECT id FROM users WHERE Email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $message = 'A user with this email address already exists.';
                $message_type = 'error';
                $check->close();
            } else {
                $check->close();
                // Store password hash (compatible with password_hash or md5 fallback)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $ins = $conn->prepare("INSERT INTO users (Name, Email, Password, role, is_banned) VALUES (?, ?, ?, ?, 0)");
                $ins->bind_param("ssss", $name, $email, $hashed_password, $role);
                if ($ins->execute()) {
                    $message = "User '{$name}' created successfully with role '{$role}'.";
                    $message_type = 'success';
                } else {
                    $message = 'Failed to create user: ' . $conn->error;
                    $message_type = 'error';
                }
                $ins->close();
            }
        }
    }

    // 2. CHANGE ROLE
    elseif ($action === 'change_role') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $new_role = $_POST['new_role'] ?? '';

        if ($target_id <= 0 || !in_array($new_role, ['admin', 'vendor', 'customer'], true)) {
            $message = 'Invalid role update request.';
            $message_type = 'error';
        } elseif ($target_id === $current_user_id && $new_role !== 'admin') {
            $message = 'You cannot demote your own admin account.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $target_id);
            if ($stmt->execute()) {
                $message = "Role updated to '{$new_role}' successfully.";
                $message_type = 'success';
            } else {
                $message = 'Failed to update role: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }

    // 3. TOGGLE BAN
    elseif ($action === 'toggle_ban') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $ban_state = (int)($_POST['is_banned'] ?? 0);
        $new_ban = $ban_state ? 0 : 1;

        if ($target_id === $current_user_id) {
            $message = 'You cannot ban your own account.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_ban, $target_id);
            if ($stmt->execute()) {
                $status_word = $new_ban ? 'banned' : 'unbanned';
                $message = "User has been {$status_word}.";
                $message_type = 'success';
            } else {
                $message = 'Failed to change ban status: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }

    // 4. DELETE USER
    elseif ($action === 'delete') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        if ($target_id === $current_user_id) {
            $message = 'You cannot delete your own account.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $target_id);
            if ($stmt->execute()) {
                $message = "User deleted successfully.";
                $message_type = 'success';
            } else {
                $message = 'Failed to delete user: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

/*
|--------------------------------------------------------------------------
| GET ALL USERS
|--------------------------------------------------------------------------
*/

$sql = "SELECT id, Name, Email, role, is_banned FROM users ORDER BY (id = {$current_user_id}) DESC, id DESC";
$result = $conn->query($sql);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Users & Permissions Management - ShopMind</title>

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

        .btn-add-user { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: #ffffff; border: none; padding: 12px 22px; border-radius: 10px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.35); }
        .btn-add-user:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(139, 92, 246, 0.5); color: #fff; text-decoration: none; }

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

        .user-name-box { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 38px; height: 38px; border-radius: 50%; background: #252b45; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #8b5cf6; border: 1px solid #334155; }
        .you-badge { background: #8b5cf6; color: #fff; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 4px; margin-left: 6px; }

        .role-badge { display: inline-block; padding: 5px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: capitalize; }
        .role-admin { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .role-vendor { background: rgba(139, 92, 246, 0.15); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.3); }
        .role-customer { background: rgba(59, 130, 246, 0.15); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }

        .status-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 9999px; }
        .status-allowed { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .status-banned { background: rgba(239, 68, 68, 0.15); color: #f87171; }

        .action-flex { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .role-select { background: #13192e; border: 1px solid #2d3755; color: #f8fafc; padding: 6px 10px; border-radius: 6px; font-size: 13px; outline: none; }
        .btn-action-small { background: #252b45; color: #cbd5e1; border: 1px solid #334155; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-action-small:hover { background: #334155; color: #fff; }
        .btn-ban { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3); }
        .btn-ban:hover { background: #f59e0b; color: #fff; }
        .btn-unban { background: rgba(16, 185, 129, 0.15); color: #34d399; border-color: rgba(16, 185, 129, 0.3); }
        .btn-unban:hover { background: #10b981; color: #fff; }
        .btn-del { background: rgba(239, 68, 68, 0.15); color: #f87171; border-color: rgba(239, 68, 68, 0.3); }
        .btn-del:hover { background: #ef4444; color: #fff; }

        /* Modal Styles */
        .modal-custom { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal-custom.active { display: flex; }
        .modal-box { background: #0d1122; border: 1px solid #252b45; border-radius: 14px; width: 90%; max-width: 520px; padding: 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8); }
        .modal-box h4 { margin: 0 0 20px 0; color: #fff; font-size: 20px; font-weight: 700; border-bottom: 1px solid #252b45; padding-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 7px; }
        .form-control-custom { width: 100%; background: #13192e; border: 1px solid #252b45; border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 14px; outline: none; }
        .form-control-custom:focus { border-color: #8b5cf6; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; border-top: 1px solid #252b45; padding-top: 16px; }
        .btn-cancel { background: #1e293b; color: #94a3b8; border: 1px solid #334155; padding: 9px 18px; border-radius: 8px; font-size: 14px; cursor: pointer; }
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
                        <div class="shopmind-title">Users & Permissions Management</div>
                        <p class="shopmind-subtitle">Manage system users, assign roles (Admin, Vendor, Customer), and manage access permissions.</p>
                    </div>
                    <button type="button" class="btn-add-user" onclick="openAddUserModal()">
                        <i class="fas fa-user-plus"></i> Add New User / Admin
                    </button>
                </div>

                <div class="shopmind-card">
                    <div class="shopmind-card-header">
                        <h6 class="shopmind-card-title">All Registered Users (<?= count($users) ?>)</h6>
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
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role / Permission</th>
                                    <th>Status</th>
                                    <th>Change Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $u): ?>
                                    <?php
                                        $uid = (int)$u['id'];
                                        $uname = (string)$u['Name'];
                                        $uemail = (string)$u['Email'];
                                        $urole = (string)$u['role'];
                                        $is_banned = (bool)$u['is_banned'];
                                        $is_me = ($uid === $current_user_id);
                                        $initial = strtoupper(substr($uname, 0, 1));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="user-name-box">
                                                <div class="user-avatar"><?= h($initial) ?></div>
                                                <div>
                                                    <span style="font-weight: 600; color: #fff;"><?= h($uname) ?></span>
                                                    <?php if ($is_me): ?>
                                                        <span class="you-badge">YOU</span>
                                                    <?php endif; ?>
                                                    <div style="font-size: 11px; color: #8b5cf6;">ID: #<?= $uid ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="color: #cbd5e1;"><?= h($uemail) ?></span>
                                        </td>
                                        <td>
                                            <span class="role-badge role-<?= h($urole) ?>">
                                                <?= h(ucfirst($urole)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($is_banned): ?>
                                                <span class="status-pill status-banned">
                                                    <i class="fas fa-ban"></i> Banned
                                                </span>
                                            <?php else: ?>
                                                <span class="status-pill status-allowed">
                                                    <i class="fas fa-check-circle"></i> Active
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$is_me): ?>
                                                <form method="POST" style="display: flex; gap: 6px;">
                                                    <input type="hidden" name="action" value="change_role">
                                                    <input type="hidden" name="user_id" value="<?= $uid ?>">
                                                    <select name="new_role" class="role-select" onchange="this.form.submit()">
                                                        <option value="customer" <?= $urole === 'customer' ? 'selected' : '' ?>>Customer</option>
                                                        <option value="vendor" <?= $urole === 'vendor' ? 'selected' : '' ?>>Vendor</option>
                                                        <option value="admin" <?= $urole === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    </select>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #94a3b8; font-size: 12px;">Primary Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$is_me): ?>
                                                <div class="action-flex">
                                                    <!-- Ban / Unban -->
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="toggle_ban">
                                                        <input type="hidden" name="user_id" value="<?= $uid ?>">
                                                        <input type="hidden" name="is_banned" value="<?= $is_banned ? 1 : 0 ?>">
                                                        <button type="submit" class="btn-action-small <?= $is_banned ? 'btn-unban' : 'btn-ban' ?>">
                                                            <i class="fas <?= $is_banned ? 'fa-unlock' : 'fa-ban' ?>"></i>
                                                            <?= $is_banned ? 'Unban' : 'Ban' ?>
                                                        </button>
                                                    </form>

                                                    <!-- Delete User -->
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete user <?= addslashes(h($uname)) ?>?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?= $uid ?>">
                                                        <button type="submit" class="btn-action-small btn-del" title="Delete User">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #64748b; font-size: 12px;">Active Session</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        No users found.
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
     MODAL: ADD NEW USER / ADMIN
========================================================================= -->
<div id="modalAddUser" class="modal-custom">
    <div class="modal-box">
        <h4>
            <span><i class="fas fa-user-plus" style="color: #8b5cf6; margin-right: 8px;"></i>Add New User</span>
            <button type="button" style="background:none;border:none;color:#94a3b8;font-size:24px;cursor:pointer;" onclick="closeAddUserModal()">&times;</button>
        </h4>
        <form method="POST">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" class="form-control-custom" placeholder="e.g. Mahmoud Ali" required>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" class="form-control-custom" placeholder="e.g. user@example.com" required>
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" class="form-control-custom" placeholder="Minimum 6 characters" required>
            </div>

            <div class="form-group">
                <label>Role / Permissions *</label>
                <select name="role" class="form-control-custom" required>
                    <option value="customer">Customer (Shop & Order)</option>
                    <option value="vendor">Vendor (Add & Manage own products)</option>
                    <option value="admin">Admin (Full Control of System)</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddUserModal()">Cancel</button>
                <button type="submit" class="btn-submit">Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddUserModal() {
        document.getElementById('modalAddUser').classList.add('active');
    }
    function closeAddUserModal() {
        document.getElementById('modalAddUser').classList.remove('active');
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
