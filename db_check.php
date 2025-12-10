<?php
require_once 'config.php';

header('Content-Type: application/json');

$roles = ['student','instructor','admin','course_team','dept_head','data_analyst','office_manager'];
$result = [];
foreach($roles as $r){
    $q = "SELECT COUNT(*) as cnt FROM users WHERE role = '".mysqli_real_escape_string($conn,$r)."'";
    $res = mysqli_query($conn, $q);
    if($res){ $row = mysqli_fetch_assoc($res); $result[$r] = (int)$row['cnt']; } else { $result[$r] = null; }
}

$total = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users");
$totalCount = $total ? (int)mysqli_fetch_assoc($total)['cnt'] : null;

echo json_encode(['success'=>true, 'total_users'=>$totalCount, 'by_role'=>$result]);
