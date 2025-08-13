# UP WP All Import Form Imports

Plugin WordPress permettant de créer des formulaires d'import liés à WP All Import pour automatiser les imports de fichiers.

## Description

Ce plugin permet de créer plusieurs **formulaires d'import** liés chacun à un **modèle d'import WP All Import**. Chaque formulaire permet à un utilisateur d'uploader un fichier → cloner l'import modèle → lancer l'import automatiquement, tout en gardant l'historique complet dans WP All Import.

## Prérequis

- WordPress 5.0+
- PHP 7.4+
- Plugin **WP All Import** installé et activé
- Permissions d'administrateur pour utiliser le plugin

## Installation

1. Téléchargez le plugin ou clonez ce repository
2. Placez le dossier `up-wpai-form-imports` dans `/wp-content/plugins/`
3. Activez le plugin dans l'administration WordPress
4. Assurez-vous que WP All Import est installé et activé

## Utilisation

### 1. Créer un modèle d'import dans WP All Import

Avant d'utiliser ce plugin, vous devez créer au moins un import dans WP All Import qui servira de modèle :

1. Allez dans **WP All Import > New Import**
2. Configurez votre import (fichier source, mapping des champs, etc.)
3. Sauvegardez l'import (il servira de modèle)

### 2. Créer un formulaire d'import

1. Allez dans **Formulaires d'import > Ajouter nouveau**
2. Donnez un titre à votre formulaire
3. Sélectionnez le modèle d'import WP All Import à utiliser
4. Ajoutez une description optionnelle
5. Publiez le formulaire

### 3. Utiliser le formulaire

1. Allez dans **Formulaires d'import > Tous les formulaires**
2. Pour chaque formulaire, vous verrez :
   - Le nom du formulaire
   - Le modèle d'import associé
   - Un bouton "Uploader et lancer"
3. Cliquez sur "Choisir un fichier" et sélectionnez votre fichier (CSV, XML, Excel)
4. Cliquez sur "Uploader et lancer"
5. Le plugin va automatiquement :
   - Uploader votre fichier
   - Cloner le modèle d'import
   - Remplacer le fichier source par le vôtre
   - Lancer l'import automatiquement

### 4. Suivre l'import

Une fois l'import lancé, vous pouvez le suivre dans **WP All Import > Manage Imports**.

## Fonctionnalités

- ✅ Création de formulaires d'import personnalisés
- ✅ Association avec des modèles WP All Import existants
- ✅ Upload sécurisé de fichiers (CSV, XML, Excel)
- ✅ Clonage automatique des imports
- ✅ Lancement automatique des imports
- ✅ Interface utilisateur intuitive
- ✅ Gestion des permissions et sécurité
- ✅ Messages de confirmation et d'erreur
- ✅ Historique complet dans WP All Import

## Types de fichiers supportés

- **CSV** (Comma Separated Values)
- **XML** (Extensible Markup Language)
- **Excel** (.xls, .xlsx)

## Sécurité

- Vérification des permissions utilisateur (`manage_options`)
- Validation des types de fichiers
- Protection contre les uploads malveillants
- Utilisation des nonces WordPress
- Stockage sécurisé des fichiers uploadés

## Structure du plugin

```
up-wpai-form-imports/
├── up-wpai-form-imports.php    # Fichier principal
├── includes/                   # Classes PHP
│   ├── class-admin.php
│   ├── class-form-handler.php
│   └── class-import-cloner.php
├── assets/                     # Ressources CSS/JS
│   ├── css/admin.css
│   └── js/admin.js
├── templates/                  # Templates d'interface
│   └── admin/
├── languages/                  # Fichiers de traduction
└── README.md
```

## Développement

Voir le fichier `DEVELOPPEMENT.md` pour les détails techniques et la liste des tâches de développement.

## Support

Pour toute question ou problème :

1. Vérifiez que WP All Import est bien installé et activé
2. Vérifiez que vous avez les permissions d'administrateur
3. Consultez les logs WordPress pour les erreurs éventuelles

## Changelog

### Version 1.0.0
- Version initiale
- Création de formulaires d'import
- Association avec modèles WP All Import
- Upload et clonage automatique
- Interface d'administration complète

## Licence

GPL v2 ou ultérieure
