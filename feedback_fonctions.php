
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

/**
 * Inserer le js
 * @param $id
 * @return string
 */
function filtre_browserinfos_js_dist($id) {
	include_spip("inc/feedback_browserinfos");
	return feedback_js_browserdetect("#{$id}");
}

function filtre_browserinfos_print_dist($infos) {
	include_spip("inc/feedback_browserinfos");
	return feedback_presente_browserinfos($infos);
}

/**
 * Quelles sont les saisies qui se débrouillent toutes seules, sans le _base commun.
 *
 * @return array Retourne un tableau contenant les types de saisies qui ne doivent pas utiliser le _base.html commun
 */
function feedback_saisies_autonomes($saisies_autonomes) {
	$saisies_autonomes[] = 'browserinfos';

	return $saisies_autonomes;
}

/**
 * Collecter les infos $_SERVER PHP en plus lors de la verification
 * @param $flux
 * @return mixed
 */
function feedback_saisies_verifier($flux) {
	$saisies = $flux['args']['saisies'];
	foreach ($saisies as $key => $saisie){
		if ($saisie['saisie'] === 'browserinfos') {
			include_spip("inc/feedback_browserinfos");
			$infos = feedback_collecte_browserinfos($key);
			if ($infos) {
				set_request($key, $infos);
			}
		}
	}

	return $flux;
}