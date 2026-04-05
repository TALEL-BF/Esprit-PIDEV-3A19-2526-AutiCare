# Fiche de Test - Admin RDV / Seance / Planning

## 1. Objectif
Valider le bon fonctionnement des modules administration pour:
- CRUD (Creer, Lire, Modifier, Supprimer)
- Recherche
- Tri
- Filtres
- Indicateurs de performance

Perimetre:
- RDV
- Seance
- Planning

## 2. Prerequis
- Application demarree en environnement de test/dev.
- Compte admin connecte.
- Donnees minimales existantes dans les tables `rdv`, `seance`, `emploi_du_temps`.
- Navigateur desktop + test responsive mobile.

Pages a tester:
- `/admin/rdv`
- `/admin/seance`
- `/admin/planning`

## 3. Strategie de test
- Tester chaque fonctionnalite par module.
- Verifier le comportement nominal + cas limites.
- Valider les messages de succes et l'etat final du tableau.
- Verifier que le tri automatique s'applique au chargement.

---

## 4. Cas de test - RDV

### 4.1 CRUD RDV
1. Creation:
- Action: creer un RDV avec tous les champs valides.
- Resultat attendu: message de succes; le RDV apparait dans la liste.

2. Lecture:
- Action: verifier la ligne creee dans le tableau.
- Resultat attendu: type, date, statut, duree corrects.

3. Modification:
- Action: cliquer sur Modifier, changer statut et duree, enregistrer.
- Resultat attendu: message de succes; valeurs mises a jour dans la liste.

4. Suppression:
- Action: cliquer sur Supprimer et confirmer.
- Resultat attendu: message de succes; ligne retiree.

### 4.2 Recherche / Filtres / Tri RDV
1. Recherche:
- Action: saisir un mot-cle (type/statut/date).
- Resultat attendu: seules les lignes correspondantes restent visibles.

2. Filtre statut:
- Action: choisir Confirme, Planifiee, Annulee, Autres.
- Resultat attendu: affichage conforme au statut selectionne.

3. Filtre duree:
- Action: tester Courte, Moyenne, Longue.
- Resultat attendu: lignes filtrees selon les bornes minutes.

4. Tri:
- Action: tester tri date asc/desc, duree asc/desc, type A-Z/Z-A.
- Resultat attendu: ordre du tableau correct.

5. Reinitialiser:
- Action: cliquer Reinitialiser.
- Resultat attendu: champs vides + tri par defaut reapplique.

### 4.3 Indicateurs de performance RDV
Verifier:
- Taux de confirmation
- Taux d'annulation
- Duree moyenne

Resultat attendu:
- Les pourcentages sont coherents avec le nombre total de RDV.
- La duree moyenne correspond a la somme des durees / nombre de RDV.

---

## 5. Cas de test - Seance

### 5.1 CRUD Seance
1. Creation:
- Action: creer une seance valide.
- Resultat attendu: message succes; seance visible.

2. Lecture:
- Action: controler titre, date, jour, duree, statut.
- Resultat attendu: donnees exactes.

3. Modification:
- Action: modifier jour/statut/duree, enregistrer.
- Resultat attendu: mise a jour effective.

4. Suppression:
- Action: supprimer une seance.
- Resultat attendu: ligne retiree apres confirmation.

### 5.2 Recherche / Filtres / Tri Seance
1. Recherche:
- Action: rechercher par titre/date/jour/statut.
- Resultat attendu: filtrage instantane correct.

2. Filtres:
- Action: filtrer par Jour, Statut, Duree.
- Resultat attendu: intersection des filtres correcte.

3. Tri:
- Action: trier par Date, Duree, Titre.
- Resultat attendu: ordre conforme au choix.

4. Reinitialiser:
- Action: cliquer Reinitialiser.
- Resultat attendu: retour a la vue complete + tri defaut.

### 5.3 Indicateurs de performance Seance
Verifier:
- Taux de confirmation
- Taux d'annulation
- Duree moyenne

Resultat attendu:
- Valeurs cohérentes avec les lignes visibles et les donnees en base.

---

## 6. Cas de test - Planning

### 6.1 CRUD Planning
1. Creation:
- Action: creer une ligne avec annee, jour, tranche et affectation valide.
- Resultat attendu: ligne creee et visible.

2. Regle metier:
- Action: tenter de renseigner RDV et Seance en meme temps.
- Resultat attendu: blocage avec message d'erreur.

3. Modification:
- Action: modifier jour/tranche/affectation.
- Resultat attendu: sauvegarde correcte.

4. Suppression:
- Action: supprimer une ligne planning.
- Resultat attendu: suppression effective.

### 6.2 Recherche / Filtres / Tri Planning
1. Recherche:
- Action: rechercher par annee/jour/tranche/affectation.
- Resultat attendu: lignes correspondantes uniquement.

2. Filtres:
- Action: filtrer Jour + Tranche + Affectation.
- Resultat attendu: combinaison des filtres correcte.

3. Tri:
- Action: tri Annee asc/desc, Jour A-Z/Z-A.
- Resultat attendu: ordre correct.

4. Reinitialiser:
- Action: cliquer Reinitialiser.
- Resultat attendu: filtres vides + tri defaut.

### 6.3 Indicateurs de performance Planning
Verifier:
- Taux lignes RDV
- Taux lignes Seance
- Taux de planification valide

Resultat attendu:
- Calculs conformes au total des lignes planning.
- Nombre de lignes non definies correct.

---

## 7. Tests UX / Responsive
Pour chaque page:
- Verifier affichage desktop et mobile.
- Verifier lisibilite des cartes KPI.
- Verifier que la table reste utilisable avec plusieurs filtres actifs.

Resultat attendu:
- Interface stable, lisible et sans chevauchement.

## 8. Criteres de validation finale
La recette est validee si:
- Tous les cas CRUD passent.
- Recherche, filtres et tri fonctionnent sans erreur JS.
- Les indicateurs de performance affichent des valeurs coherentes.
- Aucun probleme Twig/PHP n'apparait dans les logs de test.

## 9. Evidence a collecter
- Captures ecran avant/apres creation, edition, suppression.
- Captures ecran des filtres et tris en action.
- Capture des indicateurs pour chaque module.
- Export/notes des anomalies detectees (si applicable).

---

## 10. Explication de la logique de travail

### 10.1 Avec quoi ce travail a ete fait
- Back-end: Symfony (Controller + Form + Validation + Routing)
- Base de donnees: Doctrine ORM (EntityManager + Repository)
- Front-end admin: Twig + JavaScript natif (recherche, filtres, tri)
- Securite action sensible: token CSRF pour suppression

### 10.2 Comment le travail a ete organise (grandes etapes)
1. Structurer le CRUD dans le controleur:
- Une route liste/ajout, une route edition, une route suppression pour chaque module (RDV, Seance, Planning).

2. Centraliser les regles de saisie dans les classes Form:
- Champs obligatoires, types attendus, limites de valeurs (exemple duree min/max).

3. Afficher les donnees et formulaires dans Twig:
- Tableau des lignes + formulaire d'ajout/modification + messages de succes.

4. Ajouter la partie experience utilisateur:
- Recherche instantanee.
- Filtres combinables.
- Tri manuel + tri automatique au chargement.
- Bouton reinitialiser.

5. Ajouter des indicateurs de performance:
- Calculs Twig (taux, moyenne, repartition) a partir des lignes chargees.

6. Verifier apres chaque changement:
- Lint Twig et verification de cohérence fonctionnelle.

---

## 11. Grandes lignes du code CRUD

### 11.1 Pattern CRUD utilise dans le controleur
Le meme schema est applique dans les actions de:
- RDV
- Seance
- Planning

Schema general:
1. Creer ou recuperer l'entite (new ou via id).
2. Construire le formulaire (createForm).
3. Lire la requete utilisateur (handleRequest).
4. Valider (isSubmitted + isValid).
5. Sauvegarder en base (persist/flush) ou mettre a jour (flush).
6. Afficher un message de succes et rediriger.

Suppression:
1. Methode POST.
2. Verification CSRF obligatoire.
3. remove + flush.
4. Redirection liste.

### 11.2 Specificite Planning (regle metier)
Dans Planning, la logique metier impose:
- Soit un RDV
- Soit une Seance
- Jamais les deux en meme temps

Controle applique:
- Les champs `rdvSelection` et `seanceSelection` sont lus.
- Les id sont injectes dans l'entite.
- Une verification logique (XOR) valide l'unicite du choix.
- Si invalide: erreur formulaire explicite, sans ecriture en base.

---

## 12. Grandes lignes du controle de saisie

### 12.1 Ou est fait le controle
Le controle de saisie est principalement fait dans les classes Form Symfony:
- contraintes `NotBlank`
- contraintes de type (DateTime, Integer)
- contraintes metier (`Range`, `Positive`, longueur max)

### 12.2 Exemples de regles de saisie appliquees
1. RDV:
- Date/heure obligatoire et format valide.
- Duree obligatoire, positive, entre 15 et 240 minutes.
- Type, statut, psychologue, patient obligatoires.

2. Seance:
- Titre obligatoire, longueur max 100.
- Date obligatoire et valide.
- Duree obligatoire, positive, entre 15 et 240 minutes.
- Jour et statut obligatoires.
- IDs (patient/professeur/cours) entiers positifs.
- Description optionnelle avec limite de longueur.

3. Planning:
- Jour et tranche obligatoires.
- Choix RDV/Seance controle par regle metier (XOR).

### 12.3 Pourquoi cette approche est robuste
- Validation serveur: impossible de contourner en modifiant le HTML.
- Messages d'erreur precis affiches dans le formulaire.
- Donnees invalides bloquees avant sauvegarde.

---

## 13. Logique Recherche / Tri / Filtres / Statistiques

1. Recherche:
- JavaScript lit le texte saisi.
- Chaque ligne du tableau est comparee.
- Affichage dynamique sans recharger la page.

2. Filtres:
- Chaque select ajoute une condition.
- Les conditions sont combinees (intersection logique).

3. Tri:
- Les lignes visibles sont triees (date, duree, texte).
- Tri automatique applique au chargement et apres reinitialisation.

4. Statistiques:
- Les compteurs/taux sont calcules a partir des collections Twig.
- Les KPI affichent un resume de performance exploitable en admin.

---

## 14. Explication detaillee du code des 3 CRUD

### 14.1 CRUD RDV
Le code RDV suit un schema Symfony classique:

1. Create (ajout):
- Action controleur: creation d'un objet RDV.
- Formulaire construit avec RdvType.
- Si formulaire soumis et valide: persist + flush.
- Message flash succes puis redirection vers la liste.

2. Read (lecture):
- Recuperation des RDV via repository Doctrine.
- Tri par id decroissant pour afficher les plus recents en haut.
- Envoi des donnees a la vue Twig.

3. Update (modification):
- Recuperation du RDV par id.
- Formulaire pre-rempli.
- Si valide: flush (mise a jour en base).
- Message succes + redirection.

4. Delete (suppression):
- Route POST uniquement.
- Verification du token CSRF.
- remove + flush.
- Redirection vers la liste RDV.

### 14.2 CRUD Seance
Le CRUD Seance reprend exactement la meme architecture que RDV:

1. Create:
- Nouvelle entite Seance + formulaire SeanceType.
- Validation puis persist + flush.

2. Read:
- Chargement des seances depuis Doctrine.
- Affichage dans le tableau admin.

3. Update:
- Edition d'une seance existante.
- Validation + flush.

4. Delete:
- Suppression protegee par CSRF.
- remove + flush + redirection.

### 14.3 CRUD Planning
Le CRUD Planning est similaire mais avec une regle metier supplementaire.

1. Create:
- Creation d'une ligne planning.
- Injection des choix dynamiques RDV/Seance dans le formulaire.
- Application de la selection utilisateur dans l'entite.
- Controle de la regle: choisir soit RDV soit Seance.
- Si conforme: persist + flush.

2. Read:
- Recuperation des lignes planning.
- Affichage avec affectation (RDV, Seance, ou Non defini).

3. Update:
- Chargement de la ligne existante.
- Rechargement des choix RDV/Seance.
- Revalidation de la regle metier puis flush.

4. Delete:
- Verification CSRF.
- remove + flush + retour liste.

### 14.4 Controle de saisie dans les 3 CRUD
Le controle de saisie est fait au niveau des FormType:
- Champs obligatoires (NotBlank)
- Types de donnees (date, entier)
- Contraintes metier (Range, Positive, longueur max)

Avantage:
- Les donnees incorrectes sont bloquees avant ecriture en base.
- Les messages d'erreur sont affiches clairement dans l'interface.

---

## 15. Lecture du code (explication concrete)

Cette section explique le code tel qu'il est ecrit dans:
- [src/Controller/AdminConsultationController.php](src/Controller/AdminConsultationController.php)
- [src/Form/RdvType.php](src/Form/RdvType.php)
- [src/Form/SeanceType.php](src/Form/SeanceType.php)
- [src/Form/EmploiDuTempsType.php](src/Form/EmploiDuTempsType.php)

### 15.1 RDV - chemin exact d'execution

1. Methode `rdv(...)` (ajout + liste)
- Le controleur cree l'objet:
	`\$rdv = new Rdv();`
- Il construit le formulaire:
	`\$form = \$this->createForm(RdvType::class, \$rdv, ['is_create' => true]);`
- Il lit les donnees envoyees:
	`\$form->handleRequest(\$request);`
- Condition principale:
	`if (\$form->isSubmitted() && \$form->isValid()) { ... }`
- Si valide:
	`\$entityManager->persist(\$rdv);`
	`\$entityManager->flush();`
	puis redirection.
- Sinon, il recharge la liste:
	`findBy([], ['id' => 'DESC'])`
	et affiche la page Twig.

2. Methode `editRdv(...)`
- Symfony charge l'entite RDV par id.
- Meme cycle formulaire (`createForm` -> `handleRequest` -> `isValid`).
- Ici on fait seulement `flush()` (objet deja gere par Doctrine).

3. Methode `deleteRdv(...)`
- Supprime uniquement en POST.
- Verifie CSRF:
	`isCsrfTokenValid('delete_rdv_' . \$rdv->getId(), ...)`
- Si ok: `remove` + `flush`.

### 15.2 Seance - meme pattern CRUD

1. Methode `seance(...)`
- `\$seance = new Seance();`
- `createForm(SeanceType::class, \$seance, ['is_create' => true])`
- `handleRequest`, puis `persist + flush` si valide.

2. Methode `editSeance(...)`
- Charge la seance cible.
- `flush()` si formulaire valide.

3. Methode `deleteSeance(...)`
- Verif CSRF.
- `remove + flush`.

### 15.3 Planning - CRUD + regle metier

1. Methode `planning(...)`
- Cree `\$emploi = new EmploiDuTemps();`
- Calcule l'annee scolaire avec `getCurrentSchoolYear()`.
- Injecte les choix RDV/Seance dans le formulaire via:
	`getEmploiFormOptions(\$entityManager)`
- Apres soumission valide, applique la selection:
	`applyEmploiSelection(\$form, \$emploi)`
- Controle metier critique:
	`isEmploiSelectionValid(\$emploi)`
	qui impose XOR (exactement un choix entre RDV et Seance).
- Si invalide: `addError(new FormError(...))` et pas d'ecriture.
- Si valide: `persist + flush`.

2. Methode `editPlanning(...)`
- Recharge les choix dynamiques RDV/Seance.
- Pre-remplit `rdvSelection` et `seanceSelection`.
- Reapplique le meme controle XOR avant `flush()`.

3. Methode `deletePlanning(...)`
- Meme securite CSRF + `remove + flush`.

### 15.4 Comment les FormType controlent la saisie

1. Dans [src/Form/RdvType.php](src/Form/RdvType.php)
- `NotBlank` sur type/statut/date/champs obligatoires.
- `Range(min:15, max:240)` sur la duree.
- `Positive` sur la duree.

2. Dans [src/Form/SeanceType.php](src/Form/SeanceType.php)
- `NotBlank` sur titre/date/jour/statut.
- `Length(max:100)` sur le titre.
- `Range + Positive` sur la duree.
- `Positive` sur `idAutiste`, `idProfesseur`, `idCours`.

3. Dans [src/Form/EmploiDuTempsType.php](src/Form/EmploiDuTempsType.php)
- `NotBlank` sur `jourSemaine` et `trancheHoraire`.
- `rdvSelection` et `seanceSelection` sont `mapped => false`.
- Le controle XOR ne se fait pas ici mais dans le controleur.

### 15.5 Resume ultra-court du flux technique

Flux unique utilise partout:
1. Request HTTP
2. createForm + handleRequest
3. Validation (FormType + regles metier)
4. Doctrine (persist/remove/flush)
5. Flash message
6. Redirect
7. Render Twig
