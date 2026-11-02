# Journal des changements

## 1.6.4 — 2026-11-02

**Après la mise à jour : cliquer « Mettre à jour » dans Configuration → Plugins.**

- Mails texte sans pièce jointe envoyés par la file d'attente de GLPI : plus d'avertissement « Undefined array
  key "attach_documents_to_notifications_for_anonymous" » quand ce réglage manque dans la base de GLPI (vu en
  production le 02/11/2026).
- Mise à jour lancée en console (`bin/console plugin:install`) : la version était lue dans la session, absente
  en console, et les migrations ne jouaient pas. Elle est lue désormais dans la constante du plugin (même
  valeur sur le web), comparée avec `version_compare`.
- Dépôt : workflows modèles retirés (Codacy, CodeQL, Defender, Fortify, SonarCloud, SonarQube, PHPMD,
  rust-clippy) : sans configuration ni secrets, ils échouaient chaque semaine. Remplacés par la CI.
- CI GitHub : syntaxe sur PHP 8.2 à 8.5, installation réelle dans GLPI 11.0.12 (neuve et mise à jour depuis
  la release précédente), chargement de chaque classe, parcours des pages et de leurs onglets.

## 1.6.3 — 2026-10-06

- **Garanties d'envoi** : si la file d'attente est indisponible (écriture refusée, erreur de base), le mail part en direct comme avant — la file n'empêche jamais un envoi. Un mail envoyé aussitôt est mis en file avec une heure d'envoi décalée de 5 minutes, pour que la tâche « queuednotification » ne l'envoie pas une seconde fois pendant l'envoi immédiat.
- **Mail d'identifiants du nouveau demandeur envoyé par la file d'attente des notifications de GLPI**, aussitôt :
  la réponse à l'écran reste immédiate. En cas d'échec, le mail reste en file et GLPI le renvoie automatiquement
  (message à l'écran). Le mot de passe figure dans le mail : il reste dans la table de la file de GLPI jusqu'à son
  nettoyage. Visible dans Administration → File d'attente des notifications. Aucune migration de base.
