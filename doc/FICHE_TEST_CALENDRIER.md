# 📑 FICHE DE TEST : CALENDRIER PROFESSIONNEL (AUTICARE)

Cette fiche détaille les fonctionnalités du nouveau calendrier intégré pour les **Séances** (Enseignants) et les **Rendez-vous** (Psychologues).

---

## 🎨 Design & Thème
- **Couleurs Interface** : Violet (`#8b5cf6`) pour les boutons, titres et icônes.
- **Couleurs Événements** : Rose (`#f472b6`) pour les blocs de rendez-vous et séances.
- **Animations** :
  - Apparition en fondu (Fade-in) au chargement.
  - Zoom léger et ombre violette au survol d'un événement.
  - Transitions fluides sur les boutons.

---

## 🛠️ Fonctionnalités à Tester

### 1. Navigation & Affichage
| Action | Résultat Attendu | Statut |
| :--- | :--- | :--- |
| **Bouton "Aujourd'hui"** | Le calendrier revient instantanément à la date du jour. | [ ] OK |
| **Sélecteur "Mois"** | Affiche la vue mensuelle complète. | [ ] OK |
| **Sélecteur "Semaine"** | Affiche le planning de la semaine avec les heures. | [ ] OK |
| **Flèches ( < / > )** | Passe au mois/semaine précédent ou suivant. | [ ] OK |

### 2. Gestion des Événements (Drag & Drop)
| Action | Résultat Attendu | Statut |
| :--- | :--- | :--- |
| **Déplacer un évènement** | Faites glisser un bloc rose sur une autre date/heure. | [ ] OK |
| **Confirmation (Auto)** | L'évènement reste à sa nouvelle position (Enregistré en DB via API). | [ ] OK |
| **Rafraîchissement** | Après avoir actualisé la page (F5), l'évènement est toujours là. | [ ] OK |

### 3. Intégration par Rôle
| Rôle | Page à tester | Résultat Attendu | Statut |
| :--- | :--- | :--- | :--- |
| **Psychologue** | `/admin/rdv` | Affiche uniquement les [RDV] en rose. | [ ] OK |
| **Enseignant** | `/admin/seance` | Affiche uniquement les [Séances] en rose. | [ ] OK |

---

## 📂 Configuration Technique
- **Contrôleur** : `src/Controller/CalendarController.php` (Gère l'API JSON).
- **Templates** : 
  - `templates/admin/pages/rdv.html.twig`
  - `templates/admin/pages/seance.html.twig`
- **Bibliothèque** : FullCalendar v6 (CDN).

---

## 🚀 Prochaines Étapes Suggérées
1. Ajouter une fenêtre modale (Popup) pour modifier les détails en cliquant sur un évènement.
2. Ajouter un filtre par Patient/Élève.
3. Synchronisation avec Google Calendar ou Outlook.

---
*Fiche générée par GitHub Copilot le 11 Avril 2026.*
