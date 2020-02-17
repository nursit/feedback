<?php
/**
 * Plugin feedback
 *
 * (c) 2015-2020 Nursit
 * Licence GPL
 *
 */

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}



function feedback_js_browserdetect($selecteur_dom) {
	// ajouter l'appel a WichBrowser
	$dir = protocole_implicite(url_absolue(find_in_path("lib/WhichBrowser/Server/")));
	$js = <<<snipet
<script>
	(function(){var p=[],w=window,d=document,e=f=0;p.push('ua='+encodeURIComponent(navigator.userAgent));e|=w.ActiveXObject?1:0;e|=w.opera?2:0;e|=w.chrome?4:0;
	e|='getBoxObjectFor' in d || 'mozInnerScreenX' in w?8:0;e|=('WebKitCSSMatrix' in w||'WebKitPoint' in w||'webkitStorageInfo' in w||'webkitURL' in w)?16:0;
	e|=(e&16&&({}.toString).toString().indexOf("\\n")===-1)?32:0;p.push('e='+e);f|='sandbox' in d.createElement('iframe')?1:0;f|='WebSocket' in w?2:0;
	f|=w.Worker?4:0;f|=w.applicationCache?8:0;f|=w.history && history.pushState?16:0;f|=d.documentElement.webkitRequestFullScreen?32:0;f|='FileReader' in w?64:0;
	p.push('f='+f);p.push('r='+Math.random().toString(36).substring(7));p.push('w='+screen.width);p.push('h='+screen.height);var s=d.createElement('script');
	s.src='{$dir}detect.js?' + p.join('&');d.getElementsByTagName('head')[0].appendChild(s);})();
	function detectBrowserInit(){
		if (typeof WhichBrowser=="undefined") {setTimeout(detectBrowserInit,250);return}
		var result = new WhichBrowser();
		result = JSON.stringify(result);
		result = JSON.parse(result);
		result.device.screenWidth = screen.width;
		result.device.screenHeight = screen.height;
		jQuery('{$selecteur_dom}').attr('value',JSON.stringify(result));
	}
	jQuery(detectBrowserInit);
</script>
snipet;

	return $js;
}

function feedback_collecte_browserinfos($name) {
	$infos = _request($name);
	if (is_null($infos)) {
		return $infos;
	}
	$infos = json_decode($infos, true);
	if (!isset($infos['server'])) {
		$infos = [
			'client' => $infos,
			'server' => []
		];

		$keys = array(
			"HTTP_USER_AGENT",
			"HTTP_ACCEPT",
			"HTTP_ACCEPT_LANGUAGE",
			"HTTP_ACCEPT_ENCODING",
			"HTTP_DNT",
			"HTTP_X_FORWARDED_FOR",
			"REMOTE_ADDR",
			"REQUEST_URI",
			"HTTP_REFERER",
			"HTTP_COOKIE",
		);

		foreach ($keys as $key){
			$infos['server'][$key] = $_SERVER[$key];
		}

	}
	$infos = json_encode($infos);

	return $infos;
}

function feedback_presente_browserinfos($infos) {
	include_spip('inc/filtres');
	include_spip('inc/texte');

	if (is_string($infos)) {
		$infos = json_decode($infos, true);
	}
	if (!$infos) {
		return "";
	}

	$infos = feedback_array_recursive_filter($infos);
	$infos = array_filter($infos, "feedback_not_null");

	$print = charger_filtre('print');

	$out = "";
	if (!empty($infos['client'])) {
		$out .= "\n\n------------\n# Browser Infos\n";
		$out .= $print($infos['client']);
		if (!empty($infos['server'])) {
			$out .= "\n\n------------\n# Server Infos\n";
			$out .= $print($infos['server']);
		}
	}
	else {
		$out .= $print($infos);
	}

	$out = preg_replace(",<br[^>]*>,Uims", "\n", $out);
	$out = trim($out) . "\n";
	return $out;
}


function feedback_array_recursive_filter($tableau){
	if (!is_array($tableau)){
		return $tableau;
	}
	$tableau = array_map('feedback_array_recursive_filter', $tableau);
	$tableau = array_filter($tableau, "feedback_not_null");
	return $tableau;
}

function feedback_not_null($val){
	return !is_null($val);
}
