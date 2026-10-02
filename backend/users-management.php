<?php
session_start();
require_once __DIR__ . '/fun/db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Admin only.');
}

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

$adminId = (int)$_SESSION['user_id'];
$message = '';
$messageType = 'success';
if (empty($_SESSION['csrf_users'])) {
    $_SESSION['csrf_users'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_users'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $postedToken)) {
        $message = 'انتهت صلاحية الطلب. حدّث الصفحة وحاول مرة أخرى.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if (!$targetId || $targetId < 1) {
            $message = 'معرّف المستخدم غير صحيح.';
            $messageType = 'danger';
        } elseif ($action === 'delete') {
            if ($targetId === $adminId) {
                $message = 'لا يمكنك حذف حسابك الحالي أثناء تسجيل الدخول.';
                $messageType = 'danger';
            } else {
                try {
                    $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'i', $targetId);
                    mysqli_stmt_execute($stmt);
                    $affected = mysqli_stmt_affected_rows($stmt);
                    mysqli_stmt_close($stmt);
                    $message = $affected ? 'تم حذف المستخدم.' : 'المستخدم غير موجود.';
                    $messageType = $affected ? 'success' : 'warning';
                } catch (mysqli_sql_exception $e) {
                    $message = 'تعذّر حذف المستخدم؛ قد تكون له طلبات أو بيانات مرتبطة. راجع العلاقات في قاعدة البيانات.';
                    $messageType = 'danger';
                }
            }
        } elseif ($action === 'ban' || $action === 'unban') {
            if ($targetId === $adminId) {
                $message = 'لا يمكنك حظر حسابك الحالي.';
                $messageType = 'danger';
            } else {
                try {
                    // الحظر متاح للعملاء والبائعين فقط، وليس لحسابات الإدارة.
                    $stmt = mysqli_prepare($conn, "UPDATE users SET is_banned = ? WHERE id = ? AND role IN ('customer', 'vendor')");
                    $banValue = ($action === 'ban') ? 1 : 0;
                    mysqli_stmt_bind_param($stmt, 'ii', $banValue, $targetId);
                    mysqli_stmt_execute($stmt);
                    $affected = mysqli_stmt_affected_rows($stmt);
                    mysqli_stmt_close($stmt);
                    $message = $affected ? (($banValue === 1) ? 'تم حظر الحساب.' : 'تم إلغاء الحظر.') : 'لم يتغير الحساب؛ ربما غير موجود أو من نوع Admin بالفعل.';
                    $messageType = $affected ? 'success' : 'warning';
                } catch (mysqli_sql_exception $e) {
                    $message = 'تعذّر تنفيذ الحظر. تأكد من إضافة عمود is_banned إلى جدول users.';
                    $messageType = 'danger';
                }
            }
        } elseif ($action === 'update') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $allowedRoles = ['admin', 'vendor', 'customer'];

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $allowedRoles, true)) {
                $message = 'راجع الاسم والبريد الإلكتروني والصلاحية.';
                $messageType = 'danger';
            } elseif ($targetId === $adminId && $role !== 'admin') {
                $message = 'لا يمكنك تغيير صلاحية حسابك الحالي من هنا.';
                $messageType = 'danger';
            } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
                $message = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.';
                $messageType = 'danger';
            } else {
                try {
                    if ($newPassword !== '') {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = mysqli_prepare($conn, 'UPDATE users SET Name = ?, Email = ?, role = ?, Password = ? WHERE id = ?');
                        mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $role, $hashedPassword, $targetId);
                    } else {
                        $stmt = mysqli_prepare($conn, 'UPDATE users SET Name = ?, Email = ?, role = ? WHERE id = ?');
                        mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $role, $targetId);
                    }
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $message = 'تم تحديث بيانات المستخدم.';
                    $messageType = 'success';
                } catch (mysqli_sql_exception $e) {
                    $message = ($e->getCode() === 1062)
                        ? 'البريد الإلكتروني مستخدم بالفعل.'
                        : 'حدث خطأ أثناء التحديث. تأكد من اتصال قاعدة البيانات.';
                    $messageType = 'danger';
                }
            }
        }
    }
}

$usersResult = mysqli_query($conn, 'SELECT id, Name, Email, role, is_banned FROM users ORDER BY id DESC');
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div id="content-wrapper" class="d-flex flex-column">
  <div id="content">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="container-fluid py-4">
      <style>
        .um-panel{background:#fff;border-radius:12px;padding:22px;box-shadow:0 2px 14px #0000000b}
        .um-table{width:100%;border-collapse:collapse;min-width:680px}
        .um-table th,.um-table td{padding:12px;border-bottom:1px solid #eee;text-align:left;vertical-align:middle}
        .um-table th{background:#f8f9fc;color:#444}
        .um-btn{border:0;border-radius:6px;padding:7px 12px;color:#fff;cursor:pointer}
        .um-edit{background:#4e73df}.um-delete{background:#e74a3b}
        .um-input{width:100%;padding:9px;border:1px solid #d1d3e2;border-radius:6px;margin:4px 0}
        .um-actions{display:flex;gap:7px;align-items:center}
        .um-alert{padding:12px 16px;border-radius:7px;margin-bottom:16px}
        .um-success{background:#d4edda;color:#155724}.um-danger{background:#f8d7da;color:#721c24}.um-warning{background:#fff3cd;color:#856404}
        .um-modal{display:none;position:fixed;inset:0;background:#0008;z-index:9999;align-items:center;justify-content:center;padding:15px}
        .um-modal.open{display:flex}.um-modal-card{background:white;border-radius:12px;padding:22px;width:min(480px,100%);max-height:90vh;overflow:auto}
        .um-muted{font-size:12px;color:#777}
      </style>
      <h1 class="h3 mb-4">Users Management</h1>
      <?php if ($message !== ''): ?><div class="um-alert um-<?=h($messageType)?>"><?=h($message)?></div><?php endif; ?>
      <div class="um-panel">
        <div class="table-responsive">
          <table class="um-table">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($user = mysqli_fetch_assoc($usersResult)): ?>
              <tr>
                <td><?=h($user['id'])?></td><td><?=h($user['Name'])?></td><td><?=h($user['Email'])?></td><td><?=h($user['role'])?></td>
                <td><?=((int)$user['is_banned'] === 1 ? '<span class="badge badge-danger">Banned</span>' : '<span class="badge badge-success">Active</span>')?></td>
                <td><div class="um-actions">
                  <button type="button" class="um-btn um-edit" onclick='openEdit(<?=json_encode($user, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>)'>Edit</button>
                  <?php if (in_array($user['role'], ['vendor', 'customer'], true)): ?>
                    <form method="post" onsubmit="return confirm('تأكيد تغيير حالة الحظر؟');">
                      <input type="hidden" name="csrf_token" value="<?=h($csrf)?>"><input type="hidden" name="action" value="<?=((int)$user['is_banned'] === 1 ? 'unban' : 'ban')?>"><input type="hidden" name="user_id" value="<?=h($user['id'])?>">
                      <button class="um-btn" style="background:<?=((int)$user['is_banned'] === 1 ? '#1cc88a' : '#f6c23e')?>;color:#222" type="submit" <?=((int)$user['id']===$adminId?'disabled':'')?> ><?=((int)$user['is_banned'] === 1 ? 'Unban' : 'Ban')?></button>
                    </form>
                  <?php endif; ?>
                  <form method="post" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                    <input type="hidden" name="csrf_token" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?=h($user['id'])?>">
                    <button class="um-btn um-delete" type="submit" <?=((int)$user['id']===$adminId?'disabled title="لا يمكن حذف حسابك الحالي"':'')?> >Delete</button>
                  </form>
                </div></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        
      </div>
    </div>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>
</div>

<div class="um-modal" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editTitle">
  <div class="um-modal-card">
    <h2 class="h5 mb-3" id="editTitle">Edit User</h2>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update"><input type="hidden" name="user_id" id="editUserId">
      <label for="editName">Name</label><input class="um-input" id="editName" name="name" required maxlength="100">
      <label for="editEmail">Email</label><input class="um-input" id="editEmail" name="email" type="email" required maxlength="190">
      <label for="editRole">Role</label><select class="um-input" id="editRole" name="role"><option value="customer">Customer</option><option value="vendor">Vendor</option><option value="admin">Admin</option></select>
      <label for="editPassword">New password <span class="um-muted">(اختياري)</span></label><input class="um-input" id="editPassword" name="new_password" type="password" minlength="8" autocomplete="new-password" placeholder="اتركها فارغة للإبقاء على الحالية">
      <div class="um-actions mt-3"><button class="um-btn um-edit" type="submit">Save Changes</button><button class="btn btn-secondary" type="button" onclick="closeEdit()">Cancel</button></div>
    </form>
  </div>
</div>
<script>
function openEdit(user){
 document.getElementById('editUserId').value=user.id;
 document.getElementById('editName').value=user.Name||'';
 document.getElementById('editEmail').value=user.Email||'';
 document.getElementById('editRole').value=user.role||'customer';
 document.getElementById('editPassword').value='';
 document.getElementById('editModal').classList.add('open');
}
function closeEdit(){document.getElementById('editModal').classList.remove('open');}
document.getElementById('editModal').addEventListener('click',function(e){if(e.target===this)closeEdit();});
</script>
