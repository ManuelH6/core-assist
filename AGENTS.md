<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package - do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain-don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== ui-authenticity rules ===

# UI Authenticity & Zero AI Footprint (Interdiction formelle des marqueurs d'IA)

Toute modification d'interface, de textes ou de code doit respecter une authenticité humaine totale et bannir les tics typiques de génération par IA :

1. **Zéro méta-citation de prompt ou de consigne :**
   - Ne jamais faire figurer dans l'interface (footer, mentions, descriptions, en-têtes) ou dans le code (commentaires, attributs) les références, URLs ou noms de sites sources donnés en inspiration dans un prompt (ex: sites modèles, documentations de design, noms de bibliothèques citées comme consigne).
   - Ne jamais insérer de crédits ou colophons mentionnant des sources externes sauf demande explicite.

2. **Zéro badge technique dans l'UI utilisateur :**
   - Ne jamais afficher sur les pages de l'application des badges glorifiant la stack technique interne (ex: "100% Laravel Blade", "No Vite", "Architecture modulaire"). Les écrans s'adressent à des utilisateurs finaux et parlent uniquement de leur métier.

3. **Zéro commentaire ou log de complaisance :**
   - Ne pas insérer de commentaires dans les fichiers CSS/JS/Blade s'adressant au prompt ou justifiant le respect d'une contrainte (ex: `/* Règle accessibilité X */`, `/* Pas de Vite */`).
   - Ne pas injecter de `console.log()` de complaisance pour confirmer qu'une contrainte technique a été respectée.

4. **Ton humain, sobre et orienté métier :**
   - Proscrire le jargon marketing générique d'IA (*« Plateforme modulaire conçue pour la précision »*, *« Hub de vigilance »*, *« Expérience holistique »*).
   - Utiliser des libellés fonctionnels, sobres et précis (*« Missions »*, *« Plan de Staffing »*, *« Rapports de rentabilité »*).

5. **Cohérence de marque et étanchéité contextuelle :**
   - Toujours nommer l'application par sa vraie marque (`CoreAssist`) et ne jamais laisser fuiter les fallbacks par défaut du framework (`Laravel`).
   - Veiller à ne pas polluer l'application avec des termes résiduels provenant de prompts antérieurs non reliés au domaine en cours.

=== mobile-ux-standards ===

# Normes d'Ergonomie Mobile & Design d'Interaction

Tout développement d'interface (Blade, CSS, composants interactifs) doit respecter obligatoirement les 6 normes d'ergonomie mobile et de design d'interaction :

1. **La Zone du Pouce (The Thumb Zone Standard) :**
   - Plus de 75 % des interactions sur smartphone s'effectuent à une seule main avec le pouce.
   - Les déclencheurs d'actions fréquents, filtres et onglets doivent être dans la zone naturelle de confort du pouce (bas ou milieu d'écran).
   - Les options secondaires et les filtres complexes s'ouvrent par le bas sous forme de Bottom Sheet escamotable (`offcanvas-bottom`).

2. **Dimension Minimale des Cibles Tactiles (Touch Target Sizing - WCAG 2.2 AA / Loi de Fitts) :**
   - Toute surface cliquable/tactile (bouton, lien, champ, cellule interactive) doit mesurer au minimum 44 × 44 pixels.
   - Espacement tampon minimal de 8 pixels entre deux cibles interactives adjacentes pour empêcher les clics parasites.

3. **Matrice de Présentation des Données (Data Display Matrix) :**
   - Interdiction de contraindre l'utilisateur mobile à faire défiler horizontalement un tableau complet sans repère.
   - Desktop (≥ 768px) : Tableau de données structuré avec première colonne collante (`table-sticky-first`).
   - Mobile (< 768px) : Transformation obligatoire du tableau en Cartes Empilées (`Mobile Cards Pattern` / `.mobile-card-list`) présentant un en-tête (titre + statut), une grille de 2 colonnes pour les métriques clés, et un bouton d'action pleine largeur en pied de carte.

4. **Filtrage par Tiroir Bas (Filter Bottom Sheet Pattern) :**
   - Sur grand écran : Barre segmentée horizontale ou volet latéral.
   - Sur mobile (< 768px) : Déclencheur unique compact avec icône entonnoir et badge de compte (`[Filtres (X)]`).
   - Au tap : Déploiement d'un tiroir inférieur (Bottom Sheet / `offcanvas-bottom`) avec cases à cocher/boutons radios spacieux et bouton pleine largeur « Appliquer les filtres ».

5. **Consolidation des Actions (Action Density & Progressive Disclosure) :**
   - Limiter à un seul bouton d'action primaire visible par niveau visuel.
   - Regrouper les actions secondaires dans un menu d'actions contextuel (Action Sheet / Menu `•••`).
   - En bas de formulaire sur mobile : boutons empilés verticalement (`flex-column`), validation positive en haut en pleine largeur, annulation en dessous.

6. **Ergonomie des Formulaires & Claviers Virtuels (Adaptive Form Inputs) :**
   - Libellés (`form-label`) obligatoirement au-dessus du champ (jamais à gauche sur mobile).
   - Déclaration explicite du type sémantique pour afficher le clavier adapté : `type="email"`, `type="tel"`, `type="number"`, `inputmode="decimal"`, `type="date"`.

=== living-documentation ===

# Documentation Vivante (EXPLAIN.md)

À chaque fois qu'une nouvelle fonctionnalité, migration, modèle ou décision d'architecture est intégrée à l'application, vous DEVEZ obligatoirement mettre à jour le document `EXPLAIN.md` à la racine du projet.
Ce document explique en termes simples, concrets et accessibles (sans jargon abstrait) le besoin métier du cabinet, les choix d'architecture retenus, et pourquoi cette organisation est la plus adaptée par rapport aux alternatives.

</laravel-boost-guidelines>
