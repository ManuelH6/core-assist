# CoreAssist - Conception Métier & Architecture Fonctionnelle

Ce document est le **cahier des charges fonctionnel et explicatif** de CoreAssist. 

Il a pour objectif d'expliquer, dans un langage clair, accessible et sans jargon informatique superflu, comment fonctionne l'application, quelle est sa logique métier, pourquoi elle est structurée de cette façon et en quoi cette organisation est la plus adaptée aux réalités quotidiennes d'un cabinet de conseil.

---

## 1. La vision : pourquoi concevoir CoreAssist ?

Un cabinet de conseil ne vend ni des produits manufacturés, ni des logiciels standards. Il vend de l'**intelligence méthodologique** : des diagnostics d'entreprise, des audits d'organisation, des études de faisabilité, des plans de redressement financier et du conseil stratégique sur-mesure.

Aujourd'hui, la grande majorité des cabinets fonctionnent avec des outils généralistes inadaptés :
- Des **tableurs Excel isolés** : chaque consultant modifie sa propre version d'une grille d'analyse, écrase les formules, perd les données antérieures, et stocke ses fichiers sur son poste.
- Des **gestionnaires de tâches classiques** (type Trello, Asana, Monday) : ces outils gèrent des « tickets » ou des to-do lists, mais ignorent complètement ce qu'est une méthodologie de conseil, un catalogue d'outils, un niveau de preuve, une révision de cadrage ou un livrable contractuel validé par un client.
- L'**absence de traçabilité des conclusions** : lorsqu'un rapport final préconise de fermer un site ou de licencier, il est souvent très difficile de remonter aux observations exactes de terrain ou aux simulations chiffrées qui justifient cette recommandation.

**CoreAssist est conçu pour être la plateforme d'exploitation du cabinet.** Elle permet d'industrialiser la rigueur du conseil sans rigidifier le travail intellectuel des consultants.

---

## 2. Le parcours réel d'une intervention du cabinet

Pour comprendre la structure de la base de données, il faut suivre le cheminement réel d'une mission de conseil, de la toute première prise de contact jusqu'à la clôture finale :

```text
1. ÉCOUTE & CADRAGE
   Un prospect contacte le cabinet (Demande)
   ➔ Le cabinet reformule le vrai problème (Fiche de compréhension)
   ➔ Le cabinet propose une offre d'intervention (Cadrage & ses versions)
   ➔ Le client valide formellement une proposition.

2. ORGANISATION DE LA MISSION
   Le contrat validé donne naissance à une ou plusieurs interventions (Missions)
   ➔ Le cabinet constitue l'équipe projet (Membres & Rôles de mission)
   ➔ Le travail d'enquête est découpé librement (Arborescence des Travaux)
   ➔ Les collaborateurs sont affectés aux travaux (Affectations).

3. MOBILISATION DE LA BOÎTE À OUTILS
   Le cadre opérationnel d'intervention est formalisé (Mission Framing Sheet™)
   ➔ Le cabinet met à disposition son patrimoine méthodologique (Catalogue d'outils)
   ➔ Le chef de mission choisit et paramètre les outils pour ce client (Utilisations d'outils)
   ➔ Les travaux mobilisent les outils adaptés
   ➔ Les consultants exécutent les outils et saisissent les données (Exécutions & Snapshots).

4. CAPITALISATION & ANALYSE
   Les consultants constatent, calculent et déduisent (Résultats)
   ➔ Les résultats s'alimentent entre eux pour former des synthèses globales (Lignage des Sources).

5. CONTRÔLE QUALITÉ INTERNE
   Les travaux et résultats sont soumis au chef de mission (Revues internes & Historique)
   ➔ Les demandes de corrections sont tracées jusqu'à l'approbation interne.

6. RESTITUTION & ESPACE CLIENT
   Le cabinet assemble les livrables prévus (Rapports, études, présentations)
   ➔ Le document est figé dans une version officielle et transmis au client
   ➔ Le client consulte, commente et échange avec l'équipe (Espace client)
   ➔ Le décideur client valide formellement la version finale
   ➔ La mission est clôturée avec succès.
```

---

## 3. Les domaines fonctionnels et la justification des choix

L'architecture est organisée en **six domaines fonctionnels cohérents**. Chaque table répond à un besoin métier précis et évite un piège opérationnel bien identifié.

---

### Domaine 1 : La Relation Client & le Cadrage Préalable

Avant de lancer le moindre travail d'analyse, le cabinet doit qualifier rigoureusement le besoin du client et contractualiser son périmètre.

#### `clients` (Le référentiel des clients)
- **Rôle métier :** Représente les entreprises, institutions ou personnes accompagnées.
- **Pourquoi cette table ?** Un client existe dans la durée, bien au-delà d'une simple mission. Un même client peut faire appel au cabinet aujourd'hui pour un diagnostic financier, puis deux ans plus tard pour un plan de recrutement. Séparer le client permet de conserver son historique complet.

#### `requests` (Les sollicitations entrantes)
- **Rôle métier :** Enregistre l'expression brute du besoin telle qu'exprimée par le client (par téléphone, email, rencontre physique).
- **Pourquoi cette table ?** Il ne faut jamais écraser ce que le client a dit avec ses propres mots. Un client qui dit *« mes employés manquent de motivation »* peut en réalité avoir un problème de sous-rémunération ou d'outils défaillants. Conserver la demande brute garantit qu'on pourra toujours vérifier si la réponse du cabinet traite bien le problème ressenti par le dirigeant.

#### `initial_understandings` (La fiche de compréhension initiale)
- **Rôle métier :** La formalisation professionnelle du problème par le cabinet (reformulation, question centrale, contraintes connues, manques d'information).
- **Pourquoi cette table ?** C'est la marque de fabrique du cabinet : transformer une plainte vague en un problème méthodologique clair. Elle est liée à la demande par une relation stricte 1:1 (`UNIQUE request_id`) pour forcer le consultant à formaliser cette compréhension avant de proposer un devis.

#### `framings` (Le cadrage général d'intervention)
- **Rôle métier :** Le contrat-cadre d'intervention proposé pour répondre à la demande.
- **Pourquoi cette table ?** Le cadrage est l'enveloppe générale du projet. Il porte le titre, la référence et le lien avec la demande.

#### `framing_versions` (Les versions successives de la proposition)
- **Rôle métier :** Le contenu détaillé de la proposition (objectifs, calendrier, honoraires, livrables attendus) et son historique de négociation.
- **Pourquoi cette table plutôt que des colonnes dans `framings` ?** En conseil, une proposition commerciale fait presque toujours l'objet d'allers-retours : le client demande de réduire le budget, d'ajouter un volet ou de raccourcir les délais. Modifier directement le texte ferait perdre la trace de ce qui avait été initialement proposé. La V1 reste intacte, la V2 intègre les ajustements, et la version acceptée par le client est formellement identifiée. Le contenu rédactionnel est stocké en format structuré (`JSON`) pour permettre d'enrichir les rubriques du document sans devoir créer 30 colonnes en base de données.

---

### Domaine 2 : La Gestion des Missions & la Mobilisation de l'Équipe

Une fois le cadrage validé par le client, l'intervention passe en phase opérationnelle.

#### `missions` (Le cadre opérationnel d'intervention)
- **Rôle métier :** L'unité d'intervention concrète sur le terrain.
- **Pourquoi cette table ?** Une mission n'est pas rattachée à une version de cadrage, mais au cadrage général. Pourquoi ? Parce qu'un accord global validé par un client peut donner naissance à plusieurs missions distinctes dans le temps (exemple : une première mission d'audit de 3 semaines, puis une seconde mission de réorganisation de 3 mois).

#### `mission_members` (La composition de l'équipe projet)
- **Rôle métier :** Définit qui intervient dans la mission et avec quel rôle (*Manager*, *Chef de mission*, *Consultant*, *Contributeur*).
- **Pourquoi cette table ?** Dans un cabinet de conseil, le rôle d'un consultant est **contextuel** : une consultante senior peut être *Cheffe de mission* sur un audit d'entreprise, mais simple *Contributrice spécialisée* sur une mission complexe menée par un associé. Enregistrer le rôle dans le compte utilisateur aurait rigidifié l'organisation ; la table pivot permet d'adapter les responsabilités à chaque dossier.

---

### Domaine 3 : L'Organisation et l'Affectation des Travaux

C'est ici que le travail sur le terrain s'organise concrètement.

#### `works` (Le découpage des investigations)
- **Rôle métier :** Représente les activités réelles à mener (ex : *« Analyser la structure des coûts »*, *« Réaliser 15 entretiens individuels »*, *« Étudier le trafic de la zone de chalandise »*).
- **Pourquoi cette table et pourquoi un `parent_id` ?** Les missions de conseil ne se ressemblent pas. Une mission simple nécessite 4 travaux à plat. Une mission d'envergure nécessite une décomposition arborescente (un grand axe de travail découpé en sous-analyses). Le champ `parent_id` permet une arborescence libre à l'infini, sans imposer une hiérarchie rigide dans le code.

#### `work_assignments` (L'affectation des consultants aux travaux)
- **Rôle métier :** Indique qui travaille concrètement sur chaque tâche, avec quel statut (*Responsable du travail* ou *Contributeur*) et sur quelle période.
- **Pourquoi cette table ?** Un travail peut mobiliser plusieurs personnes. De plus, retirer l'unicité stricte permet de conserver l'historique : si un consultant quitte la mission en cours de route et qu'un confrère prend le relais, la passation est tracée.

---

### Domaine 4 : Le Catalogue, la Configuration et l'Exécution des Outils

C'est le cœur méthodologique de Business Assist. **Un outil n'est pas une tâche, et le catalogue du cabinet n'est pas la mission d'un client.**

```text
 CATALOGUE DU CABINET                       UTILISATION DANS LA MISSION
 ┌──────────────────────────┐               ┌───────────────────────────────┐
 │ Outil : Analyse de Marge │               │ Mission Boulangerie Alpha     │
 │ Version : 1.0            │               │ Outil : Analyse de Marge      │
 │ Définition : Entrées /   │ ────────────> │ Config : Pains, viennoiseries │
 │ Sorties / Règles calcul │               │ Données propres à ce client   │
 └──────────────────────────┘               └──────────────┬────────────────┘
                                                           │
                                                           ▼
                                            Exécutions successives (Snapshots)
                                            - Exécution 1 : Hypothèse Farine +10%
                                            - Exécution 2 : Hypothèse Farine +25%
```

#### `tools` (Le catalogue d'outils du cabinet)
- **Rôle métier :** Le répertoire officiel des méthodes, formulaires, grilles d'évaluation et algorithmes conçus par le cabinet. Ce catalogue est initialisé avec les dix outils atomiques fondamentaux de la méthode Diagnostic 360° :
  1. **`D360-MFS` - Mission Framing Sheet™ :** formalisation du cadre opérationnel de la mission à partir des éléments de la demande et du cadrage.
  2. **`D360-DSC` - Diagnostic Scope Canvas™ :** sélection des domaines, des axes d'investigation et des niveaux d'approfondissement.
  3. **`D360-DIR` - Diagnostic Intake Register™ :** recensement, organisation et suivi des pièces et informations à collecter.
  4. **`D360-SIG` - Stakeholder Input Grid™ :** recueil des entretiens et perception des parties prenantes clés.
  5. **`D360-D360` - 360° Diagnostic Matrix™ :** matrice d'analyse multidimensionnelle croisant les constats et signaux faibles.
  6. **`D360-PSG` - Problem Structuring Grid™ :** qualification, causes racines et structuration rigoureuse des problèmes détectés.
  7. **`D360-DRD` - Diagnostic Restitution Deck™ :** organisation du support de restitution et synthèse des conclusions remises au dirigeant.
  8. **`D360-LDC` - Live Decision Capture :** consignation instantanée des réactions, arbitrages et décisions prises en séance de restitution.
  9. **`D360-DAP` - Diagnostic Archive Pack™ :** constitution du dossier documentaire complet garantissant la mémoire et la traçabilité des travaux.
  10. **`D360-NSR` - Next-Step Router™ :** structuration des suites envisageables et plans d'action post-diagnostic.
- **Pourquoi cette table ?** Ces outils constituent la valeur immatérielle et le savoir-faire du cabinet. Ils existent indépendamment de toute mission. La table conserve la finalité (`purpose`) de l'outil et désigne la version actuellement publiée (`current_version_id`) qui sert de référence opérationnelle.

#### `tool_versions` (L'évolution des outils dans le temps)
- **Rôle métier :** Les définitions successives de chaque outil (paramètres attendus en entrée, calculs à appliquer, indicateurs produits en sortie).
- **Pourquoi cette table ?** Si le cabinet fait évoluer sa méthode de diagnostic financier en ajoutant de nouveaux ratios en 2027, les missions réalisées en 2026 ne doivent pas être corrompues ou afficher des erreurs de calcul rétroactives. Chaque version distingue clairement la structure (`definition`), les paramètres que le DG a le droit d'adapter (`configuration_schema`), les règles métier (`business_rules`), les sorties attendues (`output_definition`) et le moteur d'affichage adapté (`interface_type`).

#### `mission_tool_usages` (L'instance de l'outil pour un client)
- **Rôle métier :** La personnalisation d'un outil du catalogue pour une mission précise.
- **Pourquoi cette table ?** Adapter une grille d'entretien pour une boulangerie (questions sur le fournil, les horaires de nuit) ne doit jamais modifier la grille originale du cabinet, ni impacter la mission d'un client dans le bâtiment. Cette table crée un clone de travail étanche, propre à la mission.

#### `work_tool_usages` (Le lien entre travaux et outils)
- **Rôle métier :** Associe les outils configurés aux travaux qui en ont besoin.
- **Pourquoi cette table d'association N:N ?** 
  - Un travail d'investigation peut nécessiter plusieurs outils (ex : un questionnaire terrain + un calculateur d'impact financier).
  - Un même outil configuré peut être mobilisé dans plusieurs travaux différents.
  - Certains travaux d'observation humaine ne mobilisent aucun outil informatique. Lier rigidement un outil à une tâche aurait paralysé le métier.

#### `tool_executions` (L'historique des calculs et simulations)
- **Rôle métier :** Enregistre chaque lancement effectif d'un outil avec ses données d'entrée (`input_data`) et ses données produites (`output_data`).
- **Pourquoi cette table ?** Le conseil est un métier d'hypothèses. Un consultant lance un calcul de rentabilité avec un coût d'électricité standard. Le lendemain, il affine ses hypothèses avec le tarif heures creuses. Enregistrer chaque exécution sous forme de photo instantanée (`JSON`) permet de comparer les scénarios sans perdre les calculs antérieurs.

#### `mission_framing_sheets` (Le cadre de référence opérationnel de la mission - Outil 01)
- **Rôle métier :** Formalise la fiche opérationnelle de cadrage propre à une mission (`Mission Framing Sheet™`). Elle récupère automatiquement les informations déjà convenues lors de la compréhension initiale (contexte, besoin exprimé, problématique centrale) et du cadrage contractuel validé (objectifs généraux, livrables, conditions), puis les complète avec les éléments d'exécution propres à la mission (objectifs spécifiques, responsable, dates prévisionnelles, contraintes terrain).
- **Pourquoi cette table ?** Le cadrage contractuel formalise ce qui a été vendu au client au niveau global. Mais une même demande peut donner naissance à plusieurs missions opérationnelles distinctes (ex : Phase 1 Diagnostic, Phase 2 Restructuration). `mission_framing_sheets` traduit le contrat en feuille de route opérationnelle, conserve la traçabilité des modifications apportées aux données héritées (`field_provenance`), et produit une photographie figée (`output_snapshot`) qui servira de cadre de travail partagé à tous les autres outils du diagnostic 360°.

#### `diagnostic_scope_canvases` (La délimitation du périmètre d'investigation - Outil 02)
- **Rôle métier :** Délimite précisément le périmètre d'investigation du diagnostic (`Diagnostic Scope Canvas™`) en organisant hiérarchiquement les domaines clés (Finance & Rentabilité, Opérations & Production, Commercial & Marché, Organisation & RH, Stratégie & Pilotage), les axes d'analyse et les sous-axes concrets. Chaque élément est qualifié par son statut (`inclus`, `exclu`, `à confirmer`), son niveau de profondeur d'investigation (`standard`, `approfondi`, `rapide`) et une justification obligatoire en cas d'exclusion. À la saisie, le consultant compose le périmètre progressivement, axe par axe, depuis le référentiel du cabinet qui retire automatiquement les éléments déjà retenus, sans soumettre de champs dupliqués entre les vues bureau et mobile.
- **Pourquoi cette table ?** Un diagnostic d'entreprise ne peut pas tout explorer avec la même intensité sans risquer de diluer les efforts ou de dépasser le budget convenu. `diagnostic_scope_canvases` formalise un arbitrage clair entre ce qui est analysé en profondeur, ce qui est traité de manière standard, et ce qui est expressément écarté (avec motif tracé). Cette cartographie figée (`output_snapshot`) sert ensuite de filtre directeur pour les outils suivants : elle dicte directement les pièces à réclamer dans le registre de collecte (`Diagnostic Intake Register™`) et les profils d'interlocuteurs à interroger (`Stakeholder Input Grid™`). Les règles métier de validation sont interceptées proprement pour afficher des messages exploitables à l'écran plutôt qu'une erreur serveur.

#### `diagnostic_intake_registers` (Le pilotage de la collecte d'informations - Outil 03)
- **Rôle métier :** Recense, organise et suit l'ensemble des données, documents et renseignements nécessaires pour examiner les axes inclus dans le périmètre du diagnostic (`Diagnostic Intake Register™`).
- **Pourquoi cette table ?** Alors que le périmètre (DSC) définit *ce qu'on va examiner*, le registre de collecte précise *ce qu'il faut savoir et obtenir pour pouvoir l'examiner*. Il centralise l'avancement global de la collecte, prévient les demandes redondantes auprès du client et conserve l'historique de chaque sollicitation.

#### `required_information` (Les informations recherchées par axe)
- **Rôle métier :** Représente chaque donnée ou renseignement concret attendu (ex : *« États financiers des 3 derniers exercices »*, *« Relevé des pertes matières en atelier »*), son niveau de priorité, sa source pressentie, le mode d'obtention envisagé (document, entretien, extraction) et le collaborateur affecté à son obtention.
- **Pourquoi cette table ?** Distinguer l'information recherchée de la source évite une confusion courante en mission : le besoin métier est de connaître le coût de revient (information) ; la source peut être un fichier comptable ou un entretien de production. Cette table permet de suivre individuellement chaque besoin jusqu'à sa fiabilisation.

#### `received_items` (Les pièces, documents et éléments collectés)
- **Rôle métier :** Consigne chaque pièce effectivement reçue (fichier Excel, contrat, balance, compte-rendu d'entretien, rapport d'audit), son type, sa provenance et sa date de remise.
- **Pourquoi cette table ?** Réceptionner un document ne signifie pas qu'il correspond à un seul besoin. Un grand livre général transmis par le client répond simultanément aux questions sur le chiffre d'affaires, les achats et les charges externes. Cette table isole l'objet physique/numérique transmis par le client.

#### `received_item_information` (L'association N:N entre pièces et besoins d'information)
- **Rôle métier :** Associe chaque élément collecté aux différentes informations recherchées qu'il permet de couvrir, avec le degré de couverture (*intégral* ou *partiel*).
- **Pourquoi cette table de liaison ?** Elle assure une traçabilité bidirectionnelle indispensable : à partir d'un besoin (ex: marge brute), on retrouve immédiatement toutes les pièces justificatives fournies ; à partir d'un fichier transmis par le client, on sait exactement quelles questions du diagnostic il a permis d'élucider. Elle matérialise aussi la règle métier fondamentale : *une pièce reçue n'est pas automatiquement une information vérifiée*.

#### `stakeholder_input_grids` (La cartographie et le suivi des parties prenantes - Outil 04)
- **Rôle métier :** Organise l'identification des personnes ou groupes détenant des connaissances utiles au diagnostic et pilote leur mobilisation (`Stakeholder Input Grid™`).
- **Pourquoi cette table ?** Un diagnostic 360° ne repose pas uniquement sur des pièces comptables ou des relevés techniques. Il nécessite de confronter les perceptions humaines (direction, encadrement, ouvriers, clients, partenaires). Cette table centralise le répertoire des interlocuteurs et mesure l'avancement effectif de leur sollicitation.

#### `stakeholders` (Le répertoire des interlocuteurs clés et groupes)
- **Rôle métier :** Fiche d'identification de chaque acteur (nom d'une personne physique, intitulé d'un service ou groupe externe), son statut (*interne*, *partenaire*, *fournisseur*, *client*, *institutionnel*), sa fonction et son rôle dans le diagnostic (*décideur*, *expert*, *relais terrain*).
- **Pourquoi cette table ?** Une partie prenante n'est pas toujours une personne individuelle : interroger *« L'équipe de nuit du fournil »* ou *« Les transporteurs sous-traitants »* est une réalité fréquente en mission. Isoler l'interlocuteur permet de lui associer plusieurs contributions sur des domaines différents sans dupliquer ses coordonnées.

#### `expected_contributions` (Le plan de mobilisation et contributions attendues)
- **Rôle métier :** Définit pour chaque partie prenante ce que le cabinet attend d'elle (information recherchée, explications sur un dysfonctionnement, retour d'expérience), la modalité retenue (*entretien*, *réunion*, *questionnaire*, *observation*) et le consultant affecté à la démarche.
- **Pourquoi cette table et pourquoi des clés `scope_item_id` et `required_information_id` ?** Pour appliquer la règle d'or de non-ressaisie : le consultant ne retappe jamais un domaine ou un axe déjà défini dans le périmètre (DSC) ni une information déjà demandée dans le registre (DIR). L'outil référence directement l'élément d'origine (`scope_item_id`, `required_information_id`) et applique une règle d'étanchéité stricte : si un axe a été explicitement exclu du diagnostic par le DG dans le DSC, toute tentative de mobilisation sur ce sujet est immédiatement bloquée. Cette table planifie la charge d'entretiens et suit l'état de chaque sollicitation (*à solliciter*, *planifié*, *réalisé*, *indisponible*).

#### `received_contributions` (Le dossier des déclarations et témoignages recueillis)
- **Rôle métier :** Consigne les déclarations, points saillants et signaux d'alerte effectivement recueillis lors des entretiens ou ateliers, avec leur date, modalité, compte-rendu associé et appréciation de fiabilité (*témoignage solide*, *perception à corroborer*, *opinion subjective*).
- **Pourquoi cette table ?** Une contribution attendue n'est pas une contribution obtenue, et une déclaration humaine n'est pas une vérité démontrée. Si un chef de quai affirme : *« Les camions partent souvent à moitié vides à cause des retards de facturation »*, cette déclaration constitue une perception à instruire, pas encore une conclusion du rapport.

#### `received_contribution_information` (Le croisement entre témoignages et besoins d'information DIR)
- **Rôle métier :** Relie chaque témoignage ou compte-rendu d'entretien aux besoins d'information recensés dans le DIR (`required_information`).
- **Pourquoi cette table de liaison ?** Elle assure la passerelle entre l'enquête humaine et les pièces documentaires : une information sur la baisse de marge brute est documentée d'un côté par les fiches de prix (DIR) et corroborée de l'autre par les explications orales de la direction commerciale (SIG).

#### `diagnostic_360_matrices` (Le moteur central d'évaluation du diagnostic - Outil 05)
- **Rôle métier :** Constitue la matrice centrale du diagnostic 360° (`360° Diagnostic Matrix™` ou D360). C'est le cœur analytique de l'intervention : alors que les quatre outils précédents définissent le cadre (MFS), délimitent le périmètre (DSC), organisent la collecte de pièces (DIR) et mobilisent les acteurs (SIG), le D360 réalise le travail d'évaluation systématique proprement dit.
- **Pourquoi cette table ?** Elle porte l'état global du travail d'évaluation d'une mission, conserve la trace des instantanés d'entrée (`input_snapshot` héritant automatiquement des axes DSC, des pièces DIR et des entretiens SIG sans aucune ressaisie) et d'instantané de sortie (`output_snapshot`), qui servira à alimenter les outils de hiérarchisation des problèmes (PSG - Problem Structuring Grid) et le rapport de restitution (DRD).

#### `diagnostic_assessments` (Les évaluations méthodiques par axe d'investigation)
- **Rôle métier :** Enregistre la situation réelle observée, l'analyse critique du cabinet, la qualification du niveau d'appréciation (*exemplaire*, *satisfaisant*, *acceptable*, *vulnérable*, *critique*) et l'état d'avancement de l'évaluation sur chaque axe retenu.
- **Pourquoi cette table ?** Les évaluations sont générées automatiquement à partir des axes déclarés « inclus » dans le Diagnostic Scope Canvas (DSC), avec liaison directe par `scope_item_id`. Si un axe a été explicitement exclu dans le DSC, il n'apparaît pas dans la matrice d'évaluation. Le consultant n'a jamais à ressaisir le libellé d'un domaine ou d'un axe : il se concentre sur l'examen factuel de la situation et son jugement d'expert.

#### `diagnostic_evidences` (Le registre des éléments de preuve et sources associées)
- **Rôle métier :** Centralise l'ensemble des éléments probants servant d'appui à l'évaluation : pièces documentaires issues du DIR (`received_item_id`), témoignages recueillis dans le SIG (`received_contribution_id`), observations physiques directes sur le terrain ou entretiens complémentaires. Chaque preuve est assortie d'une cote de fiabilité explicite (*solide*, *à corroborer*, *fragile*, *contradictoire*) et de ses réserves méthodologiques éventuelles.
- **Pourquoi cette table ?** Un diagnostic de cabinet ne peut pas reposer sur de simples intuitions non étayées. Cette table isole les éléments de preuve pour leur appliquer un filtre d'évaluation critique de fiabilité avant de fonder une conclusion définitive.

#### `assessment_evidence` (L'association N:N entre évaluations et preuves)
- **Rôle métier :** Table pivot qui associe chaque axe d'évaluation aux preuves matérielles qui l'éclairent, en précisant le degré de pertinence (*preuve directe*, *corroboration indirecte*, *élément de contexte*) et des annotations de rapprochement.
- **Pourquoi cette table de liaison ?** Une preuve documentaire (par exemple un grand livre analytique DIR) ou un témoignage clé (par exemple l'entretien du directeur d'usine SIG) éclaire souvent plusieurs axes distincts (la rentabilité des produits, le dimensionnement des stocks et la gestion des rebuts). Cette table garantit qu'une preuve est enregistrée une seule fois et mobilisée partout où elle fait autorité.

#### `diagnostic_findings` (La qualification des constats : forces, difficultés et hypothèses)
- **Rôle métier :** Répertorie formellement les constats issus de chaque évaluation : **Points forts** (atouts démontrés, compétences reconnues), **Difficultés & Vulnérabilités** (dysfonctionnements, causes probables identifiées, conséquences potentielles sur l'organisation ou la trésorerie, gravité/criticité) et **Hypothèses d'investigation** (pistes d'approfondissement).
- **Pourquoi cette table ?** C'est le vivier qualitatif structuré du diagnostic. En isolant chaque difficulté avec ses causes et ses conséquences, le cabinet prépare directement la grille de structuration des problèmes (PSG). De plus, tracer explicitement les points forts évite l'écueil d'un audit purement punitif et valorise les ressources internes du client.

#### `diagnostic_axis_summaries` (La synthèse consolidée par axe)
- **Rôle métier :** Regroupe la synthèse rédigée d'un axe précis, résumant les forces clés, les difficultés majeures, les lacunes résiduelles d'information et les contradictions observées nécessitant des investigations complémentaires.
- **Pourquoi cette table ?** Elle offre une vue d'étape condensée pour le chef de mission lors de la revue d'un axe sans obliger à relire tous les détails bruts.

#### `diagnostic_domain_summaries` (La synthèse globale et appréciation par grand domaine)
- **Rôle métier :** Formalise l'appréciation globale d'un grand domaine d'entreprise (Finance, Opérations, Commercial, etc.) avec sa note globale, sa synthèse rédigée et ses enseignements majeurs.
- **Pourquoi cette table ?** Pour passer de l'analyse microscopique par axe à la vision stratégique macroscopique attendue par la direction générale du client dans le rapport de restitution final.

#### `problem_structuring_grids` (La structuration et la hiérarchisation des problèmes - Outil 06)
- **Rôle métier :** Pilote la transformation des difficultés constatées lors de l'évaluation D360 en problèmes formellement qualifiés et structurés (`Problem Structuring Grid™` ou PSG). Alors que le D360 évalue la situation d'ensemble, le PSG analyse *en profondeur* les dysfonctionnements : pourquoi ils se produisent (causes), ce qu'ils provoquent (conséquences) et comment ils s'alimentent mutuellement (réseau relationnel).
- **Pourquoi cette table ?** Une simple liste de difficultés ne permet pas de construire un plan de redressement efficace : s'attaquer aux symptômes sans traiter les causes profondes aggrave souvent la situation. Cette table porte la photographie d'entrée (`input_snapshot`) récupérant les difficultés du D360, et produit l'instantané de sortie (`output_snapshot`) qui servira de matière première au deck de restitution (DRD) et au dialogue avec les dirigeants (LDC).

#### `structured_problems` (Les fiches d'identité des problèmes qualifiés)
- **Rôle métier :** Chaque fiche problème dispose d'un identifiant unique (`PRB-001`, `PRB-002`), d'une formulation précise (évitant les titres flous comme *« Problème commercial »*), de son positionnement dans le périmètre (domaine, axe, sous-axe DSC), de son lien d'origine vers le constat D360 (`diagnostic_finding_id`), de ses manifestations observables sur le terrain et de son statut de qualification (*à examiner*, *en cours d'analyse*, *structuré*, *validé*).
- **Pourquoi cette table ?** Elle assure une traçabilité totale entre l'évaluation de terrain et l'analyse causale. Un constat D360 peut être découpé en plusieurs problèmes distincts, ou plusieurs observations de terrain peuvent converger vers un problème unique.

#### `problem_causes` (L'arbre des causes et leur degré de démonstration)
- **Rôle métier :** Consigne l'ensemble des causes explicatives d'un problème, leur nature (*organisationnelle*, *humaine*, *financière*, *technique*, *stratégique*), leur explication détaillée et leur niveau de confirmation explicite (*hypothèse*, *partiellement étayée*, *confirmée*).
- **Pourquoi cette table ?** En mission de conseil, une explication avancée par un interlocuteur n'est qu'une hypothèse tant qu'elle n'est pas vérifiée par des chiffres ou des recoupements. Cette table empêche de présenter des supputations comme des vérités établies et pousse l'équipe à chercher des éléments probants pour faire évoluer le niveau de confirmation.

#### `problem_consequences` (L'évaluation des impacts directs et transversaux)
- **Rôle métier :** Décrit les effets négatifs concrets provoqués par le problème, son degré d'importance (*mineure*, *moyenne*, *élevée*, *critique*) et le domaine dans lequel il se manifeste (souvent différent du domaine d'origine : un problème d'ordonnancement en usine se traduit par des pertes financières en trésorerie et des pénalités contractuelles auprès des clients).
- **Pourquoi cette table ?** Elle permet de démontrer à la direction générale le coût de l'inaction et l'urgence de traiter le problème en reliant la défaillance opérationnelle à ses retombées économiques ou commerciales mesurables.

#### `problem_relations` (La cartographie causale et le réseau d'influence entre problèmes)
- **Rôle métier :** Modélise les liens entre les différents problèmes de l'organisation (*contribue à*, *provoque*, *aggrave*, *dépend de*, *partage une cause commune avec*) et précise s'il s'agit d'un lien avéré ou d'une relation de travail hypothétique.
- **Pourquoi cette table ?** Les dysfonctionnements d'une entreprise ne sont jamais isolés. Cette table permet de faire émerger automatiquement les **problèmes racines** (ceux qui déclenchent les autres en cascade sans être provoqués en amont) et d'identifier les goulets d'étranglement majeurs sur lesquels concentrer les efforts de redressement.

#### `problem_evidences` (L'ancrage probant des causes et conséquences)
- **Rôle métier :** Relie un problème, une cause ou une conséquence aux pièces documentaires collectées (`received_item_id` DIR), aux témoignages oraux (`received_contribution_id` SIG) ou aux constats étayés du D360 (`d360_evidence_id`).
- **Pourquoi cette table ?** Pour garantir l'inattaquabilité du travail du cabinet : chaque cause affirmée et chaque impact majeur peuvent être immédiatement justifiés par une preuve traçable lors des comités de restitution.

#### `problem_priority_assessments` (La hiérarchisation multicritère des problèmes)
- **Rôle métier :** Formalise l'évaluation multicritère de chaque problème selon 5 dimensions complémentaires : gravité intrinsèque, fréquence d'apparition, étendue dans l'organisation, urgence d'intervention et impact sur les objectifs stratégiques de la mission. Attribue une classe de priorité objective (*P1 Critique*, *P2 Majeur*, *P3 Moyen terme*, *P4 Secondaire*) avec argumentaire explicatif.
- **Pourquoi cette table ?** Elle évite l'arbitraire de l'urgence émotionnelle du client et apporte la rigueur méthodologique du cabinet pour arbitrer objectivement où allouer le temps et les budgets d'intervention.

#### `diagnostic_restitution_decks` (La préparation et la trame éditoriale de restitution - Outil 07)
- **Rôle métier :** Prépare, organise et séquence la restitution stratégique du diagnostic (`Diagnostic Restitution Deck™` ou DRD). Sa responsabilité n'est pas de refaire le diagnostic, mais de transformer les constats techniques (D360) et les problèmes structurés (PSG) en un support éditorial cohérent, transparent, pédagogique et justifiable face aux dirigeants.
- **Pourquoi cette table ?** Elle porte l'état global du travail de restitution pour une mission, conserve les photographies d'entrée (`input_snapshot` capturant l'état des outils sources) et de sortie (`output_snapshot`), et pilote le taux d'avancement rédactionnel (`readinessPercentage`).

#### `restitution_sections` (Les chapitres et jalons de la présentation)
- **Rôle métier :** Découpe la restitution en sections ordonnées (`position`, `title`, `section_key`, `objective`, `status`, `observations`). Une trame standard en 8 sections est automatiquement instanciée : Présentation de la mission, Démarche & Sources, Périmètre & Couverture réelle, Synthèse 360°, Analyse détaillée par domaine, Problèmes structurés & causes racines, Enjeux prioritaires (P1/P2), Conclusion & Trajectoire cible.
- **Pourquoi cette table ?** Chaque section peut être préparée, annotée, ordonnée et formellement validée indépendamment par le chef de mission. Le DRD met un point d'honneur à la **transparence méthodologique** en distinguant clairement le périmètre prévu, les axes audités en profondeur, les axes partiels et les sujets hors champ justifiés.

#### `restitution_contents` (Les blocs de contenu rédactionnels et structurés)
- **Rôle métier :** Contient les unités de contenu individuelles au sein d'une section (`text`, `callout`, `summary_table`, `domain_rating`, `strengths_list`, `problems_list`, `conclusion_box`), avec titre, texte explicatif, données structurées JSON et indicateur éditorial (`is_custom_editorial`).
- **Pourquoi cette table ?** Elle permet d'agencer souplement des blocs variés sans figer un format rigide : le consultant peut juxtaposer une cotation globale, un tableau d'impacts chiffrés et un encadré de conclusion stratégique.

#### `restitution_sources` (La filiation probante des contenus importés)
- **Rôle métier :** Mémorise le lien d'origine de chaque bloc importé depuis les outils d'amont (`source_tool`, `source_type`, `source_id`, `source_reference`, `source_summary`).
- **Pourquoi cette table ?** Elle empêche la dérive fréquente où un support de présentation perd le fil de ses justifications. Si le client conteste un constat lors de la restitution, le consultant remonte instantanément en un clic à l'analyse D360, au document DIR ou à l'entretien SIG qui le démontre.

#### `restitution_versions` (La mémoire des jalons et versions consolidées)
- **Rôle métier :** Archive chaque version figée de la restitution (`version_number`, `title`, `restitution_snapshot` JSON complet, `published_at`).
- **Pourquoi cette table ?** Entre la première version de travail partagée en interne et le support validé pour le comité de direction, plusieurs jalons se succèdent. Conserver l'historique complet garantit une traçabilité totale et alimente directement le rapport formel (livrable officiel).

#### `live_decision_captures` (La mémoire vivante des arbitrages et engagements - Outil 08)
- **Rôle métier :** Formalise et sécurise les décisions, arbitrages, accords et engagements pris en réunion ou en comité de pilotage (`Live Decision Capture™` ou LDC). Il conserve une trace opposable et documentée de ce qui a été décidé, par qui, dans quel contexte et avec quelles conséquences.
- **Pourquoi cette table ?** Elle centralise le registre décisionnel d'une mission, stocke la photographie d'entrée (`input_snapshot` des problèmes PSG et constats D360 sous-jacents) et calcule en temps réel le taux de concrétisation des engagements pris (`commitmentsCompletionPercentage`).

#### `decision_exchanges` (Les instances, réunions et comités d'arbitrage)
- **Rôle métier :** Enregistre le cadre d'échange dans lequel les décisions sont intervenues (`meeting`, `steering_committee`, `workshop`, `interview`, `videoconference`, avec date, lieu, contexte, observations et participants).
- **Pourquoi cette table ?** Une décision n'apparaît jamais hors sol : savoir qui était présent et dans quelle séance elle a été votée ou actée protège le cabinet et le client de tout malentendu futur.

#### `discussion_points` (Les sujets débattus et points d'arbitrage)
- **Rôle métier :** Recense les points précis abordés au cours d'un échange, reliés au domaine, à l'axe ou directement à un problème structuré issu du PSG (`problem_id`).
- **Pourquoi cette table ?** Elle permet de retracer les discussions préalables avant le passage à la décision, documentant les arguments échangés et les options envisagées.

#### `captured_decisions` (Le registre formel des décisions et arbitrages)
- **Rôle métier :** Porte chaque décision formalisée avec sa référence unique (`DEC-001`), son intitulé, sa formulation exacte, sa nature (`arbitrage`, `validation`, `accord`, `orientation`, `refusal`, `postponement`), son autorité décisionnelle (`client`, `steering_committee`, `joint`, `cabinet`), sa motivation (`justification`), sa date et son statut (`proposed`, `taken`, `to_confirm`, `revised`, `cancelled`).
- **Pourquoi cette table ?** Elle gère le cycle de vie complet d'une décision, y compris sa révision auditée (`previous_version_id` et `revision_notes`) garantissant qu'aucune décision n'est modifiée en douce sans conserver la mémoire de la version initiale.

#### `decision_commitments` (Le plan d'action et les engagements opérationnels)
- **Rôle métier :** Décline immédiatement chaque décision en engagement concret : qui doit faire quoi (`action`, `responsible_name`, `assigned_to_user_id`, `assigned_to_stakeholder_id`), pour quelle date d'échéance (`due_date`), avec quel résultat attendu (`expected_outcome`) et quel état d'avancement (`to_do`, `in_progress`, `completed`, `blocked`, `cancelled`).
- **Pourquoi cette table ?** Une décision sans plan d'action reste un vœu pieux. Cette table garantit que chaque arbitrage se traduit par un responsable identifié et une date d'exécution vérifiable.

#### `decision_sources` (L'ancrage justificatif des décisions)
- **Rôle métier :** Relie explicitement une décision à ses sources probantes (`problem` du PSG, `finding` du D360, `intake_item` du DIR ou `stakeholder` du SIG).
- **Pourquoi cette table ?** Elle évite les arbitrages arbitraires ou déconnectés des constats : chaque choix stratégique est adossé aux faits avérés découverts pendant le diagnostic.

#### `diagnostic_archive_packs` (La conservation pérenne et la mémoire du diagnostic - Outil 09)
- **Rôle métier :** Constitue, organise et préserve le dossier d'archive structuré de l'ensemble des éléments produits ou utilisés au cours du diagnostic (`Diagnostic Archive Pack™` ou DAP). Il ne duplique pas inutilement les fichiers physiques mais référence avec précision les données, pièces collectées, entretiens, évaluations, arbres de problèmes, versions de restitution et décisions d'arbitrage.
- **Pourquoi cette table ?** Elle porte le dossier d'archive d'une mission (`reference`, `title`, `description`, `status`, `responsible_id`, `closed_at`, `completeness_score`). Elle stocke la photographie d'entrée (`input_snapshot`) et la photographie finale d'inventaire complet (`output_snapshot`) garantissant l'accès inaltérable aux preuves et conclusions du diagnostic même des années après la clôture.

#### `archive_categories` (Le plan de classement documentaire du cabinet)
- **Rôle métier :** Définit les catégories documentaires officielles (`code`, `name`, `description`, `icon`, `position`, `is_system`) : Cadrage Opérationnel, Périmètre, Collecte, Consultations, Analyses 360°, Problèmes & Causes, Restitutions, Décisions & Arbitrages, Documents Complémentaires.
- **Pourquoi cette table ?** Elle harmonise la structure de classement à l'échelle du cabinet pour que tout collaborateur retrouve instantanément une pièce quel que soit le dossier.

#### `archive_items` & `archive_item_categories` (Les unités documentaires et analytiques archivées)
- **Rôle métier :** Enregistre chaque élément versé ou auto-découvert avec sa référence (`ARC-001`), son intitulé, son type (`document`, `tool_snapshot`, `data_record`, `contribution`, `decision`, `restitution_version`), son outil source (`mfs`, `dsc`, `dir`, `sig`, `d360`, `psg`, `drd`, `ldc`), son chemin ou référence de base de données (`location_type`, `location_path`), ses métadonnées JSON et son statut (`available`, `replaced`, `deprecated`, `archived`).
- **Pourquoi une table de liaison multi-catégories ?** Un même élément peut appartenir à plusieurs catégories documentaires (ex: une note d'arbitrage financier peut relever à la fois des « Décisions » et des « Analyses ») sans devoir dupliquer le document.

#### `archive_versions` (L'historique inaltérable des versions successives)
- **Rôle métier :** Mémorise chaque version successive d'un élément archivé (`version_number`, `version_tag`, `title`, `location_path`, `payload_snapshot`, `created_by`, `changelog`).
- **Pourquoi cette table ?** Une nouvelle version n'écrase jamais l'ancienne. L'auditabilité impose de pouvoir reconstituer avec exactitude l'état du dossier à n'importe quelle date de la mission.

#### `archive_sources` (La filiation probante et les liens de justification)
- **Rôle métier :** Trace le lien direct entre un élément d'archive et son origine métier amont (`source_tool`, `source_type`, `source_id`, `relationship_type` [origin, justification, evidence, version_of, dependency], `notes`).
- **Pourquoi cette table ?** Elle garantit la traçabilité intégrale de la chaîne de valeur du diagnostic : chaque pièce archivée justifie son rôle et ses connexions avec les autres travaux.

#### `next_step_routers` (Le pont entre diagnostic et passage à l'action - Outil 10)
- **Rôle métier :** Transforme les conclusions analytiques du diagnostic (D360, PSG, LDC) en un ensemble cohérent et ordonnancé de recommandations stratégiques et d'actions concrètes (`Next-Step Router™` ou NSR). Il construit le chemin pragmatique entre ce qui a été diagnostiqué et ce que l'organisation doit accomplir.
- **Pourquoi cette table ?** Elle porte l'instance de feuille de route pour une mission, stocke la photographie d'entrée (`input_snapshot`) et suit le taux global d'avancement des actions (`completionPercentage`).

#### `recommendations` (Les orientations stratégiques macroscopiques)
- **Rôle métier :** Porte les recommandations à haut niveau (`REC-001`), leur domaine, leur axe, leur justification, leur niveau de priorité (`critical`, `high`, `medium`, `low`), leur statut (`proposed`, `validated`, `in_progress`, `implemented`, `rejected`) et leurs photographies d'entrée/sortie.
- **Pourquoi cette table ?** Une recommandation n'est pas une tâche unique : elle donne la vision et la direction cible, et se décline ensuite en une ou plusieurs actions concrètes.

#### `next_step_actions` (Le registre des actions opérationnelles)
- **Rôle métier :** Recense les unités de travail identifiables (`ACT-001`), leur type, leur horizon temporel (`immediate`, `short_term`, `medium_term`, `long_term`), leur responsable (`responsible_id` / `responsible_name`), leur date d'échéance (`due_date`), leur résultat concret attendu et leur résultat constaté (`actual_result`). Elle stocke directement les références probantes (`problem_reference`, `finding_reference`, `decision_reference`) évitant la prolifération de tables de liaison prématurées.
- **Architecture sobre :** Conformément au principe d'économie conceptuelle, la feuille de route n'impose pas de table SQL `roadmaps` ou `roadmap_phases` : elle est générée dynamiquement par regroupement des actions selon leur horizon (`immediate`, `short_term`, `medium_term`, `long_term`). De même, les affectations réutilisent directement le référentiel des utilisateurs (`users`) du cabinet.

---

### Domaine 5 : Les Résultats, leur Lignage et les Revues Qualité

Une fois les investigations menées et les outils exécutés, il faut capitaliser les découvertes et en vérifier la qualité.

#### `results` (Les constats et analyses métier)
- **Rôle métier :** Formalise ce qui a été appris et découvert (observations de terrain, données chiffrées, constats de dysfonctionnement, analyses de cause racine, recommandations préliminaires).
- **Pourquoi cette table ?** Une exécution technique d'outil produit des chiffres bruts ; un **résultat** apporte l'interprétation humaine du consultant. Un résultat peut être issu d'un outil, rédigé manuellement, ou déduit d'une observation directe.

#### `result_sources` (L'arbre de filiation des résultats)
- **Rôle métier :** Mémorise quels résultats ont permis de construire d'autres analyses de synthèse.
- **Pourquoi cette table ?** C'est le garant suprême de la crédibilité du cabinet. Si le rapport final affirme : *« Le point de vente B est structurellement déficitaire à cause de ses charges de personnel »*, la table des sources permet de prouver en un clic que ce constat s'appuie sur le Résultat n°14 (taux horaire), le Résultat n°22 (planning des équipes) et le Résultat n°31 (chiffre d'affaires horaire).

#### `reviews` & `review_events` (Le contrôle qualité interne)
- **Rôle métier :** Le circuit d'examen interne où le chef de mission ou l'associé contrôle le travail d'un consultant avant diffusion.
- **Pourquoi une relation polymorphique ?** Le cabinet a besoin d'examiner indifféremment une investigation terminée (`works`), un constat sensible (`results`) ou un projet de rapport (`deliverables`). Plutôt que de développer 3 modules de validation identiques, une table unique `reviews` gère ce processus, secondée par `review_events` qui garde la mémoire de tous les allers-retours (*« corrections demandées le 12/10 »*, *« nouvelle version soumise le 14/10 »*, *« approuvé le 15/10 »*).

---

### Domaine 6 : La Restitution, les Livrables & l'Espace Client

C'est la finalité contractuelle de la mission : transmettre les conclusions au client et obtenir sa validation formelle.

#### `deliverables` & `deliverable_versions` (Les rapports officiels)
- **Rôle métier :** Représente les documents contractuels promis au client (Rapport d'audit organisationnel, Plan de restructuration financière, Note de synthèse).
- **Pourquoi séparer le livrable de ses versions ?** Une mission peut comporter plusieurs livrables. Chaque livrable vit à travers des versions successives. Le modèle gère aussi bien des documents structurés rédigés en ligne que des fichiers bureautiques importés (PDF, Word, présentations PowerPoint). Dès qu'une version est envoyée au client, elle est **verrouillée en lecture seule**.

#### `client_users` (L'accès des interlocuteurs du client)
- **Rôle métier :** Comptes d'accès réservés aux représentants du client (DG, Directeur financier, Responsable de projet).
- **Pourquoi cette table ?** Elle assure une étanchéité absolue : le client ne voit que les livrables qui lui sont publiés et ses propres échanges. Il n'a aucun accès aux travaux préparatoires, aux brouillons non validés ni aux discussions internes de l'équipe.

#### `deliverable_comments` (Le dialogue autour du document)
- **Rôle métier :** Les observations, remarques et questions formulées par le client sur une version précise du rapport.
- **Pourquoi un `parent_id` ?** Pour permettre des échanges sous forme de fil de discussion (répondre directement à une interrogation du client pour éclaircir un chiffre).

#### `client_deliverable_decisions` (La validation officielle)
- **Rôle métier :** L'enregistrement légal de la décision du client (*Validé sans réserve*, *Réserves formulées*, *Rejet*).
- **Pourquoi cette table séparée des commentaires ?** Un client peut faire 5 remarques éditoriales (commentaires) tout en validant le livrable sur le fond. La décision formelle a une valeur contractuelle qui décharge le cabinet et permet de passer à la clôture de la mission.

---

## 4. Synthèse des tables du modèle consolidé

| Domaine | Tables métier |
|---|---|
| **1. Relation Client & Cadrage** | `clients`, `requests`, `initial_understandings`, `framings`, `framing_versions` |
| **2. Gestion des Missions** | `missions`, `mission_members` |
| **3. Organisation des Travaux** | `works`, `work_assignments` |
| **4. Outils Méthodologiques & Diagnostic 360°** | `tools`, `tool_versions`, `mission_tool_usages`, `work_tool_usages`, `tool_executions`, `mission_framing_sheets`, `diagnostic_scope_canvases`, `diagnostic_intake_registers`, `required_information`, `received_items`, `received_item_information`, `stakeholder_input_grids`, `stakeholders`, `expected_contributions`, `received_contributions`, `received_contribution_information`, `diagnostic_360_matrices`, `diagnostic_assessments`, `diagnostic_evidences`, `assessment_evidence`, `diagnostic_findings`, `diagnostic_axis_summaries`, `diagnostic_domain_summaries`, `problem_structuring_grids`, `structured_problems`, `problem_causes`, `problem_consequences`, `problem_relations`, `problem_evidences`, `problem_priority_assessments`, `diagnostic_restitution_decks`, `restitution_sections`, `restitution_contents`, `restitution_sources`, `restitution_versions`, `live_decision_captures`, `decision_exchanges`, `discussion_points`, `captured_decisions`, `decision_commitments`, `decision_sources`, `diagnostic_archive_packs`, `archive_categories`, `archive_items`, `archive_item_categories`, `archive_versions`, `archive_sources`, `next_step_routers`, `recommendations`, `next_step_actions` |
| **5. Résultats & Contrôle Qualité** | `results`, `result_sources`, `reviews`, `review_events` |
| **6. Livrables & Espace Client** | `deliverables`, `deliverable_versions`, `client_users`, `deliverable_comments`, `client_deliverable_decisions` |

- *(Note : la table `users` est fournie nativement par le framework Laravel et sert de référentiel d'authentification pour les collaborateurs et les clients. Elle porte désormais le rôle métier `role` [`dg`, `manager`, `mission_lead`, `consultant`, `expert`, `client`] ainsi que `client_id` pour rattacher les comptes clients à leur organisation et leur ouvrir le portail de dépôt de demandes et d'arbitrage des cadrages).*

#### Configuration des outils et autorisations par mission (`mission_tool_usages` & `mission_members`)
- **Rôle métier :** Lors de la création ou du paramétrage d'une mission par le DG ou le Manager, chaque outil méthodologique activé reçoit sa configuration propre (`allowed_roles`, droits par action `view`/`edit`/`submit`/`validate` et règles spécifiques) et les membres autorisés sont rattachés avec leur rôle contextuel sur la mission.
- **Pourquoi ce mécanisme ?** Il garantit le principe du moindre privilège configuré : un intervenant n'est autorisé à voir et exécuter dans une mission que les outils et actions explicitement ouverts pour son rôle sur ce dossier, sans jamais pouvoir modifier un périmètre ou valider un livrable hors de son mandat.

---

## 5. Les cinq règles fondamentales de conception

Ces règles résument la philosophie de CoreAssist et garantissent la cohérence du système :

1. **Un outil n'est pas une tâche** : la tâche est ce qu'un consultant doit accomplir ; l'outil est le moyen optionnel qu'il utilise pour y parvenir.
2. **Le catalogue appartient au cabinet, la configuration appartient à la mission** : modifier les paramètres d'un outil pour un client n'altère jamais la méthode générale du cabinet.
3. **Les outils ne se parlent pas entre eux, c'est la mission qui centralise** : tous les outils déposent leurs constats dans un pot commun de *Résultats*.
4. **Une version soumise est immuable** : qu'il s'agisse d'un cadrage ou d'un livrable, tout document transmis au client est gelé. Une retouche entraîne toujours la création d'une version supérieure.
5. **Approbation interne $\neq$ Validation client** : le visa technique d'un chef de mission est une revue interne ; la recette d'un rapport par le client est une décision contractuelle. Deux mécanismes étanches.
