# Plugin Feedback pour SPIP

## Formulaire de Feedback pour SPIP

Le formulaire permet aux visiteurs de signaler un problème.
Il collecte en plus les informations techniques concernant le visiteur ($_SERVER et informations sur le navigateur et l'OS via javascript)

Utilisation :

### Dans un squelette

<pre>
[(#REM)
	Envoyer un feedback a support@example.org
]
#FORMULAIRE_FEEDBACK{support@example.org}

[(#REM)
	Envoyer un feedback a l'auteur 1
]
#FORMULAIRE_FEEDBACK{1}

[(#REM)
	Envoyer un feedback a l'auteur 1 et a support@example.org
]
#FORMULAIRE_FEEDBACK{#LISTE{1,support@example.org}}
</pre>


### Dans un article

<pre>

Envoyer un feedback a support@example.org
<formulaire|feedback|dest=support@example.org>

Envoyer un feedback a l'auteur 1
<formulaire|feedback|dest=1>

Envoyer un feedback a l'auteur 1 et a support@example.org
<formulaire|feedback|dest=1,support@example.org>
</pre>


Lorsque le visiteur est identifié par SPIP, on lui envoie aussi une copie de son message (mais sans les informations techniques)
(et uniquement dans ce cas pour eviter une utilisation abusive du formulaire)
