<?php
/**
 * 旧URL互換リダイレクト → /hinata/member.php
 */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
header('Location: /hinata/member.php?id=' . $id, true, 301);
exit;
