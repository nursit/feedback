<?php
/**
 * Plugin feedback
 *
 * (c) 2015 Nursit
 * Licence GPL
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS['formulaires_no_spam'][] = 'feedback';

/**
 * Charger les valeurs par defaut de la saisie
 *
 * @param int|string|array $destinataires
 *   email, id_auteur ou liste mixe email/id_auteur
 * @return array
 */
function formulaires_feedback_charger_dist($destinataires=null){

	$valeurs = array(
		'nom' => '',
		'email' => '',
		'message' => '',
	);

	if ($GLOBALS['visiteur_session']['id_auteur']
	  AND $auteur = sql_fetsel('*','spip_auteurs','id_auteur='.intval($GLOBALS['visiteur_session']['id_auteur']))){

		$valeurs['_nom'] = $auteur['nom'];
		$valeurs['_email'] = $auteur['email'];
	}

	// ajouter l'appel a WichBrowser
	$dir = protocole_implicite(url_absolue(find_in_path("lib/WhichBrowser/")));
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
	  Browsers = new WhichBrowser();
	  jQuery('#browserInfos').attr('value',JSON.stringify(Browsers));
  }
  jQuery(detectBrowserInit);
</script>
<input type='hidden' name='browserInfos' value='' id='browserInfos' />
snipet;

	$valeurs['_hidden'] = $js;

	return $valeurs;
}

/**
 * Verifier la saisie du message
 *
 * @param int|string|array $destinataires
 *   email, id_auteur ou liste mixe email/id_auteur
 * @return array
 */
function formulaires_feedback_verifier_dist($destinataires=null){
	$erreurs = array();


	$oblis = array('message');
	if (!$GLOBALS['visiteur_session']['id_auteur']
	  OR !$auteur = sql_fetsel('*','spip_auteurs','id_auteur='.intval($GLOBALS['visiteur_session']['id_auteur']))){
		$oblis[] = 'nom';
		$oblis[] = 'email';
	}

	foreach($oblis as $obli){
		if (!_request($obli))
			$erreurs[$obli] = _T('feedback:erreur_'.$obli.'_obligatoire');
	}

	if (in_array('email',$oblis) AND !$erreurs['email']){
		include_spip('inc/filtres');
		if (!email_valide(_request('email'))){
			$erreurs['email'] = _T('feedback:erreur_email_invalide');
		}
	}

	# Debug
	# $erreurs['message_erreur'] = nl2br(feedback_collecter_user_infos());

	// limiter le nombre mini et maxi de caracteres dans le message ?

	return $erreurs;
}


/**
 * Envoyer le message saisi aux destinataires.
 * Si pas de destinataire indique, utiliser l'adresse du webmestre du site
 *
 * @param int|string|array $destinataires
 *   email, id_auteur ou liste mixe email/id_auteur
 * @return array
 */
function formulaires_feedback_traiter_dist($destinataires=null){
	$message = _request('message');
	if ($GLOBALS['visiteur_session']['id_auteur']
	  AND $auteur = sql_fetsel('*','spip_auteurs','id_auteur='.intval($GLOBALS['visiteur_session']['id_auteur']))){
		$email = $auteur['email'];
		$nom = $auteur['nom'];
		$id_auteur = $auteur['id_auteur'];
	}
	else {
		$email = _request('email');
		$nom = _request('nom');
		$id_auteur = 0;
	}

	$dest_emails = array();
	$dest_id = array();
	// envoyer un mail au webmestre si pas de destinataire explicite
	if (is_null($destinataires)){
		$dest_emails[] = $GLOBALS['meta']['email_webmaster'];
	}
	else {
		if (!is_array($destinataires)){
			$destinataires = explode(",",$destinataires);
		}
		foreach($destinataires as $d){
			if (is_numeric($d) AND $e = sql_getfetsel('email','spip_auteurs','id_auteur='.intval($d))){
				$dest_emails[] = $e;
				$dest_id[] = $d;
			}
			else {
				$dest_emails[] = $d;
			}
		}
	}


	include_spip('inc/notifications');
	$sujet = "[".$GLOBALS['meta']['nom_site']."] Feedback";
 	$texte = "Nom : $nom\nEmail : $email\n$message";
	$user_infos = feedback_collecter_user_infos();
	// on laisse le from par defaut, car sinon ne passe pas dans les services de mail
	// mais on mets un Reply-To vers l'email du visiteur qui soumet le formulaire
	$head = "Reply-To: $email\n";
	notifications_envoyer_mails($dest_emails,$texte."\n\n$user_infos",$sujet,'',$head);

	$ok = _T('feedback:message_bien_envoye');

	// envoyer une copie a l'emetteur, seulement si il est identifie
	if ($id_auteur){
		$texte = _T('feedback:texte_message_duplicata')."\n\n".$texte;
		notifications_envoyer_mails($email,$texte,$sujet);
		$ok .= "<br />"._T('feedback:message_copie_envoyee',array('email'=>$email));
	}

	// TODO enregistrer en base des messages
	// depuis $id_auteur vers $dest_id

	return array('message_ok'=>$ok);
}

function feedback_collecter_user_infos(){

	include_spip('inc/filtres');
	include_spip('inc/texte');

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

	$infos = array();
	foreach($keys as $key){
		$infos[$key] = $_SERVER[$key];
	}
	$print = charger_filtre('print');

	$out = "\n\n------------\n# Server Infos\n";
	$out .= $print($infos);

	$out .= "\n\n------------\n# Browser Infos\n";
	$browser_infos = _request('browserInfos');
	$browser_infos = json_decode($browser_infos,true);

	$out .= $print(feedback_array_recursive_filter($browser_infos));

	$out = preg_replace(",<br[^>]*>,Uims","\n",$out);
	return $out;
}


function feedback_array_recursive_filter($tableau){
	if (!is_array($tableau)) return $tableau;
	$tableau = array_map('feedback_array_recursive_filter',$tableau);
	$tableau = array_filter($tableau,"feedback_not_null");
	return $tableau;
}

function feedback_not_null($val){
	return !is_null($val);
}
