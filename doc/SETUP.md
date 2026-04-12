# ⚡ SETUP NOTIFICATIONS - Configuration Finale

## 📋 Fichiers créés (3 essentiels)

✅ **Backend Services:**
- `src/Service/WindowsNotificationService.php` - Alerts système Windows
- `src/Service/WebNotificationService.php` - Notifications web toast

✅ **Frontend:**
- `public/js/toast-notifications.js` - Gestionnaire toasts JavaScript

✅ **Templates:**
- `templates/partials/notifications_web.html.twig` - Intégration toasts

---

## 🔧 Configuration nécessaire

### Étape 1️⃣ : Modifier AdminSeanceController

Le fichier `src/Controller/AdminSeanceController.php` a déjà été modifié ✅

**Vérifier que les services sont injectés:**
```php
public function seance(
    Request $request, 
    EntityManagerInterface $entityManager, 
    SeanceRepository $seanceRepository, 
    WindowsNotificationService $windowsNotifier,      // ✅
    WebNotificationService $webNotifier               // ✅
): Response
```

### Étape 2️⃣ : Modifier le template admin

Le fichier `templates/admin/base_admin.html.twig` a déjà été modifié ✅

**Vérifier qu'il contient:**
```twig
<!-- Toast Notifications JavaScript -->
<script src="{{ asset('js/toast-notifications.js') }}"></script>

<!-- Display web notifications from flash messages -->
{% include 'partials/notifications_web.html.twig' %}
```

### Étape 3️⃣ : C'est tout! ✅

**Aucune autre configuration requise !**

---

## 🚀 Pour tester

```bash
# 1. Démarrer le serveur
php bin/console server:start

# 2. Aller à http://localhost:8000/admin/seance

# 3. Créer une séance

# 4. Regarder :
#    - Popup Windows 🪟 (coin bas droit du PC)
#    - Toast web 🌐 (coin haut droit du navigateur)
```

---

## 📁 Structure finale

```
src/
  Service/
    ✅ WindowsNotificationService.php
    ✅ WebNotificationService.php
    
  Controller/
    ✅ AdminSeanceController.php (MODIFIÉ)

public/js/
  ✅ toast-notifications.js

templates/
  admin/
    ✅ base_admin.html.twig (MODIFIÉ)
  partials/
    ✅ notifications_web.html.twig
```

---

## 💡 Comment ça marche

### Création d'une séance

```
Admin remplit formulaire
    ↓
Admin clique "Soumettre"
    ↓
Séance créée en base ✅
    ↓
WindowsNotificationService.notifyAdminNewSeance()
  └─→ Popup Windows s'affiche 🪟
    ↓
WebNotificationService.prepareNotificationData()
  └─→ Toast web préparé
    ↓
Flash message stocké +redirection
    ↓
Admin redirigé
    ↓
Toast web s'affiche dans le navigateur 🌐 (5 secondes)
```

---

## 🔐 Sécurité

✅ Pas de dépendances externes  
✅ Caractères PowerShell échappés  
✅ Variables HTML échappées  
✅ Pas de XSS possible  
✅ Gestion d'erreurs robuste  

---

## 📊 C'est tresminimaliste

| Aspect | Statut |
|--------|--------|
| Fichiers créés | 3 services/frontend |
| Fichiers modifiés | 2 (Controller + Template) |
| Dépendances externes | 0 ❌ |
| Configuration requise | 0 ❌ |
| Installation | 0 commandes ❌ |
| Migration BD | 0 ❌ |

---

## ✨ Notifications affichées

### ✅ Création de séance
```
Windows: 🔔 Nouvelle Séance Ajoutée
         Séance: [Titre]
         📅 Date: [Date]
         ⏰ Heure: [Heure]
         ⏱️ Durée: [Durée] min

Web Toast: Vert (success)
          Titre: Nouvelle Séance Créée
          Message: Une nouvelle séance a été ajoutée avec succès
          Détails: Titre, Date, Heure, Durée
          Durée: 5 secondes
```

### ✏️ Modification de séance
```
Windows: ✏️ Séance Modifiée
         Séance: [Titre]
         📅 Date: [Date]
         ⏰ Heure: [Heure]
         ⏱️ Durée: [Durée] min

Web Toast: Bleu (info)
          Titre: Séance Modifiée
          Message: La séance a été mise à jour avec succès
          Détails: Titre, Date, Heure, Durée
          Durée: 5 secondes
```

---

## 🎯 Test rapide

- [ ] Aller à http://localhost/admin/seance
- [ ] Créer une séance (remplir tous les champs)
- [ ] Cliquer "Ajouter" ou "Enregistrer"
- [ ] **Voir popup Windows 🪟** (coin bas droit PC)
- [ ] **Voir toast web 🌐** (coin haut droit navigateur)
- [ ] Toast disparaît après 5s
- [ ] Modifier une séance
- [ ] Voir toast différent (bleu au lieu de vert)

✅ Si tout marche → Configuration correcte!

---

## 🛠️ Personnalisation

### Modifier les messages Windows

Fichier: `src/Service/WindowsNotificationService.php`

```php
public function notifyAdminNewSeance(Seance $seance): bool
{
    $titre = '🔔 Nouvelle Séance Ajoutée';  // ← Modifier ici
    // ...
}
```

### Modifier les couleurs Toast

Fichier: `public/js/toast-notifications.js`

```javascript
const colors = {
    success: { bg: '#d4edda', border: '#28a745', text: '#155724' },
    // ↑ Modifier les couleurs ici
    info: { bg: '#d1ecf1', border: '#17a2b8', text: '#0c5460' },
    warning: { bg: '#fff3cd', border: '#ffc107', text: '#856404' },
    error: { bg: '#f8d7da', border: '#dc3545', text: '#721c24' },
};
```

### Modifier la durée d'affichage

Fichier: `src/Service/WebNotificationService.php`

```php
'duration' => 5000,  // 5 secondes, modifier ici (en millisecondes)
```

---

## 🐛 Vérification

### Les fichiers suivants doivent exister :

```bash
src/Service/WindowsNotificationService.php       ✅
src/Service/WebNotificationService.php           ✅
public/js/toast-notifications.js                 ✅
templates/partials/notifications_web.html.twig   ✅
```

### Vérifier la modification du contrôleur:

```bash
# Dans src/Controller/AdminSeanceController.php
# Les services doivent être injectés :
WindowsNotificationService $windowsNotifier
WebNotificationService $webNotifier

# Les appels doivent exister :
$windowsNotifier->notifyAdminNewSeance($seance);
$webNotifier->prepareNotificationData(...);
```

### Vérifier la modification du template:

```bash
# Dans templates/admin/base_admin.html.twig
# Doit contenir :
<script src="{{ asset('js/toast-notifications.js') }}"></script>
{% include 'partials/notifications_web.html.twig' %}
```

---

## 🚀 C'est prêt!

**Aucune config supplémentaire nécessaire.**

Juste créer une séance dans `/admin/seance` pour voir les notifications! 🎉

---

Version finale : Notifications Windows 🪟 + Web 🌐
