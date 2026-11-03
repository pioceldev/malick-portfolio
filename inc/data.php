<?php
declare(strict_types=1);

/**
 * Données publiques : projets, compétences, parcours, services, processus,
 * témoignages, navigation et réseaux sociaux.
 */

function map_project(array $row): array
{
    $gallery = json_decode((string) ($row['gallery'] ?? '[]'), true);
    $row['techList'] = parse_list($row['tech'] ?? '');
    $row['galleryList'] = is_array($gallery) ? array_values(array_filter($gallery)) : [];
    $row['coverUrl'] = image_url($row['cover'] ?? '');
    $row['demo'] = (string) ($row['demo_url'] ?? '');
    $row['repo'] = (string) ($row['repo_url'] ?? '');
    return $row;
}

function public_projects(): array
{
    $sql = "SELECT * FROM projects WHERE published = 1
            ORDER BY featured DESC, sort ASC, COALESCE(NULLIF(year, ''), '0') DESC, id DESC";
    return array_map('map_project', rows($sql));
}

function project_by_slug(string $slug): ?array
{
    $found = row('SELECT * FROM projects WHERE slug = ? AND published = 1', [$slug]);
    return $found ? map_project($found) : null;
}

/** Charge tout le contenu affiché par les pages publiques. */
function public_data(): array
{
    $settings = settings();
    $projects = public_projects();

    $skills = rows('SELECT * FROM skills WHERE published = 1 ORDER BY sort ASC, id ASC');
    $skillGroups = [];
    foreach ($skills as $skill) {
        $category = trim((string) ($skill['category'] ?? '')) !== '' ? $skill['category'] : 'Autres';
        $skillGroups[$category][] = $skill;
    }

    $socials = [];
    foreach (SOCIAL_FIELDS as $field) {
        $url = trim((string) ($settings[$field['key']] ?? ''));
        if ($url !== '') {
            $socials[] = ['key' => $field['key'], 'label' => $field['label'], 'url' => $url];
        }
    }

    return [
        'settings' => $settings,
        'nav' => rows('SELECT * FROM nav_items WHERE published = 1 ORDER BY sort ASC, id ASC'),
        'projects' => $projects,
        'skills' => $skills,
        'skillGroups' => $skillGroups,
        'experiences' => rows('SELECT * FROM experiences WHERE published = 1 ORDER BY sort ASC, id DESC'),
        'services' => rows('SELECT * FROM services WHERE published = 1 ORDER BY sort ASC, id ASC'),
        'processSteps' => rows('SELECT * FROM process_steps WHERE published = 1 ORDER BY sort ASC, id ASC'),
        'testimonials' => rows('SELECT * FROM testimonials WHERE published = 1 ORDER BY sort ASC, id ASC'),
        'socials' => $socials,
        'cv' => trim((string) ($settings['cv_file'] ?? '')),
    ];
}

/* ---------------------------------------------------------------------- */
/* URLs                                                                   */
/* ---------------------------------------------------------------------- */

function project_url(string $slug): string
{
    return u('/projets/' . rawurlencode($slug));
}

function admin_url(string $page = '', array $query = []): string
{
    $url = u('/admin/' . ltrim($page, '/'));
    if ($query !== []) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    }
    return $url;
}

function entity_list_url(string $key): string
{
    return admin_url('list.php', ['entity' => $key]);
}

function entity_new_url(string $key): string
{
    return admin_url('form.php', ['entity' => $key]);
}

function entity_edit_url(string $key, int $id): string
{
    return admin_url('form.php', ['entity' => $key, 'id' => $id]);
}
