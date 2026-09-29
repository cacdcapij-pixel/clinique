<?php
require_once __DIR__ . '/inc/auth.php';
fermer_session();
header('Location: index.php');
exit;
