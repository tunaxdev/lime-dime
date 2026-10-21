<?php
include '_partials/session.php';
session_unset();
session_destroy();
header('Location: /limedime/login.php');
exit;
