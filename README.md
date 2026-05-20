# Stop Spammers pour Piwigo

Plugin Piwigo destiné à réduire le spam dans les commentaires et dans le plugin
Contact Form. Cette version combine plusieurs protections :

- vérification d'adresse IP avec StopForumSpam ;
- cache local des IP bloquées ;
- seuil de confiance configurable ;
- whitelist d'IP ;
- limite du nombre de liens dans le message ;
- blocage par mots-clés ;
- honeypot HTML pour le formulaire de contact.

## Installation

1. Copier le dossier du plugin dans `plugins/stop_spammers`.
2. Activer le plugin depuis l'administration Piwigo.
3. Vérifier que la table `*_stop_spammers` est créée ou mise à jour.

Le fichier `include/install.inc.php` crée la table de cache des IP bloquées.
Le champ `ip` utilise `varchar(45)` pour accepter les IPv4 et les IPv6.

## Configuration Piwigo

Ajouter les réglages dans `local/config/config.inc.php`.

```php
$conf['stop_spammers_sfs_threshold'] = 20;
$conf['stop_spammers_cache_days'] = 30;
$conf['stop_spammers_whitelist'] = array(
  '127.0.0.1',
  '::1',
  // 'TON.IP.PUBLIQUE.ICI',
);

$conf['stop_spammers_max_links'] = 3;
$conf['stop_spammers_keywords'] = array(
  'ai ads',
  'ai content',
  'generate ai',
  'publish easily',
  'free tools',
  'free plan',
  'traffic',
  'revenue',
  'backlinks',
  'seo',
  'marketing',
  'customers no longer',
  'static websites',
  'no obligations',
  'unsubscribe',
  'opt-out',
  'bit.ly',
  'systeme.io',
);
```

Notes :

- `stop_spammers_sfs_threshold` : seuil de confiance StopForumSpam. Plus il est bas, plus le filtrage est strict.
- `stop_spammers_cache_days` : durée pendant laquelle une IP rejetée reste bloquée localement.
- `stop_spammers_whitelist` : IP à ne jamais bloquer.
- `stop_spammers_max_links` : nombre de liens à partir duquel le message est rejeté. Avec `3`, un message contenant 3 liens ou plus est rejeté.
- `stop_spammers_keywords` : mots ou expressions qui provoquent un rejet si elles apparaissent dans le message.

## Honeypot pour Contact Form

Le contrôle serveur vérifie le champ POST `website_url`.
Ce champ est maintenant injecté automatiquement dans le template ContactForm par un prefilter Smarty.

Si le template ContactForm contient déjà ce champ manuellement, il peut être supprimé :
le plugin le génère automatiquement.

Un utilisateur normal ne voit pas ce champ. Beaucoup de robots le remplissent
quand même ; dans ce cas le message est rejeté.

## Fonctionnement

Les contrôles sont exécutés dans `stop_spammers_checks()` avant l'appel distant
à StopForumSpam :

1. si `website_url` est rempli, le message est rejeté ;
2. le contenu du message est récupéré depuis le commentaire ou depuis `$_POST` ;
3. les liens sont comptés, y compris les domaines sans `https://` comme `example.com` ;
4. les mots-clés configurés sont recherchés ;
5. StopForumSpam est consulté si les contrôles locaux n'ont pas déjà rejeté le message.

Le plugin écoute les hooks Piwigo suivants :

```php
user_comment_check
contact_form_check
```

Il protège donc les commentaires Piwigo et les messages envoyés avec ContactForm,
à condition que ContactForm déclenche bien `contact_form_check`.

## Tests rapides

Après déploiement :

1. Envoyer un message normal avec peu ou pas de liens : il doit passer.
2. Envoyer un message avec 3 domaines ou plus, par exemple :

   ```text
   septanteneuf.ch officia.ch google.com apple.ch
   ```

   Avec `$conf['stop_spammers_max_links'] = 3`, il doit être rejeté.

3. Envoyer un message contenant un mot-clé comme `systeme.io` ou `backlinks` :
   il doit être rejeté.
4. Tester le honeypot en forçant `website_url` dans le formulaire : il doit être rejeté.

## Dépannage

- Si le filtrage par liens ne semble pas fonctionner, vérifier que la version de
  `main.inc.php` contient bien le compteur qui détecte aussi les domaines nus.
- Si le honeypot ne fonctionne pas, vérifier que `website_url` est présent dans
  le HTML final du formulaire ContactForm.
- Si une IP légitime est bloquée, l'ajouter dans `stop_spammers_whitelist`.
- Si le site affiche une erreur PHP après modification de `config.inc.php`,
  vérifier qu'il ne contient que du PHP valide et pas de texte explicatif collé
  en dehors de commentaires.

