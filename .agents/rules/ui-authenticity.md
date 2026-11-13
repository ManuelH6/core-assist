# UI Authenticity & Zero AI Footprint

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
