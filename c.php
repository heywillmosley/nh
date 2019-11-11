<?php
$url = 'http://biolinkconnect.com/KeyAPI.ashx';
$fields = 'Hello=World&Foo=Bar&Baz=Wombat';
$fields_string='';
foreach($fields as $key=>$value)
{
    $fields_string .= $key.'='.$value.'&'; 
    
}
rtrim($fields_string, '&');

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch,CURLOPT_POST, count($fields));
curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);