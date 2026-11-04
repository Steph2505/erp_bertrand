# Audit & déploiement — ERP Bertrand Store / Espace Mokolo d'Obala

| Branche | Environnement | URL | Chemin o2switch |
|---|---|---|---|
| `dev` | dev | https://dev.espacemokolodobala.com | `/home3/sc1zeucoder/dev.espacemokolodobala.com/` |
| `test` | recette | https://test.espacemokolodobala.com | `/home3/sc1zeucoder/test.espacemokolodobala.com/` |
| `production` | production | https://espacemokolodobala.com | `/home3/sc1zeucoder/espacemokolodobala.com/` |

> La branche `main` ne déploie rien. Si elle doit rester la branche d'intégration,
> le flux est `dev` → `main` (PR) → `test` → `production`.

## Fichiers

```
.github/workflows/
├── deploy.yml               ← workflow réutilisable (toute la logique)
├── deploy-dev.yml           ← push sur dev
├── deploy-test.yml          ← push sur test
└── deploy-production.yml    ← push sur production
```

Les trois appelants ne font que passer des paramètres. Une correction dans
`deploy.yml` profite aux trois environnements — c'est le but.

| | dev | test | production |
|---|---|---|---|
| `db:seed --force` | oui | oui | oui |
| Dump SQL avant migrations | non | oui | oui |
| Mode maintenance | oui | oui | oui |
| Nouveau push annule le précédent | non | non | non |
| Validation humaine | non | non | **oui** (GitHub Environment) |

---

## 1. Ce que le projet a de plus que vos autres dépôts (luxe, m-planner, prest-event)

| | luxe / m-planner | ERP Bertrand |
|---|---|---|
| Framework | Laravel 10, site vitrine | Laravel **10.50.2**, application métier |
| Contrôleurs | quelques-uns | **14 dossiers**, 83 vues Blade |
| Modèles | ~3 | **37** |
| Migrations | ~5 | **49** (données réelles en production) |
| Assets | SCSS compilé à la main, commité | **Vite** (`public/build` gitignoré → à compiler en CI) |
| Uploads | aucun | **spatie/laravel-medialibrary** (images produits/packs) |
| Droits | aucun | **spatie/laravel-permission** (rôles + permissions) |
| Sessions / cache | `file` | **`database`** |
| Environnements | 1 | **3** |

Ces différences changent le déploiement — ce n'est pas une copie du workflow `luxe`.

---

## 2. Points bloquants corrigés dans le workflow

### 2.1 — Les assets ne sont pas dans le dépôt (bloquant)
`public/build` et `public/hot` sont dans `.gitignore`, et les layouts appellent
`@vite(['resources/sass/app.scss', 'resources/js/app.js'])`.
Un rsync du dépôt seul → `ViteManifestNotFoundException` sur **toutes** les pages.

→ Étapes Node 20 + `npm ci && npm run build`, puis **vérification** que
`public/build/manifest.json` contient bien les trois entrées (`app.scss`, `app.js`, `pos.js`).

### 2.2 — `rsync --delete` effacerait les images produits (critique)
Les images sont stockées par medialibrary dans `storage/app/public/`, exposées via
le lien symbolique `public/storage`. Les excludes du modèle `luxe` ne les couvraient pas.

→ `--exclude='storage/app/public'`, `--exclude='storage/app/private'`,
`--exclude='public/storage'`. Sous rsync, un `--exclude` protège **aussi** le côté distant.
`bootstrap/cache/*.php` est également exclu (caches compilés côté serveur).

### 2.3 — 49 migrations sur des données réelles
→ `artisan migrate --force`, **précédé d'un `mysqldump` gzippé** dans
`~/backups-<env>/` (rétention 14 jours) sur `test` et `production`.
Sans ce dump, une migration ratée sur des données de caisse réelles est irrécupérable.
Restauration : § 6.5.

### 2.4 — `storage:link` obligatoire
Sans lien symbolique, toutes les images produits renvoient 404. Idempotent, donc rejoué
à chaque déploiement.

### 2.5 — Écritures concurrentes pendant le rsync
POS, mouvements de stock et sessions de caisse écrivent en continu. Un rsync de
~2 000 fichiers pendant une vente laisse l'application dans un état incohérent.
→ `artisan down --retry=30` avant le rsync, `artisan up` après (voir § 2.9c).

### 2.6 — Cache des permissions spatie
Les rôles sont mis en cache 24 h par défaut. Après un déploiement qui touche au
`PermissionSeeder`, les droits restent périmés → `artisan permission:cache-reset`.

### 2.7 — Garde-fou sur la cible
Avec trois environnements, une faute de frappe dans `remote_path` écraserait le
mauvais site. Le workflow refuse de déployer si `<remote_path>/.env` n'existe pas
déjà — ce qui force aussi à faire l'initialisation manuelle (§ 5) en premier.

### 2.8 — Vérification que le document root pointe sur `public/`
Le smoke test appelle `<url>/.env` : s'il répond autre chose que 403/404,
le fichier d'environnement est exposé publiquement et le workflow le signale en erreur.

### 2.9 — Course sur la whitelist SSH (corrigé)

`SshWhitelist/remove_all` est **global au compte cPanel**. Scénario :

```
Job A  add IP_A ──── rsync / migrate ────── remove_all ✗
Job B        add IP_B ──── migrate ─────────┼──► IP_B supprimée, SSH coupé
                                            └──► bloqué entre « artisan down »
                                                 et « artisan up » : site fermé
```

Deux verrous, parce que le problème a deux origines.

**a) Entre les trois environnements de ce dépôt — mutex GitHub.**
`deploy.yml` déclare au niveau du *job* un groupe `o2switch-ssh-sc1zeucoder`
volontairement **identique pour dev, test et production** (il n'inclut pas
`env_name`). Les trois se sérialisent : jamais deux en parallèle.
`cancel-in-progress: false` partout, **y compris sur `dev`** — annuler un
déploiement en cours risquerait justement de le tuer entre `down` et `up`.

> Limite GitHub : un seul run peut être *en attente* par groupe. Si `production`
> tourne et que `dev` puis `test` s'empilent, `dev` (en attente) est annulé.
> Un run annulé avant d'avoir démarré ne touche à rien — il suffit de le relancer.

**b) Depuis les autres dépôts du même compte — retry avec réarmement.**
GitHub ne sait pas coordonner `luxe`, `m-planner`, `prest-event`, `cez-fitness` et
l'ERP entre eux : leurs workflows appellent aussi `remove_all`. Les helpers
`ssh_run`, `ssh_script` et `rsync_run` (étape « Helpers SSH ») traitent ce cas :

- ils distinguent **code 255** (SSH n'a pas pu se connecter) de tout autre code
  (la commande distante a répondu). **Seul 255 est réessayé** — une migration qui
  échoue n'est jamais rejouée en boucle ;
- avant chaque nouvelle tentative, l'IP du runner est **réajoutée** à la whitelist ;
- 3 tentatives, 10 s d'intervalle. Toutes les commandes distantes du workflow sont
  idempotentes, donc rejouer un script entier est sans effet de bord ;
- les scripts distants passent par un **fichier** (`$RUNNER_TEMP/post.sh`) et non
  par un heredoc : un heredoc est consommé à la première lecture, et la deuxième
  tentative enverrait un script vide ;
- `rsync_run` applique la même logique aux codes réseau de rsync (12, 30, 35, 255)
  et ajoute `--partial` pour reprendre un transfert interrompu.

**c) La relève du mode maintenance est traitée à part.**
`artisan up` monte à **6 tentatives**, et sa condition est
`steps.down.conclusion != 'skipped'` (et non `== 'success'`) : si l'étape `down`
a échoué *après* avoir effectivement fermé le site, on tente quand même de le
rouvrir. En dernier recours, le workflow échoue avec la commande exacte à taper.

**d) Nettoyage ciblé quand c'est possible.**
L'étape finale tente d'abord `SshWhitelist/remove?address=<IP>`. Si ce point d'API
existe sur votre cPanel, plus aucun déploiement concurrent n'est coupé et le problème
disparaît à la racine. Sinon repli sur `remove_all` avec un avertissement dans les
logs — comportement actuel des autres dépôts.

**Reste à votre charge** : évitez de lancer un déploiement ERP pendant celui d'un
autre site du compte. Si la suppression ciblée s'avère supportée (le log de la
dernière étape vous le dira), alignez les quatre autres dépôts dessus et la
contrainte tombe définitivement.

### 2.10 — Filet de sécurité côté serveur (optionnel, recommandé en production)

Si malgré tout un déploiement meurt sans relever le site, ce cron le rouvre au bout
de 15 minutes :

```
*/5 * * * * D=~/espacemokolodobala.com/storage/framework/down; [ -f "$D" ] && [ $(( $(date +%s) - $(stat -c %Y "$D") )) -gt 900 ] && /opt/alt/php82/usr/bin/php -c ~/php82-custom.ini ~/espacemokolodobala.com/artisan up
```

---

## 3. La checklist du dev, confrontée au code

| Étape demandée | Statut | Détail |
|---|---|---|
| `composer install --no-dev --optimize-autoloader` | ✅ | en place |
| `npm ci && npm run build` | ✅ | en place, + contrôle du manifest |
| `php artisan key:generate` | ⚠️ | **une seule fois**, à l'init manuelle (§ 5) — pas à chaque déploiement, sinon toutes les sessions et les données chiffrées sautent |
| `php artisan migrate --seed --force` | ✅ | sûr ici : voir § 3.1 |
| `php artisan storage:link` | ✅ | en place |
| `config:cache` | ✅ | vérifié sans risque : **zéro appel à `env()` hors de `config/`** |
| `view:cache` | ✅ | en place |
| `event:cache` | ✅ | en place — `EventServiceProvider` a un `$listen` explicite et `shouldDiscoverEvents() === false` |
| `route:cache` | ❌ | **échoue sur ce code** — voir § 4.1 |
| `chown -R www-data:www-data` | ❌ | **inapplicable sur o2switch** : hébergement mutualisé, les fichiers appartiennent au compte cPanel `sc1zeucoder` et Apache tourne sous cet utilisateur. Un `chown www-data` échouerait (pas de root). Seul le `chmod -R 775` est conservé. |

### 3.1 — `--seed` est sans danger ici (vérifié)
`DatabaseSeeder` n'appelle que des seeders de **référentiels**, et les quatre
seeders non idempotents sont déjà commentés :

```
ProductSeeder   // commenté     PurchaseSeeder  // commenté
PackSeeder      // commenté     SaleSeeder      // commenté
```

Tous les seeders actifs utilisent `firstOrCreate` / `updateOrCreate` → rejouables
à chaque déploiement sans créer de doublon.

> ⚠️ **`UserSeeder` crée `admin@mokolo.com` avec le mot de passe `password`.**
> Idempotent, donc il ne réinitialise pas un mot de passe déjà changé — mais
> **changez-le à la première connexion en production**, avant d'ouvrir l'accès.

---

## 4. Anomalies relevées dans le code (à traiter côté dev)

### 4.1 — `route:cache` impossible en l'état
`routes/web.php` contient des routes en closure :

- ligne 94 : `Route::name('brands.index')->get('/brands', fn() => redirect()->route('products.index'));`
- ligne 95 : idem pour `price-groups.index`
- ligne 194 : `Route::get('/create', fn() => redirect()->route('expenses.index', ['new' => 1]))`

`artisan route:cache` échoue sur `Unable to prepare route for serialization. Uses Closure.`

Le workflow **tente `route:cache` et retombe sur `route:clear`** en affichant
`ROUTE_CACHE_SKIPPED` dans les logs. Le déploiement ne casse pas, et le jour où
les closures partent, le cache s'active tout seul — aucune modification du workflow.

**Correctif** : déplacer ces trois redirections dans un contrôleur
(`RedirectController::brands`, etc.). Gain : le cache sur ~180 routes.

### 4.2 — `.env.example` inutilisable en production
Fichier au format Laravel 11 alors que le squelette est Laravel 10, et surtout :

- `DB_CONNECTION=sqlite` → un ERP multi-utilisateurs doit être en MySQL
- `APP_DEBUG=true`, `APP_ENV=local`, `APP_KEY=` vide
- `APP_LOCALE=en` / `APP_FAKER_LOCALE=en_US` alors que `config/app.php` force
  `'locale' => 'fr'` et `'timezone' => 'Africa/Abidjan'` **en dur** (les variables
  d'env sont donc ignorées — cosmétique, mais trompeur)
- `MAIL_MAILER=log` → aucune notification ne partira

Les trois `.env` sont à créer **une fois** sur le serveur (§ 5) ;
ils ne sont jamais écrasés par le rsync (`--exclude='.env'`).

### 4.3 — `resources/views/welcome.blade.php` référence un fichier inexistant
`@vite(['resources/css/app.css', ...])` — or `resources/css/` n'existe pas
(le projet utilise `resources/sass/`). La vue n'est routée nulle part, donc inoffensive
aujourd'hui, mais elle plantera si on la branche. **À supprimer.**

### 4.4 — Résidus de compilation commités
`resources/sass/app.css` et `resources/sass/app.css.map` sont des sorties de
Live Sass Compiler laissées dans le dépôt alors que le projet est passé à Vite.
À retirer du suivi git.

### 4.5 — Couverture de tests quasi nulle
3 fichiers dans `tests/` pour 37 modèles et 14 modules. Le workflow ne lance donc
aucune suite : il n'y aurait rien à vérifier. À rouvrir quand des tests existeront
(au minimum POS, stock, paiements) — l'endroit naturel est un job `tests` en
dépendance de `deploy` dans `deploy-dev.yml` et `deploy-test.yml`.

### 4.6 — `QUEUE_CONNECTION=database` sans consommateur
Aucune classe `ShouldQueue` dans `app/`. La table `jobs` existe mais rien ne la
remplit et aucun worker ne tourne. Sans risque aujourd'hui ; **si** vous passez les
exports Excel ou les notifications en asynchrone, il faudra les crons du § 6.4.

---

## 5. Initialisation manuelle — une fois par environnement

À faire **avant le premier déploiement** de chaque branche, sinon le workflow
s'arrête sur « `.env` introuvable » (§ 2.7).

### 5.1 — Côté cPanel

1. Créer le domaine / sous-domaine, **document root sur `.../<domaine>/public`**.
2. Créer **trois bases MySQL distinctes** et leurs utilisateurs :
   `sc1zeucoder_erp_dev`, `sc1zeucoder_erp_test`, `sc1zeucoder_erp`.
   Ne jamais partager une base entre deux environnements.
3. Activer SSL (AutoSSL) sur les trois domaines.

### 5.2 — Côté SSH

```bash
ssh sc1zeucoder@<CPANEL_SERVER>
cd ~/dev.espacemokolodobala.com/          # ou test. / ou espacemokolodobala.com
alias p='/opt/alt/php82/usr/bin/php -c ~/php82-custom.ini'

# 1. Le .env — jamais écrasé par le rsync, propre à chaque environnement
nano .env
```

```ini
APP_NAME="Espace Mokolo d'Obala"
APP_ENV=production           # dev/test : « staging » si vous voulez les distinguer
APP_DEBUG=false              # dev/test : true si le client accepte de voir les traces
APP_URL=https://espacemokolodobala.com    # adapter par environnement
APP_KEY=                     # rempli par key:generate juste après

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sc1zeucoder_erp  # une base PAR environnement
DB_USERNAME=sc1zeucoder_erp
DB_PASSWORD=<mot de passe cPanel>

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database         # le config/ du projet lit bien CACHE_STORE (format L11)
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=<smtp o2switch>
MAIL_PORT=465
MAIL_USERNAME=<...>
MAIL_PASSWORD=<...>
MAIL_FROM_ADDRESS="no-reply@espacemokolodobala.com"

LOG_CHANNEL=stack
LOG_LEVEL=error              # dev/test : debug
```

```bash
# 2. Dépendances minimales pour pouvoir lancer artisan avant le 1er rsync
composer install --no-dev --optimize-autoloader

# 3. Clé applicative — UNE SEULE FOIS, jamais rejouée ensuite
p artisan key:generate --force

# 4. Schéma + référentiels
p artisan migrate --force
p artisan db:seed --force

# 5. Stockage et permissions
p artisan storage:link
chmod -R 775 storage bootstrap/cache
```

Puis pousser sur la branche correspondante : le workflow prend le relais.

### 5.3 — Compte administrateur

`db:seed` crée `admin@mokolo.com` / `password` (rôle *Super Admin*).
**En production, connectez-vous immédiatement et changez ce mot de passe**, ou créez
votre propre compte et désactivez celui-ci :

```bash
p artisan tinker
>>> $u = App\Models\User::create(['name'=>'Bertrand','email'=>'...','password'=>bcrypt('...'),'is_active'=>true]);
>>> $u->assignRole('Super Admin');
>>> App\Models\User::where('email','admin@mokolo.com')->update(['is_active'=>false]);
```

---

## 6. Commandes post-déploiement

### 6.1 — Automatisées à chaque push (rien à faire)

```bash
p artisan down --retry=30
# … rsync + mysqldump (test/prod) …
p artisan optimize:clear
p artisan migrate --force
p artisan db:seed --force
p artisan storage:link
p artisan permission:cache-reset
p artisan config:cache
p artisan view:cache
p artisan event:cache
p artisan route:cache || p artisan route:clear    # cf. § 4.1
chmod -R 775 storage bootstrap/cache
p artisan up
```

### 6.2 — Vérifications après la première mise en ligne

```bash
curl -I https://espacemokolodobala.com/login                  # 200
curl -I https://espacemokolodobala.com/build/manifest.json    # 200 = assets Vite servis
curl -I https://espacemokolodobala.com/.env                   # 403/404 — sinon doc root faux
p artisan about                                               # env=production, debug=false
p artisan migrate:status | grep -c Ran                        # doit afficher 49
ls -l public/storage                                          # lien vers ../storage/app/public
tail -50 storage/logs/laravel.log
```

Puis dans le navigateur : connexion, ouverture de caisse (POS), création d'un produit
**avec image** (valide medialibrary + `storage:link`), une vente, un encaissement,
et un export PDF de facture (valide dompdf sur PHP 8.2).

### 6.3 — Dépannage courant

```bash
# Le site reste bloqué en maintenance (workflow interrompu) — cf. § 2.9c / 2.10
p artisan up

# Assets 404 après déploiement
ls -l public/build/manifest.json     # absent → relancer le workflow

# « Target class does not exist » / config périmée après un rollback
p artisan optimize:clear && p artisan config:cache && p artisan view:cache

# Droits perdus après un déploiement
chmod -R 775 storage bootstrap/cache
```

### 6.4 — Cron cPanel (à ajouter seulement si besoin)

Aucun `schedule()` n'est défini dans `app/Console/Kernel.php` et aucun job n'est
mis en file : **aucun cron n'est nécessaire aujourd'hui**. Pour mémoire :

```
# Scheduler — toutes les minutes
* * * * * /opt/alt/php82/usr/bin/php -c ~/php82-custom.ini ~/espacemokolodobala.com/artisan schedule:run >> /dev/null 2>&1

# Worker de queue — toutes les 5 min, s'arrête quand la file est vide
*/5 * * * * /opt/alt/php82/usr/bin/php -c ~/php82-custom.ini ~/espacemokolodobala.com/artisan queue:work --stop-when-empty --max-time=280 >> /dev/null 2>&1
```

À ne mettre que sur `production` — sinon les trois environnements enverront
les mêmes notifications. Voir aussi le filet de sécurité maintenance du § 2.10.

### 6.5 — Restauration après une migration ratée

```bash
ls -lt ~/backups-production/ | head
gunzip < ~/backups-production/erp-AAAA-MM-JJ-HHMMSS.sql.gz | mysql -u <user> -p <base>
p artisan optimize:clear
p artisan up
```

---

## 7. Secrets GitHub requis

Identiques aux autres dépôts, et **partagés par les trois environnements**
(`secrets: inherit` dans les appelants) — aucun nouveau secret à créer :

| Secret | Rôle |
|---|---|
| `CPANEL_SERVER` | hôte o2switch (whitelist SSH + rsync) |
| `CPANEL_USERNAME` | `sc1zeucoder` |
| `CPANEL_API_TOKEN` | jeton UAPI pour `SshWhitelist/add`, `remove` et `remove_all` |
| `SSH_PRIVATE_KEY` | clé privée `ed25519` autorisée sur le compte cPanel |

Pour la validation humaine en production : **Settings → Environments → New environment
→ `production`**, puis cocher *Required reviewers*. Tant que cet Environment n'existe
pas, le workflow tourne sans blocage (`environment:` sur un nom inconnu est ignoré).

> ⚠️ `SshWhitelist/remove_all` **vide toute la whitelist SSH** du compte, pas seulement
> l'IP du runner. Vos IP fixes ajoutées à la main sauteront à chaque déploiement.
> La course entre déploiements concurrents qui en découlait est traitée au **§ 2.9**.

---

## 8. Ordre de passage recommandé

1. Créer les 3 domaines dans cPanel, document root sur `public/`.
2. Créer les 3 bases MySQL + utilisateurs.
3. Faire le § 5 sur `dev` uniquement, pousser sur `dev`, dérouler le § 6.2.
4. Une fois `dev` vert : même chose sur `test`, puis sur `production`.
5. Créer l'Environment GitHub `production` avec *Required reviewers*.
6. Changer le mot de passe de `admin@mokolo.com` en production (§ 5.3).
7. Lire le log de l'étape « Retirer l'IP du runner » du premier déploiement : s'il
   indique une suppression **ciblée**, aligner les 4 autres dépôts du compte (§ 2.9d).
8. Traiter le § 4.1 (closures) — `route:cache` s'activera tout seul au déploiement suivant.
