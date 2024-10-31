<?php

// ==============================================================================================
// Licensed under the GPLv2 license
// ==============================================================================================
// @author     WEBO Software (http://www.webogroup.com/)
// @version    0.1
// @copyright  Copyright &copy; 2013 Openstat, All Rights Reserved
// ==============================================================================================
/* get counter code on installation */
function openstat_counter_api_add ($email, $website = '', $cms = '') {
	$code = '';
/* define website - required for registered users */
	$website = empty($website) ? empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST'] : $website;
/* no installation for current domain */
	if (empty($_COOKIE['openstat_counter_id'])) {
/* API calls, try to register and get ID with the first call */
		$ch = @curl_init('https://www.openstat.ru/rest/v0.3/simple_add');
		if ($ch) {
			@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			@curl_setopt($ch, CURLOPT_POST, true);
			@curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
			@curl_setopt($ch, CURLOPT_POSTFIELDS, 'email=' . $email . '&format=json&client_info=' . $cms);
			@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = @curl_exec($ch);
			$info = @curl_getinfo($ch);
			@curl_close($ch);
/* get counter ID, new user */
			if ($info['http_code'] == '201') {
				$user = json_decode($response);
				$counter_id = $user['msg'];
/* get counter ID, existing user, creating new counter */
			} elseif ($info['http_code'] == '409') {
				$ch = @curl_init('https://www.openstat.ru/rest/v0.3/counters');
				if ($ch) {
					@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					@curl_setopt($ch, CURLOPT_POST, true);
					@curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
					@curl_setopt($ch, CURLOPT_POSTFIELDS, '{"owner":"' . $email . '","site_url":"' . $website . '","client_info":"' . $cms . '"}');
					@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					@curl_setopt($ch, CURLOPT_HEADER, true);
					$response = @curl_exec($ch);
					$info = @curl_getinfo($ch);
					@curl_close($ch);
					if (!empty($response) && $info['http_code'] == '201') {
						$counter_id = preg_replace("!.*Location:[^\r\n]+/([0-9]+).*!is", "$1", $response);
					}
				}
			}
		}
/* get counter ID from cookie */
	} else {
		$counter_id = $_COOKIE['openstat_counter_id'];
	}
	if (!empty($counter_id)) {
/* set for 1 year cookie with counter ID */
		setcookie('openstat_counter_id', $counter_id, time() + 31536000, "/");
		$_COOKIE['openstat_counter_id'] = $counter_id;
/* get counter code by ID */
		
	}
	return $counter_id;
}
/* get JavaScript code from Openstat */
function openstat_counter_api_counter () {
	$code = '';
	$ch = curl_init('https://www.openstat.ru/rest/v0.3/counter/' . $_COOKIE['openstat_counter_id'] . '/code');
	if ($ch) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		if (!empty($response)) {
			$code = $response;
		}
	}
	return $code;
}
/* return counter code */
function openstat_counter_api_code ($id, $no_script = 0) {
	$base = 'var openstat={counter:' . $id . ',next:openstat};(function(d,t){var j=d.createElement(t);j.async=true;j.type="text/javascript";j.src="//openstat.net/cnt.js";var s=d.getElementsByTagName(t)[0];s.parentNode.insertBefore(j,s)})(document,"script")';
	return $no_script ? $base : '<!--Openstat--><span id="openstat' . $id . '"></span><script type="text/javascript">' . $base . '</script><!--/Openstat-->';
}