# RT (Route Time / Chrono)

Plugin GLPI pour gérer le temps de trajet et un chronomètre opérationnel sur les tickets. Il ajoute aussi un flux de création de demandeur (avec envoi d'identifiants optionnel) via un endpoint métier utilisé dans certains environnements.

Le plugin sert à tracer le temps non purement technique (déplacement), à fluidifier le suivi de l'intervention et à standardiser certaines actions depuis l'interface ticket.

## Ce que fait le plugin (lecture rapide)

- Ajoute un bloc / onglet RT dans les tickets (selon configuration).
- Permet d'activer un chronomètre (play/pause/affichage) côté ticket.
- Permet d'enregistrer du temps de trajet lié au ticket / aux tâches.
- Peut s'appuyer sur OpenRouteService (ORS) pour les fonctions cartographiques/calculs.
- Expose un flux `traitement.php` de création de demandeur avec envoi mail optionnel (selon réglage).

## Fonctionnement (parcours type)

1. L'administrateur configure l'affichage du chrono et/ou du temps de trajet.
2. L'administrateur renseigne la clé ORS si les fonctions de route sont utilisées.
3. Le technicien ouvre un ticket et utilise le chrono / saisit le temps de trajet.
4. Les informations RT sont visibles dans le ticket et peuvent être réutilisées par d'autres plugins (ex: `rp`).
5. Si votre processus le nécessite, le flux de création de demandeur peut envoyer un email basé sur un gabarit configuré.

## Configuration plugin (ce que chaque zone active)

### Chrono (affichage et ergonomie)

- `showtimer`: affiche/masque le chrono.
- `showactivatetimer`: affiche le bouton d'activation du chrono.
- `showPlayPauseButton`: affiche les boutons Play/Pause.
- Couleurs chrono/boutons: permet d'adapter l'affichage au thème interne de votre interface.

### Temps de trajet

- `showtime`: active l'affichage du temps de trajet.
- `fromonglettrajet`: force/privilégie l'usage depuis l'onglet trajet (selon votre workflow).
- `ORS_API_KEY`: clé OpenRouteService, utile pour les fonctions liées aux trajets/calculs automatiques si utilisées.

### Email lors de la création de demandeur

- `mail`: active/désactive l'envoi d'email lors du flux de création.
- `gabarit` (`NotificationTemplate`): modèle de notification utilisé.
- Balises supportées (selon implémentation du plugin): ex `##id.user##`, `##user.password##`, etc.

Cela permet d'automatiser la communication d'identifiants ou d'informations de compte après création.

## Prérequis

- GLPI 10/11 (selon version du plugin installée)
- PHP compatible GLPI
- OpenRouteService (facultatif) si vous utilisez les fonctions de route/cartographie
- Plugin `rp` optionnel si vous voulez réutiliser le temps de trajet dans les PDF RP

## Droits / profils

- Les écrans RT suivent les droits du plugin RT.
- L'accès à certaines fonctions (chrono, zones injectées dans ticket) dépend des droits de profil RT.
- Le flux `front/traitement.php` contrôle l'authentification, les droits et les entrées reçues.

## Architecture (résumé court)

- Une configuration plugin pilote les options chrono / trajet / email.
- Le plugin injecte des éléments d'interface dans les tickets (PHP + JS) selon les droits.
- Les données RT sont stockées et réaffichées dans les tickets / tâches.
- `front/traitement.php` gère un traitement métier spécifique (création demandeur + réponse JSON).

## Vérifications rapides après mise à jour

- Ouvrir un ticket et vérifier l'affichage du bloc RT.
- Tester chrono (affichage, play/pause).
- Ajouter/modifier du temps de trajet.
- Vérifier l'absence d'erreur JS dans la console.
- Tester le flux `traitement.php` si vous l'utilisez en production.
