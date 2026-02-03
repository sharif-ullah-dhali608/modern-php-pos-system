<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['auth_user']['user_id'];
$role = $_SESSION['auth_user']['role_as'];
$current_store_id = isset($_SESSION['store_id']) ? $_SESSION['store_id'] : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$limit = 5; // Default limit for "Latest"
if (!empty($search)) {
    $limit = 100; // Higher limit when searching
}

$stores = [];

if ($role === 'admin') {
    // Super Admin: Can see ALL stores
    $sql = "SELECT id, store_name, status, created_at, city_zip, address FROM stores WHERE status = 1";
    if (!empty($search)) {
        $sql .= " AND store_name LIKE '%$search%'";
    }
    // "Latest" ordering
    $sql .= " ORDER BY id DESC LIMIT $limit";
} else {
    // Staff: Only assigned stores
    // First get assigned IDs
    $map_q = mysqli_query($conn, "SELECT store_id FROM user_store_map WHERE user_id = '$user_id'");
    $assigned_ids = [];
    while ($r = mysqli_fetch_assoc($map_q)) {
        $assigned_ids[] = $r['store_id'];
    }
    
    if (empty($assigned_ids)) {
        echo json_encode(['success' => true, 'stores' => []]);
        exit;
    }
    
    $ids_str = implode(',', $assigned_ids);
    $sql = "SELECT id, store_name, status, created_at, city_zip, address FROM stores WHERE id IN ($ids_str) AND status = 1";
    if (!empty($search)) {
        $sql .= " AND store_name LIKE '%$search%'";
    }
    $sql .= " ORDER BY id DESC LIMIT $limit";
}

$query = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($query)) {
    $row['is_active'] = ($row['id'] == $current_store_id);
    // Add a generated logo/initial
    $row['initial'] = strtoupper(substr($row['store_name'], 0, 1));
    $stores[] = $row;
}

echo json_encode([
    'success' => true,
    'stores' => $stores,
    'role' => $role
]);
