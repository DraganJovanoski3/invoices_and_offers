<?php
if (function_exists('gd_info')) {
    echo 'GD is enabled!<br><pre>';
    print_r(gd_info());
    echo '</pre>';
} else {
    echo 'GD is NOT enabled!';
} 