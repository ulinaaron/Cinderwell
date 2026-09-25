<?php
namespace Cinderwell_Media_Folders;

abstract class Importer_Base {
    abstract protected function source_taxonomy();
    abstract protected function source_label();

    public function is_active() {
        return taxonomy_exists($this->source_taxonomy());
    }

    public function count_items() {
        if (!$this->is_active()) return ['terms' => 0, 'attachments' => 0];

        $terms = get_terms([
            'taxonomy'   => $this->source_taxonomy(),
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) return ['terms' => 0, 'attachments' => 0];

        $attachment_count = 0;
        foreach ($terms as $term) {
            $attachment_count += $term->count;
        }

        return [
            'terms'       => count($terms),
            'attachments' => $attachment_count,
        ];
    }

    public function dry_run() {
        if (!$this->is_active()) return ['error' => $this->source_label() . ' taxonomy not found'];

        $terms = get_terms([
            'taxonomy'   => $this->source_taxonomy(),
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) return ['error' => 'Could not read taxonomy'];

        $preview = [];
        $sorted = $this->sort_by_depth($terms);
        $source_to_target = [];
        foreach ($sorted as $term) {
            $parent_id = $term->parent && isset($source_to_target[$term->parent]) ? $source_to_target[$term->parent] : 0;
            $existing = $this->find_imported_term($term->term_id);
            if (!$existing && $parent_id >= 0) {
                $existing = $this->find_term_by_name($term->name, $parent_id);
            }
            $source_to_target[$term->term_id] = $existing ? $existing['term_id'] : -1;

            $preview[] = [
                'source_id'      => $term->term_id,
                'source_name'    => $term->name,
                'source_parent'  => $term->parent,
                'attachments'    => $term->count,
                'cwmf_target'    => $existing ? $existing['term_id'] : 'new',
                'will_skip'      => (bool) $existing,
            ];
        }

        return ['preview' => $preview];
    }

    public function import() {
        if (!$this->is_active()) return ['error' => $this->source_label() . ' taxonomy not found'];

        $terms = get_terms([
            'taxonomy'   => $this->source_taxonomy(),
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) return ['error' => 'Could not read taxonomy'];

        $stats = [
            'created'              => 0,
            'reused'               => 0,
            'attachments_assigned' => 0,
            'attachments_reused'   => 0,
            'errors'               => 0,
        ];

        $sorted = $this->sort_by_depth($terms);
        $source_to_target = [];

        foreach ($sorted as $term) {
            $parent_id = $term->parent && isset($source_to_target[$term->parent]) ? $source_to_target[$term->parent] : 0;

            $target = $this->find_imported_term($term->term_id);
            if (!$target) {
                $target = $this->find_term_by_name($term->name, $parent_id);
            }
            if ($target) {
                $stats['reused']++;
                $target_id = $target['term_id'];
                $target_term = get_term($target_id, Folder_Taxonomy::TAXONOMY);
                if ($target_term && !is_wp_error($target_term) && intval($target_term->parent) !== intval($parent_id)) {
                    $updated = wp_update_term($target_id, Folder_Taxonomy::TAXONOMY, ['parent' => $parent_id]);
                    if (is_wp_error($updated)) $stats['errors']++;
                }
            } else {
                $args = ['parent' => $parent_id];
                if (!empty($term->slug)) $args['slug'] = sanitize_title($term->slug);

                $new_term = wp_insert_term($term->name, Folder_Taxonomy::TAXONOMY, $args);

                if (is_wp_error($new_term)) {
                    $stats['errors']++;
                    continue;
                }

                $target_id = $new_term['term_id'];
                $stats['created']++;
            }

            $source_to_target[$term->term_id] = $target_id;
            update_term_meta($target_id, '_cwmf_import_source', $this->source_taxonomy());
            update_term_meta($target_id, '_cwmf_import_source_id', intval($term->term_id));

            $this->copy_term_meta($term->term_id, $target_id);

            $attachments = get_objects_in_term($term->term_id, $this->source_taxonomy());
            foreach ($attachments as $attachment_id) {
                $existing_terms = wp_get_object_terms($attachment_id, Folder_Taxonomy::TAXONOMY, ['fields' => 'ids']);
                if (is_wp_error($existing_terms)) $existing_terms = [];

                if (!in_array($target_id, $existing_terms, true)) {
                    $existing_terms[] = $target_id;
                } else {
                    $stats['attachments_reused']++;
                    continue;
                }
                $result = wp_set_object_terms($attachment_id, $existing_terms, Folder_Taxonomy::TAXONOMY);
                if (!is_wp_error($result)) {
                    $stats['attachments_assigned']++;
                } else {
                    $stats['errors']++;
                }
            }
        }

        return $stats;
    }

    protected function find_imported_term($source_id) {
        $terms = get_terms([
            'taxonomy'   => Folder_Taxonomy::TAXONOMY,
            'hide_empty' => false,
            'number'     => 1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'   => '_cwmf_import_source',
                    'value' => $this->source_taxonomy(),
                ],
                [
                    'key'   => '_cwmf_import_source_id',
                    'value' => intval($source_id),
                    'type'  => 'NUMERIC',
                ],
            ],
        ]);

        if (is_wp_error($terms) || empty($terms)) return null;
        return ['term_id' => $terms[0]->term_id, 'name' => $terms[0]->name];
    }

    protected function find_term_by_name($name, $parent_id = 0) {
        $terms = get_terms([
            'taxonomy'   => Folder_Taxonomy::TAXONOMY,
            'name'       => $name,
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms) || empty($terms)) return null;

        foreach ($terms as $term) {
            if (intval($term->parent) === intval($parent_id)) {
                return ['term_id' => $term->term_id, 'name' => $term->name];
            }
        }

        return null;
    }

    protected function copy_term_meta($source_id, $target_id) {
        $meta = get_term_meta($source_id);
        foreach ($meta as $key => $values) {
            if (strpos($key, 'color') !== false) {
                foreach ($values as $value) {
                    if (preg_match('/^#[0-9a-f]{6}$/i', $value)) {
                        update_term_meta($target_id, '_cwmf_folder_color', $value);
                    }
                }
            }
            if (strpos($key, 'order') !== false) {
                foreach ($values as $value) {
                    update_term_meta($target_id, '_cwmf_folder_order', intval($value));
                }
            }
        }
    }

    protected function sort_by_depth($terms) {
        $sorted  = [];
        $visited = [];
        $map     = [];
        foreach ($terms as $t) $map[$t->term_id] = $t;

        $visit = function ($term) use (&$visit, &$sorted, &$visited, $map) {
            if (isset($visited[$term->term_id])) return;
            $visited[$term->term_id] = true;

            if ($term->parent && isset($map[$term->parent])) {
                $visit($map[$term->parent]);
            }
            $sorted[] = $term;
        };

        foreach ($terms as $term) $visit($term);
        return $sorted;
    }
}
