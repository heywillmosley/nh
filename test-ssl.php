<?php


if (isset($_POST['email'])) {
  echo '<h1>Email is: '.$_POST['email'].'</h1>';
}
if (isset($_POST['pass'])) {
  echo '<h1>Password is: '.$_POST['pass'].'</h1>';
}
if (isset($_POST['pcid'])) {
  echo '<h1>PC Identifier is: '.$_POST['pcid'].'</h1>';
}

echo '<h3>Tests complete</h3>';