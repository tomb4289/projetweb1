<?php
echo "Test file is working!";
echo "<br>Current directory: " . __DIR__;
echo "<br>Request URI: " . $_SERVER['REQUEST_URI'];
echo "<br>Script name: " . $_SERVER['SCRIPT_NAME'];
echo "<br>Document root: " . $_SERVER['DOCUMENT_ROOT'];
?>
