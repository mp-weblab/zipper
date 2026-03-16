# Zipper – Gestion et sauvegarde de plugins WordPress
[![Licence : GPL v2+](https://img.shields.io/badge/Licence-GPL%20v2%2B-blue.svg)](LICENSE)
[![WordPress compatible](https://img.shields.io/badge/WordPress-6.6%2B-brightgreen.svg)](https://wordpress.org/)
[![PHP compatible](https://img.shields.io/badge/PHP-8.3%2B-8892BF.svg)](https://www.php.net/)
[![Version](https://img.shields.io/badge/version-1.0.4-blue.svg)](#)

## ℹ️ Informations

- **Nom du plugin** : Zipper
- **Version actuelle** : 1.0.0
- **Compatibilité WordPress** : 6.6+
- **Compatibilité PHP** : 8.3+
- **Licence** : GPL v2 ou ultérieure


Zipper est un plugin WordPress conçu pour les administrateurs et développeurs souhaitant **sauvegarder, distribuer et gérer leurs plugins** directement depuis l’interface d’administration.
Il permet de **créer des archives ZIP de plugins actifs ou inactifs**, pour la **sauvegarde avant mise à jour**, la **migration**, la **distribution de plugins personnalisés** ou l’**archivage sécurisé**.

## Notes de l’auteur

Ce plugin est distribué gratuitement dans un esprit de partage.
Merci de ne pas vendre ou monétiser le plugin sous une forme quelconque sans autorisation.

---

## ✨ Fonctionnalités

- 📦 Création de fichiers ZIP pour les plugins actifs ou inactifs
- 🧾 Génération de ZIP avec version et horodatage
- 📥 Téléchargement direct depuis l’interface WordPress
- 🗂 Stockage centralisé dans `wp-content/uploads/zipper`
- 🗑 Suppression individuelle ou en masse des archives
- 📄 Pagination intégrée pour gérer un grand nombre de ZIP
- 🔐 Accès administrateur uniquement avec protection par nonces
- 🛡 Dossier de stockage sécurisé contre l’accès public
- ⚙️ Utilisation de l’extension PHP native `ZipArchive`
- 🧹 Exclusion automatique des fichiers inutiles (`.git`, `node_modules`, `.DS_Store`)

---

## 🖥️ Interface

Le plugin ajoute une page dans **Outils → Zipper**.

Depuis cette page, vous pouvez :
- Générer un ZIP pour un plugin
- Télécharger une archive existante
- Supprimer une archive inutile
- Supprimer toutes les archives

---

## 📁 Structure du plugin

```
zipper/
├─ assets/
│   └─ js/
│       └─ script.js (interactions UI, AJAX)
├─ includes/
│   ├─ admin-page.php (page Outils → Zipper)
│   ├─ list-table.php (liste des ZIP stockés)
│   └─ zip-acts.php (création, téléchargement, suppression)
├─ index.php (sécurité)
├─ uninstall.php (suppression du dossier uploads/zipper)
└─ zipper.php (plugin loader)
```

---

## 🔧 Installation

1. Téléchargez ou clonez le dépôt :

```bash
git clone https://github.com/mp-weblab/zipper.git
```

2. Copiez le dossier `zipper` dans :

```
wp-content/plugins/
```

3. Activez le plugin depuis **Extensions → Extensions installées**

---

## 📌 Prérequis

- WordPress 5.0 ou supérieur
- PHP 7.4 ou supérieur
- Extension PHP `ZipArchive` activée

---

## 📖 Utilisation

1. Ouvrez **Outils → Zipper** dans l’administration WordPress
2. Sélectionnez un plugin à sauvegarder
3. Générez le ZIP
4. Téléchargez ou supprimez les archives selon vos besoins

---

## 🧹 Désinstallation

Lors de la désinstallation du plugin :
- Le dossier `uploads/zipper` est supprimé automatiquement
- Aucune donnée WordPress ni plugin tiers n’est impactée

---

## 🤝 Contributions

Les contributions sont bienvenues :

1. Forkez le projet
2. Créez une branche (`feature/ma-fonctionnalité`)
3. Ouvrez une Pull Request

Merci de respecter les bonnes pratiques WordPress et de tester vos modifications.

---

## 📄 Licence

Ce plugin est distribué sous licence **GPL v2 ou ultérieure**.
Voir le fichier `LICENSE` pour plus de détails.

---

## 👤 Auteur

MP WebLab
https://mp-weblab.com

