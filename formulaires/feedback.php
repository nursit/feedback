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

$GLOBALS['formulaires_no_spam'][] = 'feedback';

/**
 * Charger les valeurs par defaut de la saisie
 *
 * @param int|string|array $destinataires
 *   email, id_auteur ou liste mixe email/id_auteur
 * @return array
 */
function formulaires_feedback_charger_dist($destinataires = null){

	$valeurs = array(
		'nom' => '',
		'email' => '',
		'message' => '',
		'_nospam_encrypt' => true,
	);

	if (!empty($GLOBALS['visiteur_session']['id_auteur'])
		and $auteur = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . intval($GLOBALS['visiteur_session']['id_auteur']))){

		$valeurs['_nom'] = $auteur['nom'];
		$valeurs['_email'] = $auteur['email'];
	}

	include_spip('inc/feedback_browserinfos');
	$js = feedback_js_browserdetect('#browserInfos');
	$js .= "<input type='hidden' name='browserInfos' value='' id='browserInfos' />";

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
function formulaires_feedback_verifier_dist($destinataires = null){
	$erreurs = array();


	$oblis = array('message');
	if (empty($GLOBALS['visiteur_session']['id_auteur'])
		or !$auteur = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . intval($GLOBALS['visiteur_session']['id_auteur']))){
		$oblis[] = 'nom';
		$oblis[] = 'email';
	}

	foreach ($oblis as $obli){
		if (!_request($obli)){
			$erreurs[$obli] = _T('feedback:erreur_' . $obli . '_obligatoire');
		}
	}

	if (in_array('email', $oblis) AND !$erreurs['email']){
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
function formulaires_feedback_traiter_dist($destinataires = null){
	$message = _request('message');
	if (!empty($GLOBALS['visiteur_session']['id_auteur'])
		and $auteur = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . intval($GLOBALS['visiteur_session']['id_auteur']))){
		$email = $auteur['email'];
		$nom = $auteur['nom'];
		$id_auteur = $auteur['id_auteur'];
	} else {
		$email = _request('email');
		$nom = _request('nom');
		$id_auteur = 0;
	}

	$dest_emails = array();
	$dest_id = array();
	// envoyer un mail au webmestre si pas de destinataire explicite
	if (is_null($destinataires)){
		$dest_emails[] = $GLOBALS['meta']['email_webmaster'];
	} else {
		if (!is_array($destinataires)){
			$destinataires = explode(",", $destinataires);
		}
		foreach ($destinataires as $d){
			if (is_numeric($d) AND $e = sql_getfetsel('email', 'spip_auteurs', 'id_auteur=' . intval($d))){
				$dest_emails[] = $e;
				$dest_id[] = $d;
			} else {
				$dest_emails[] = $d;
			}
		}
	}


	include_spip('inc/notifications');
	$sujet = "[" . $GLOBALS['meta']['nom_site'] . "] Feedback";
	$texte = "Nom : $nom\nEmail : $email\n$message";

	include_spip('inc/feedback_browserinfos');
	$user_infos = feedback_collecte_browserinfos('browserInfos');
	$user_infos_print = feedback_presente_browserinfos($user_infos);

	// on laisse le from par defaut, car sinon ne passe pas dans les services de mail
	// mais on mets un Reply-To vers l'email du visiteur qui soumet le formulaire
	$head = "Reply-To: $email\n";
	notifications_envoyer_mails($dest_emails, $texte . "\n\n$user_infos_print", $sujet, '', $head);

	$ok = _T('feedback:message_bien_envoye');

	// envoyer une copie a l'emetteur, seulement si il est identifie
	if ($id_auteur){
		$texte = _T('feedback:texte_message_duplicata') . "\n\n" . $texte;
		notifications_envoyer_mails($email, $texte, $sujet);
		$ok .= "<br />" . _T('feedback:message_copie_envoyee', array('email' => $email));
	}

	spip_log($user_infos, 'feedback' . _LOG_DEBUG);

	// TODO enregistrer en base des messages
	// depuis $id_auteur vers $dest_id

	return array('message_ok' => $ok);
}
