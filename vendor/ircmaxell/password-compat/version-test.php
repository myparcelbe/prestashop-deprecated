<?php

require "lib/password.php";
echo "Test for functionality of compat library: " . (\MPBpostModule\PasswordCompat\binary\check() ? "Pass" : "Fail");
echo "\n";
