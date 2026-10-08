# Journal des changements

## 1.6.3 — 2026-10-06

- **Garanties d'envoi** : si la file d'attente est indisponible (écriture refusée, erreur de base), le mail part en direct comme avant — la file n'empêche jamais un envoi. Un mail envoyé aussitôt est mis en file avec une heure d'envoi décalée de 5 minutes, pour que la tâche « queuednotification » ne l'envoie pas une seconde fois pendant l'envoi immédiat.
- **Mail d'identifiants du nouveau demandeur envoyé par la file d'attente des notifications de GLPI**, aussitôt :
  la réponse à l'écran reste immédiate. En cas d'échec, le mail reste en file et GLPI le renvoie automatiquement
  (message à l'écran). Le mot de passe figure dans le mail : il reste dans la table de la file de GLPI jusqu'à son
  nettoyage. Visible dans Administration → File d'attente des notifications. Aucune migration de base.
