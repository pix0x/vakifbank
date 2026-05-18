<?php
$first = rand(1,9);
$d2 = rand(0,9); $d3 = rand(0,9); $d4 = rand(0,9);
$d5 = rand(0,9); $d6 = rand(0,9); $d7 = rand(0,9);
$d8 = rand(0,9); $d9 = rand(0,9);
$oddSum = $first + $d3 + $d5 + $d7 + $d9;
$evenSum = $d2 + $d4 + $d6 + $d8;
$d10 = ($oddSum * 7 - $evenSum) % 10;
$sum10 = $first + $d2 + $d3 + $d4 + $d5 + $d6 + $d7 + $d8 + $d9 + $d10;
$d11 = $sum10 % 10;
echo $first . $d2 . $d3 . $d4 . $d5 . $d6 . $d7 . $d8 . $d9 . $d10 . $d11;
