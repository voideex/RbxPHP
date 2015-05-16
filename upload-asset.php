<?php
/*
	-READ ME-
	Modify `login_user` and `file_name_rs` to what you will use.
	* The script will automatically create a .txt file of `file_name_rs`, which will store the user's ROBLOSECURITY.
	** This is to avoid continuously logging in, which will activate CAPTCHA protection and break the script.
	** And also to increase performance by not obtaining ROBLOSECURITY again when it's still usable.
*/

// Login User Data
$login_user    = 'username=&password=';
$file_name_rs  = 'rs.txt';
$stored_rs     = (file_exists($file_name_rs) ? file_get_contents($file_name_rs) : '');

// Input
$asset_id   = $_GET['id'];
$post_body  = file_get_contents('php://input');
$asset_xml  = (ord(substr($post_body,0,1)) == 31 ? gzinflate(substr($post_body,10,-8)) : $post_body); // if gzipped, decode

// Sample ROBLOX XML: <roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.roblox.com/roblox.xsd" version="4"></roblox>


// --------------------------------------


// [Function] Get `ROBLOSECURITY` Cookie
function getRS() {
	global $login_user, $file_name_rs;

	$get_cookies = curl_init('https://www.roblox.com/newlogin');
	curl_setopt_array($get_cookies,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $login_user
		)
	);

	$rs = (preg_match('/(\.ROBLOSECURITY=.*?);/', curl_exec($get_cookies), $matches) ? $matches[1] : '');
	file_put_contents($file_name_rs, $rs, true);
	curl_close($get_cookies);

	return $rs;
}

// [Function] Upload Asset
function uploadAsset($rs)
{
	global $stored_rs, $asset_id, $asset_xml;

	$upload_xml = curl_init("http://www.roblox.com/Data/Upload.ashx?assetid=$asset_id");
	curl_setopt_array($upload_xml,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => array('User-Agent: Roblox/WinINet', "Cookie: $rs"),
			CURLOPT_POSTFIELDS => $asset_xml
		)
	);

	$response = curl_exec($upload_xml);
	$response_code = curl_getinfo($upload_xml, CURLINFO_HTTP_CODE);

	if ($response_code == 302) {
		$response = uploadAsset(getRS());
	}

	curl_close($upload_xml);

	return $response;
}


// --------------------------------------


// Upload Asset & Echo AVID
echo uploadAsset($stored_rs);