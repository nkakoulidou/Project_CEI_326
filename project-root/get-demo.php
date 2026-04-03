<?php

$keyword=$_GET['keyword'];
$year=(int)$_GET['year'];
$role=$_GET['role'];

if($keyword === '' || $year === 0 || $role === ''){
    echo"";
    exit
}

echo "keyword:".keyword."<br>";
echo "year:".year."<br>";
echo "role:".role."<br>";

