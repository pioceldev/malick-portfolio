<?php
declare(strict_types=1);

/**
 * Contenus administrables.
 * Un champ ajouté ici apparaît automatiquement dans la liste, le formulaire
 * et la validation du back-office.
 */

function entities(): array
{
    static $entities = null;
    if (is_array($entities)) {
        return $entities;
    }

    $entities = [
        'projects' => [
            'table' => 'projects',
            'label' => 'Projets',
            'singular' => 'Projet',
            'icon' => '◆',
            'preview' => true,
            'listColumns' => [
                ['name' => 'title', 'label' => 'Titre'],
                ['name' => 'category', 'label' => 'Catégorie'],
                ['name' => 'year', 'label' => 'Année'],
                ['name' => 'featured', 'label' => 'À la une', 'type' => 'bool'],
                ['name' => 'published', 'label' => 'Publié', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Titre du projet', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'label' => 'Identifiant d’URL', 'type' => 'text', 'hint' => 'Généré depuis le titre si laissé vide.', 'slugFrom' => 'title'],
                ['name' => 'category', 'label' => 'Catégorie', 'type' => 'text', 'hint' => 'Ex. Site vitrine, Application web, UI/UX.'],
                ['name' => 'year', 'label' => 'Année', 'type' => 'text'],
                ['name' => 'summary', 'label' => 'Résumé court', 'type' => 'textarea', 'rows' => 3, 'hint' => 'Affiché sur la carte du projet.'],
                ['name' => 'description', 'label' => 'Description complète', 'type' => 'textarea', 'rows' => 10, 'hint' => 'Une ligne vide crée un nouveau paragraphe.'],
                ['name' => 'tech', 'label' => 'Technologies', 'type' => 'text', 'hint' => 'Séparées par des virgules. Ex. React, Node.js, MySQL'],
                ['name' => 'cover', 'label' => 'Image principale', 'type' => 'image'],
                ['name' => 'gallery', 'label' => 'Galerie', 'type' => 'gallery'],
                ['name' => 'demo_url', 'label' => 'Lien de démonstration', 'type' => 'url'],
                ['name' => 'repo_url', 'label' => 'Lien du dépôt', 'type' => 'url'],
                ['name' => 'status', 'label' => 'Statut', 'type' => 'select', 'options' => ['Livré', 'En cours', 'Prototype', 'Archivé']],
                ['name' => 'featured', 'label' => 'Mettre à la une', 'type' => 'boolean'],
                ['name' => 'published', 'label' => 'Publier sur le site', 'type' => 'boolean', 'default' => 1],
                ['name' => 'sort', 'label' => 'Ordre d’affichage', 'type' => 'number', 'default' => 0],
            ],
        ],

        'skills' => [
            'table' => 'skills',
            'label' => 'Compétences',
            'singular' => 'Compétence',
            'icon' => '◇',
            'listColumns' => [
                ['name' => 'name', 'label' => 'Nom'],
                ['name' => 'category', 'label' => 'Catégorie'],
                ['name' => 'level', 'label' => 'Niveau'],
                ['name' => 'published', 'label' => 'Publié', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Nom', 'type' => 'text', 'required' => true],
                ['name' => 'category', 'label' => 'Catégorie', 'type' => 'select', 'options' => SKILL_CATEGORIES],
                ['name' => 'level', 'label' => 'Niveau (0-100)', 'type' => 'number', 'min' => 0, 'max' => 100, 'hint' => 'Laisser à 0 pour masquer la jauge.'],
                ['name' => 'icon', 'label' => 'Icône ou logo (texte, emoji ou URL)', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
                ['name' => 'published', 'label' => 'Publier sur le site', 'type' => 'boolean', 'default' => 1],
                ['name' => 'sort', 'label' => 'Ordre d’affichage', 'type' => 'number', 'default' => 0],
            ],
        ],

        'experiences' => [
            'table' => 'experiences',
            'label' => 'Parcours',
            'singular' => 'Étape',
            'icon' => '◈',
            'listColumns' => [
                ['name' => 'title', 'label' => 'Titre'],
                ['name' => 'org', 'label' => 'Organisation'],
                ['name' => 'kind', 'label' => 'Type'],
                ['name' => 'start_date', 'label' => 'Début'],
                ['name' => 'published', 'label' => 'Publié', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'kind', 'label' => 'Type', 'type' => 'select', 'options' => EXPERIENCE_KINDS],
                ['name' => 'title', 'label' => 'Intitulé', 'type' => 'text', 'required' => true],
                ['name' => 'org', 'label' => 'Entreprise / école', 'type' => 'text'],
                ['name' => 'start_date', 'label' => 'Date de début', 'type' => 'text', 'hint' => 'Ex. 2023, Janvier 2023'],
                ['name' => 'end_date', 'label' => 'Date de fin', 'type' => 'text', 'hint' => 'Laisser vide ou « Aujourd’hui » si en cours.'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 6],
                ['name' => 'url', 'label' => 'Lien externe', 'type' => 'url'],
                ['name' => 'document', 'label' => 'Document (PDF, image)', 'type' => 'file'],
                ['name' => 'published', 'label' => 'Publier sur le site', 'type' => 'boolean', 'default' => 1],
                ['name' => 'sort', 'label' => 'Ordre d’affichage', 'type' => 'number', 'default' => 0],
            ],
        ],

        'services' => [
            'table' => 'services',
            'label' => 'Services',
            'singular' => 'Service',
            'icon' => '◉',
            'listColumns' => [
                ['name' => 'title', 'label' => 'Titre'],
                ['name' => 'published', 'label' => 'Publié', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Titre', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 4],
                ['name' => 'icon', 'label' => 'Numéro / icône', 'type' => 'text'],
                ['name' => 'published', 'label' => 'Publier sur le site', 'type' => 'boolean', 'default' => 1],
                ['name' => 'sort', 'label' => 'Ordre d’affichage', 'type' => 'number', 'default' => 0],
            ],
        ],

        'process' => [
            'table' => 'process_steps',
            'label' => 'Processus',
            'singular' => 'Étape de processus',
            'icon' => '◎',
            'listColumns' => [
                ['name' => 'title', 'label' => 'Titre'],
                ['name' => 'published', 'label' => 'Publié', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Titre', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 4],
                ['name' => 'published', 'label' => 'Publier sur le site', 'type' => 'boolean', 'default' => 1],
                ['name' => 'sort', 'label' => 'Ordre d’affichage', 'type' => 'number', 'default' => 0],
            ],
        ],

        'testimonials' => [
            'table' => 'testimonials',
            'label' => 'Témoignages',
            'singular' => 'Témoignage',
            'icon' => '❖',
            'listColumns' => [
                ['name' => 'name', 'label' => 'Nom'],
                ['name' => 'company', 'label' => 'Entreprise'],
                ['name' => 'rating', 'label' => 'Note'],
                ['name' => 'published', 'label' => 'Publié', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Nom', 'type' => 'text', 'required' => true],
                ['name' => 'role', 'label' => 'Fonction', 'type' => 'text'],
                ['name' => 'company', 'label' => 'Entreprise', 'type' => 'text'],
                ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'rows' => 5, 'required' => true],
                ['name' => 'rating', 'label' => 'Note (1-5)', 'type' => 'number', 'min' => 0, 'max' => 5, 'hint' => '0 pour ne pas afficher de note.'],
                ['name' => 'photo', 'label' => 'Photo', 'type' => 'image'],
                ['name' => 'published', 'label' => 'Publier sur le site', 'type' => 'boolean', 'default' => 1],
                ['name' => 'sort', 'label' => 'Ordre d’affichage', 'type' => 'number', 'default' => 0],
            ],
        ],

        'navigation' => [
            'table' => 'nav_items',
            'label' => 'Navigation',
            'singular' => 'Lien de navigation',
            'icon' => '☰',
            'listColumns' => [
                ['name' => 'label', 'label' => 'Libellé'],
                ['name' => 'href', 'label' => 'Cible'],
                ['name' => 'published', 'label' => 'Visible', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'label', 'label' => 'Libellé', 'type' => 'text', 'required' => true],
                ['name' => 'href', 'label' => 'Cible', 'type' => 'text', 'required' => true, 'hint' => 'Ex. #projets ou /projets/mon-projet'],
                ['name' => 'published', 'label' => 'Afficher dans le menu', 'type' => 'boolean', 'default' => 1],
                ['name' => 'sort', 'label' => 'Ordre d’affichage', 'type' => 'number', 'default' => 0],
            ],
        ],
    ];

    return $entities;
}

/** Entité par clé, ou null. */
function get_entity(string $key): ?array
{
    $entities = entities();
    return $entities[$key] ?? null;
}

/** Clés des entités proposées dans le menu « Contenu ». */
function content_entity_keys(): array
{
    return ['projects', 'skills', 'experiences', 'services', 'process', 'testimonials'];
}
