<?php

session_start();

/*
|--------------------------------------------------------------------------
| STORE LOGOUT
|--------------------------------------------------------------------------
|
| This logout is ONLY for the Store.
| It does not affect Admin/Vendor sessions.
|
*/


/*
|--------------------------------------------------------------------------
| REMOVE STORE SESSION DATA
|--------------------------------------------------------------------------
*/

unset($_SESSION['store_user_id']);
unset($_SESSION['store_user_name']);
unset($_SESSION['store_user_email']);
unset($_SESSION['store_user_role']);


/*
|--------------------------------------------------------------------------
| REDIRECT TO STORE
|--------------------------------------------------------------------------
|
| logout.php موجود داخل:
| ShopMind-main/account/
|
| و index.php موجود داخل:
| ShopMind-main/frontend/
|
*/

header("Location: ../frontend/index.php");
exit();

?>