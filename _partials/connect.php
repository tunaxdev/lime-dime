<?php
$conn = mysqli_connect("localhost", "root", "", "lime_dime_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
