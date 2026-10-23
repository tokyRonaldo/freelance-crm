# FreelanceCRM

**Un mini-CRM pour freelances : centraliser ses contacts, suivre ses opportunités commerciales et ne plus oublier une relance.**

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.4%20LTS-000000?logo=symfony&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?logo=tailwindcss&logoColor=white)

---

## Le problème résolu

Un freelance qui prospecte se retrouve vite avec des informations éparpillées : des prospects trouvés sur LinkedIn, des échanges par e-mail, des devis en attente de réponse, des relances à faire « dans quelques jours »… et des clients potentiels perdus par simple manque d'organisation.

FreelanceCRM regroupe tout dans une seule interface : qui est le contact, où en est chaque projet, ce qui s'est dit, et ce qu'il faut faire ensuite.



### Compte et accès
- Inscription, connexion et déconnexion (avec fenêtre de confirmation)
- Réinitialisation du mot de passe
- Page de profil
- Page d'accueil publique ; un utilisateur connecté est redirigé vers son tableau de bord et ne voit plus les pages de connexion et d'inscription
- Espace strictement privé : chaque freelance ne voit que ses propres données

### Contacts (prospects et clients)
- Création, modification et suppression
- Recherche par nom, entreprise ou e-mail, filtre par type, pagination
- Conversion d'un prospect en client en un clic
- Création rapide d'un contact depuis le formulaire d'opportunité, sans quitter la page (fenêtre modale, le contact créé est sélectionné automatiquement)
- Suppression refusée tant que le contact possède des opportunités
- Fiche complète : coordonnées, notes, opportunités, tâches à faire et historique des échanges

### Opportunités commerciales
- Cinq statuts : Nouveau, Contacté, Négociation, Gagné, Perdu
- Montant estimé, description, contact associé
- Une opportunité ne peut être rattachée qu'à un contact du même utilisateur
- Liste filtrable par statut, avec compteur et total

### Historique des échanges
- Appel, e-mail, réunion, message LinkedIn, note
- Timeline sur la fiche du contact, avec rattachement facultatif à une opportunité
- Page globale avec recherche, filtre par type et pagination
- Enregistrer un échange ne **envoie** pas de message : c'est une trace de ce qui s'est passé

### Tâches et relances
- Priorité (basse, normale, haute) et statut (à faire, en cours, terminée)
- Rattachement facultatif à un contact et à une opportunité
- Tâches regroupées en **en retard**, **aujourd'hui**, **à venir** et **terminées**
- Une tâche est « en retard » quand son échéance est passée et qu'elle n'est pas terminée : c'est calculé, pas stocké
- Date d'achèvement renseignée automatiquement

### Tableau de bord
- Indicateurs clés : contacts, prospects, opportunités ouvertes et gagnées, valeur des opportunités ouvertes, tâches en retard
- Graphiques Chart.js : répartition des opportunités par statut, opportunités gagnées par mois
- Activité récente
- Tous les chiffres sont calculés côté serveur à partir des données de l'utilisateur connecté

## Sécurité

La règle centrale du projet : **un utilisateur ne doit jamais accéder aux données d'un autre, même en modifiant une URL.**

- Chaque contrôleur vérifie le propriétaire de l'objet demandé et répond par une erreur 403 sinon
- Les requêtes Doctrine filtrent elles-mêmes par propriétaire (par exemple `searchByOwner()`), au lieu de se contenter de masquer des boutons dans Twig
- Les listes déroulantes des formulaires (contacts, opportunités) sont limitées aux données de l'utilisateur côté serveur, donc inutilisables pour rattacher un enregistrement à un compte tiers
- Toutes les actions qui modifient des données passent par POST avec un jeton CSRF
- Mots de passe hachés avec le composant de sécurité de Symfony
- Validation côté serveur de tous les formulaires
- Les liens vers un site web saisi par l'utilisateur ne sont créés que pour les adresses `http://` et `https://`
- Les redirections de retour (`_back`) n'acceptent que des chemins internes

## Stack technique

| Couche | Technologie |
|---|---|
| Langage | PHP 8.2+ |
| Framework | Symfony 7.4 LTS |
| Base de données | MySQL 8, Doctrine ORM et migrations |
| Vues | Twig, rendu côté serveur |
| Interface | Tailwind CSS, Material Symbols, polices Plus Jakarta Sans et Inter |
| Graphiques | Chart.js |
| Pagination | KnpPaginatorBundle |
| Tests | PHPUnit et outils de test de Symfony |

## Modèle de données

```mermaid
erDiagram
    USER ||--o{ CONTACT : "possède"
    USER ||--o{ OPPORTUNITY : "possède"
    USER ||--o{ ACTIVITY : "possède"
    USER ||--o{ TASK : "possède"
    CONTACT ||--o{ OPPORTUNITY : "a"
    CONTACT ||--o{ ACTIVITY : "a"
    CONTACT |o--o{ TASK : "concerne"
    OPPORTUNITY |o--o{ ACTIVITY : "concerne"
    OPPORTUNITY |o--o{ TASK : "concerne"

    USER {
        string email
        string password
        string firstName
        string lastName
    }
    CONTACT {
        string lastName
        string firstName
        string company
        string email
        string type "prospect ou client"
    }
    OPPORTUNITY {
        string title
        decimal amount
        enum status
        datetime closedAt
    }
    ACTIVITY {
        enum type
        string title
        datetime occurredAt
    }
    TASK {
        string title
        datetime dueAt
        enum priority
        enum status
    }
```

Un contact et une opportunité sont deux notions distinctes : un client peut avoir plusieurs opportunités (un site vitrine aujourd'hui, une application métier plus tard), et changer le type d'un contact ne modifie pas ses opportunités.

## Choix de conception

- **Enums PHP 8.1** (`OpportunityStatus`, `ActivityKind`, `TaskStatus`, `TaskPriority`) plutôt que des chaînes libres : libellés, icônes et classes d'affichage sont portés par l'enum
- **Pas de statut « en retard » en base** : il se déduit de la date d'échéance et du statut, ce qui évite les données qui deviennent fausses avec le temps
- **Éviter le problème N+1** : les listes chargent leurs relations avec une jointure (`addSelect`) au lieu d'une requête par ligne
- **Post / Redirect / Get** après chaque formulaire : actualiser la page ne renvoie pas les données
- **Thème de formulaire Twig** centralisé pour que tous les formulaires partagent le même style
- **Modales natives** (`<dialog>`) et JavaScript sans bibliothèque pour la déconnexion et la création rapide de contact

## Installation

### Prérequis

- PHP 8.2 ou plus (avec les extensions `pdo_mysql`, `intl`, `mbstring`, `ctype`, `iconv`)
- [Composer](https://getcomposer.org/)
- MySQL 8
- (Facultatif) la [Symfony CLI](https://symfony.com/download)

### Étapes

```bash
# 1. Récupérer le projet
git clone https://github.com/<votre-compte>/freelance-crm.git
cd freelance-crm

# 2. Installer les dépendances
composer install

# 3. Configurer la base de données (fichier non versionné)
cp .env .env.local
```

Dans `.env.local`, adaptez la connexion à votre MySQL :

```dotenv
DATABASE_URL="mysql://utilisateur:mot_de_passe@127.0.0.1:3306/freelance_crm?serverVersion=8.0.32&charset=utf8mb4"
```

```bash
# 4. Créer la base et appliquer les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Lancer le serveur
symfony serve
# ou, sans la Symfony CLI :
php -S 127.0.0.1:8000 -t public
```

Ouvrez ensuite <http://localhost:8000>, créez un compte, et c'est parti.

> Tailwind CSS, Chart.js et les polices sont chargés depuis des CDN : une connexion Internet est nécessaire. Pour une mise en production, il est préférable de compiler Tailwind et d'héberger les ressources soi-même.


## Structure du projet

```text
src/
├── Controller/    Dashboard, Contact, Opportunity, Activity, Task, sécurité
├── Entity/        User, Contact, Opportunity, Activity, Task
├── Enum/          OpportunityStatus, ActivityKind, TaskStatus, TaskPriority
├── Form/          Formulaires Symfony et validation
├── Repository/    Requêtes Doctrine, toujours filtrées par propriétaire
└── Service/       Logique métier du tableau de bord
templates/
├── base.html.twig            Gabarit de base
├── layout_app.html.twig      Menu latéral, en-tête, modales
├── form/                     Thème de formulaire et composants partagés
└── contact/, opportunity/, activity/, task/, dashboard/, security/, home/
```

## Évolutions envisagées

- Tableau Kanban pour déplacer les opportunités d'un statut à l'autre
- Envoi d'e-mails de relance et notifications
- Import et export de contacts (CSV)
- Génération de devis et suivi des factures
- API REST pour un futur frontend React ou Next.js
- Automatisations avec n8n

