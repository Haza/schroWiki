# SchroWiki

Un wiki personnel ultra-rapide en fichier HTML unique. Conçu pour remplacer les wikis d'entreprise lents (DokuWiki, Confluence...) pour un usage quotidien : stocker des credentials, des liens internes, des notes de serveurs, des procédures.

Zéro installation, zéro dépendance locale. Un seul fichier HTML à ouvrir ou déposer sur un serveur PHP.

\---

## Fonctionnalités

* **Deux modes automatiques** - détection au démarrage : mode local (SQLite WASM + localStorage) si ouvert directement dans le navigateur, mode serveur (PHP + SQLite) si `api.php` est présent
* **Hiérarchie dossier > fiche** - un niveau d'organisation, simple et suffisant
* **Recherche instantanée** - full-text en JS pur sur titre, contenu et tags, insensible à la casse et aux accents, avec excerpt et highlighting des résultats dans la sidebar et dans le preview
* **Éditeur Markdown** - vue éditeur / split / preview, avec rendu en temps réel côté preview
* **6 thèmes** - Obsidian, Midnight, Forest, Mocha, Linen, Arctic — sélecteur dans la topbar, choix mémorisé
* **Navigation clavier** - `↑` `↓` pour naviguer dans la sidebar, `Entrée` pour ouvrir, `/` pour la recherche, `Ctrl+S` pour sauvegarder, `Ctrl+N` pour une nouvelle fiche
* **Autosave** - sauvegarde automatique toutes les 30 secondes si des modifications sont en attente
* **Tags** - ajout rapide par `Entrée` ou `,`, recherche sur les tags
* **Tri sidebar** - alphabétique (défaut) ou par date de modification
* **Aide Markdown** - modale scrollable avec toutes les syntaxes supportées, copie en un clic
* **Auth par cookie** (mode serveur) - login simple, cookie 30 jours, pas besoin de credentials dans l'URL
* **Export / Import `.db`** (mode local) - fichier SQLite standard, avec autosave optionnel vers un fichier local via File System Access API
* **Liens en `target="\_blank`** - tous les liens du preview s'ouvrent dans un nouvel onglet

\---

## Utilisation

### Mode local (sans serveur)

Télécharger `index.html`, l'ouvrir dans Chrome ou Firefox. Les données sont stockées dans le `localStorage` du navigateur. Utiliser le bouton **Exporter .db** pour sauvegarder.

### Mode serveur (PHP)

Déposer `index.html` et `api.php` dans le même dossier sur un serveur PHP. La base `wiki.db` est créée automatiquement au premier accès.

```
/wiki/
  index.html
  api.php
  wiki.db       ← créé automatiquement
  .htaccess     ← optionnel, pour restreindre l'accès par IP ou HTTPS
```

Configurer les credentials dans `api.php` :

```php
define('AUTH\_USER',     'admin');
define('AUTH\_PASS',     'motdepasse');
define('COOKIE\_DAYS',   30);
define('COOKIE\_SECRET', 'une\_chaine\_aleatoire\_longue');
```

Décommenter `'secure' => true` dans `api.php` si le serveur est en HTTPS (recommandé).

### Migration local → serveur

Exporter le `.db` depuis le wiki local, le déposer sur le serveur sous le nom `wiki.db`. La structure des tables est identique, la migration est immédiate.

\---

## Stack technique

* HTML / CSS / JS vanilla - aucun framework
* [sql.js](https://github.com/sql-js/sql.js) - SQLite compilé en WASM pour le mode local
* [marked.js](https://marked.js.org) - rendu Markdown
* [Inter](https://rsms.me/inter/) + [JetBrains Mono](https://www.jetbrains.com/legalnotices/terms_of_use_jb_mono/) via Google Fonts
* PHP 7.4+ + extension PDO SQLite pour le mode serveur

\---

## Raccourcis clavier

|Raccourci|Action|
|-|-|
|`/`|Focus sur la recherche|
|`↑` `↓`|Navigation dans la sidebar|
|`Entrée`|Ouvrir la fiche sélectionnée|
|`Ctrl+N`|Nouvelle fiche|
|`Ctrl+S`|Sauvegarder|
|`Echap`|Fermer les modales / quitter la recherche|



