<?php

$file = $_GET['file'];
if (!is_file("./$file")) exit;

require_once('markdown.php');

echo Markdown(file_get_contents($file));
