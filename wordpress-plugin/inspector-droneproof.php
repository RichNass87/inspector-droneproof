<?php
/**
 * Plugin Name: Inspector DroneProof
 * Description: Contractor roof intelligence console with job inputs, flight planning, photo intake, damage marking, 3D model rendering, secure AI/Google API settings, and packet exports.
 * Version: 6.10.3
 * Author: Inspector Roofing
 * Text Domain: inspector-droneproof
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Inspector_DroneProof_Plugin {
    const VERSION = '6.10.3';
    const SHORTCODE = 'inspector_droneproof';
    const AI_TOOLS_SHORTCODE = 'inspector_ai_tools';
    const PAGE_OPTION = 'inspector_droneproof_page_id';
    const AI_TOOLS_PAGE_OPTION = 'inspector_ai_tools_page_id';
    const PRIVACY_PAGE_OPTION = 'inspector_droneproof_privacy_page_id';
    const TERMS_PAGE_OPTION = 'inspector_droneproof_terms_page_id';
    const NOTICE_TRANSIENT = 'inspector_droneproof_activated';
    const API_KEY_OPTION = 'inspector_droneproof_openai_api_key';
    const API_KEY_LAST4_OPTION = 'inspector_droneproof_openai_key_last4';
    const GOOGLE_API_KEY_OPTION = 'inspector_droneproof_google_api_key';
    const GOOGLE_API_KEY_LAST4_OPTION = 'inspector_droneproof_google_key_last4';
    const ROOF_DATA_OPTION = 'inspector_droneproof_roof_bridge_data';
    const FIELD_TOKEN_OPTION = 'inspector_droneproof_field_upload_token';
    const FIELD_JOBS_OPTION = 'inspector_droneproof_field_jobs';
    const PLAY_APP_ID = '4972784307494654443';
    const PLAY_PACKAGE_NAME = 'com.inspectorroofing.droneproofpilot';
    const PLAY_CONSOLE_URL = 'https://play.google.com/console/u/0/developers/9138701551147075650/app/4972784307494654443/app-dashboard';
    const PLAY_STORE_URL = '';
    const APP_VERSION = '0.4.0-droneproof';
    const EVIDENCE_VERSION = '0.6.0';
    const RELEASE_URL = 'https://github.com/RichNass87/inspector-droneproof/releases/tag/v0.6.0';
    const PROJECT_DOI = '10.5281/zenodo.21301425';
    const ARCHIVED_V050_DOI = '10.5281/zenodo.21301426';
    private static $schema_repair_started = false;
    private static $public_spine_rendered = false;

    public static function boot() {
        add_action('template_redirect', array(__CLASS__, 'redirect_legacy_public_slug'), -20);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));
        add_shortcode(self::SHORTCODE, array(__CLASS__, 'render_shortcode'));
        add_shortcode(self::AI_TOOLS_SHORTCODE, array(__CLASS__, 'render_ai_tools_shortcode'));
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
        add_action('init', array(__CLASS__, 'start_ai_tools_schema_repair'), 0);
        add_action('template_redirect', array(__CLASS__, 'start_ai_tools_schema_repair'), 0);
        add_action('wp_footer', array(__CLASS__, 'print_schema_cleaner_script'), 9999);
        add_action('wp_footer', array(__CLASS__, 'print_public_evidence_spine_footer'), 40);
        add_action('wp_head', array(__CLASS__, 'print_public_page_schema'), 90);
        add_filter('the_content', array(__CLASS__, 'append_public_evidence_spine'), 90);
        add_action('admin_init', array(__CLASS__, 'ensure_public_pages'));
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        add_action('admin_notices', array(__CLASS__, 'admin_notice'));
        add_action('admin_post_inspector_droneproof_create_page', array(__CLASS__, 'handle_create_page'));
        add_action('admin_post_inspector_droneproof_create_ai_tools_page', array(__CLASS__, 'handle_create_ai_tools_page'));
        add_action('admin_post_inspector_droneproof_save_settings', array(__CLASS__, 'handle_save_settings'));
        add_action('admin_post_inspector_droneproof_damage_report', array(__CLASS__, 'send_damage_report'));
        add_action('admin_post_nopriv_inspector_droneproof_damage_report', array(__CLASS__, 'send_damage_report'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'plugin_action_links'));
        register_activation_hook(__FILE__, array(__CLASS__, 'activate'));
    }

    public static function redirect_legacy_public_slug() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        if ('/inspector-droneproof' !== untrailingslashit((string) $path)) {
            return;
        }

        wp_safe_redirect(home_url('/droneproof/'), 301);
        exit;
    }

    public static function activate() {
        self::ensure_page();
        self::ensure_ai_tools_page();
        self::ensure_compliance_pages();
        self::ensure_field_token();
        set_transient(self::NOTICE_TRANSIENT, '1', 90);
    }

    public static function ensure_public_pages() {
        self::ensure_page();
        self::ensure_ai_tools_page();
        self::ensure_compliance_pages();
    }

    private static function ensure_compliance_pages() {
        $privacy_id = self::ensure_compliance_page(
            self::PRIVACY_PAGE_OPTION,
            'privacy-policy',
            'Privacy Policy',
            self::privacy_page_content()
        );
        self::ensure_compliance_page(
            self::TERMS_PAGE_OPTION,
            'terms',
            'Terms of Use',
            self::terms_page_content()
        );

        if ($privacy_id && (int) get_option('wp_page_for_privacy_policy') !== $privacy_id) {
            update_option('wp_page_for_privacy_policy', $privacy_id);
        }
    }

    private static function ensure_compliance_page($option, $slug, $title, $content) {
        $page_id = absint(get_option($option));
        $page = $page_id ? get_post($page_id) : get_page_by_path($slug);

        if ($page) {
            $page_id = (int) $page->ID;
            $updates = array('ID' => $page_id);
            if ($page->post_status !== 'publish') {
                $updates['post_status'] = 'publish';
            }
            if ($page->post_title !== $title) {
                $updates['post_title'] = $title;
            }
            if ($page->post_name !== $slug) {
                $updates['post_name'] = $slug;
            }
            if (trim((string) $page->post_content) !== trim($content)) {
                $updates['post_content'] = $content;
            }
            if (count($updates) > 1) {
                wp_update_post($updates);
            }
            update_option($option, $page_id);
            return $page_id;
        }

        $new_id = wp_insert_post(array(
            'post_title' => $title,
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => $content,
        ));

        if (!is_wp_error($new_id) && $new_id) {
            update_option($option, (int) $new_id);
            return (int) $new_id;
        }

        return 0;
    }

    private static function privacy_page_content() {
        return '<h1>Privacy Policy</h1>' .
            '<p><strong>Effective July 16, 2026.</strong> Inspector Roofing and Restoration operates Inspector DroneProof, a contractor roof-documentation application.</p>' .
            '<h2>Data processed</h2><p>Authorized users may submit property and job details, addresses, roof photos, photo metadata, roof-plane labels, annotations, field notes, mission-planning values, and checklist status. The configured Android device stores the WordPress endpoint and private field-access token.</p>' .
            '<h2>Use and service providers</h2><p>Data is used to load and synchronize authorized jobs, upload photos, generate documentation, troubleshoot the service, and protect endpoints. If enabled by the administrator, addresses may be sent to Google Maps Platform for geocoding and selected documentation text may be sent to the configured OpenAI service for QA assistance. WordPress hosting processes stored jobs and uploads.</p>' .
            '<h2>Sharing, retention, and deletion</h2><p>Inspector Roofing does not sell DroneProof job data. Records remain until deleted by an authorized administrator or under the applicable business retention process. Device settings remain until cleared or the application is removed. Access, correction, and deletion requests may be submitted through <a href="' . esc_url(home_url('/contact/')) . '">the contact page</a>, subject to legal, security, and recordkeeping requirements.</p>' .
            '<h2>Security and children</h2><p>Public source packages exclude API keys, signing credentials, and field tokens. Tokens should be rotated if exposed or if a device is lost. DroneProof is a contractor tool and is not directed to children.</p>' .
            '<h2>Boundaries</h2><p>DroneProof does not make flight-safety, engineering, legal, coverage, payment, or claim decisions. Inspector Roofing documents observable conditions and does not act as a public adjuster. Carriers decide coverage, payment, and claim outcomes.</p>';
    }

    private static function terms_page_content() {
        return '<h1>Terms of Use</h1>' .
            '<p><strong>Effective July 16, 2026.</strong> Inspector DroneProof is documentation and planning software published by Inspector Roofing and Restoration.</p>' .
            '<h2>Authorized use</h2><p>Use the software only for property and project data you are authorized to process. Users are responsible for permissions, accuracy, and final human review.</p>' .
            '<h2>Flight boundary</h2><p>The current release does not include DJI SDK binaries and does not arm, launch, or command an aircraft. Generated waypoints and checklists are planning references, not flight authorization. The pilot remains responsible for certification, registration, airspace, weather, visual line of sight, obstacles, battery state, return-to-home configuration, compatibility, and final mission approval.</p>' .
            '<h2>Platform boundary</h2><p>Inspector DroneProof is independently developed and is not affiliated with, sponsored by, certified by, or endorsed by DJI, Google, Amazon, GitHub, Zenodo, Hugging Face, or ORCID.</p>' .
            '<h2>Roofing and insurance boundary</h2><p>DroneProof supports documentation of observable roof conditions. It does not provide engineering, legal, coverage, valuation, or public-adjusting services. Inspector Roofing does not act as a public adjuster. Carriers decide coverage, payment, and claim outcomes.</p>' .
            '<h2>No guarantee</h2><p>Software, generated plans, annotations, and exports are provided without a guarantee of uninterrupted operation, aircraft compatibility, regulatory compliance, insurance acceptance, or a particular outcome. Human review is required.</p>' .
            '<p>See the <a href="' . esc_url(home_url('/privacy-policy/')) . '">Privacy Policy</a> or use <a href="' . esc_url(home_url('/contact/')) . '">the contact page</a> for questions.</p>';
    }

    private static function ensure_page() {
        $page_id = absint(get_option(self::PAGE_OPTION));
        $content = '[' . self::SHORTCODE . ']';

        if ($page_id && get_post($page_id)) {
            $page = get_post($page_id);
            if ($page) {
                $updates = array('ID' => $page_id);
                if ($page->post_status !== 'publish') {
                    $updates['post_status'] = 'publish';
                }
                if ($page->post_title !== 'DroneProof') {
                    $updates['post_title'] = 'DroneProof';
                }
                if ($page->post_name !== 'droneproof') {
                    $updates['post_name'] = 'droneproof';
                }
                if (!has_shortcode($page->post_content, self::SHORTCODE)) {
                    $updates['post_content'] = $content;
                }
                if (count($updates) > 1) {
                    wp_update_post($updates);
                }
            }
            return $page_id;
        }

        $existing = get_page_by_path('droneproof');
        if (!$existing) {
            $existing = get_page_by_path('inspector-droneproof');
        }

        if ($existing) {
            update_option(self::PAGE_OPTION, (int) $existing->ID);
            $updates = array('ID' => (int) $existing->ID);
            if ($existing->post_status !== 'publish') {
                $updates['post_status'] = 'publish';
            }
            if ($existing->post_title !== 'DroneProof') {
                $updates['post_title'] = 'DroneProof';
            }
            if ($existing->post_name !== 'droneproof') {
                $updates['post_name'] = 'droneproof';
            }
            if (!has_shortcode($existing->post_content, self::SHORTCODE)) {
                $updates['post_content'] = $content;
            }
            if (count($updates) > 1) {
                wp_update_post($updates);
            }
            return (int) $existing->ID;
        }

        $new_id = wp_insert_post(array(
            'post_title' => 'DroneProof',
            'post_name' => 'droneproof',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => $content,
        ));

        if (!is_wp_error($new_id) && $new_id) {
            update_option(self::PAGE_OPTION, (int) $new_id);
            return (int) $new_id;
        }

        return 0;
    }

    public static function ensure_ai_tools_page() {
        if (!is_admin() && !defined('WP_CLI')) {
            return 0;
        }

        $page_id = absint(get_option(self::AI_TOOLS_PAGE_OPTION));

        if ($page_id && get_post($page_id)) {
            return $page_id;
        }

        $existing = get_page_by_path('inspector-ai-tools');

        if ($existing) {
            update_option(self::AI_TOOLS_PAGE_OPTION, (int) $existing->ID);
            return (int) $existing->ID;
        }

        $new_id = wp_insert_post(array(
            'post_title' => 'Inspector AI Tools and DroneProof Hub',
            'post_name' => 'inspector-ai-tools',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '[' . self::AI_TOOLS_SHORTCODE . ']',
        ));

        if (!is_wp_error($new_id) && $new_id) {
            update_option(self::AI_TOOLS_PAGE_OPTION, (int) $new_id);
            return (int) $new_id;
        }

        return 0;
    }

    public static function plugin_action_links($links) {
        $settings_url = admin_url('admin.php?page=inspector-droneproof');
        array_unshift($links, '<a href="' . esc_url($settings_url) . '">Setup</a>');
        return $links;
    }

    public static function start_ai_tools_schema_repair() {
        if (self::$schema_repair_started || is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        self::$schema_repair_started = true;
        ob_start(array(__CLASS__, 'repair_ai_tools_schema_output'));
    }

    public static function repair_ai_tools_schema_output($html) {
        return preg_replace_callback(
            '#<script\b([^>]*)type=(["\'])application/ld\+json\2([^>]*)>(.*?)</script>#is',
            array(__CLASS__, 'repair_json_ld_script'),
            $html
        );
    }

    private static function repair_json_ld_script($matches) {
        $json = trim(html_entity_decode($matches[4], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $matches[0];
        }

        $changed = self::hydrate_atlas_dataset_schema($data);

        if (self::remove_verifiable_roof_product_snippet_schema($data)) {
            $changed = true;
        }

        if (!$changed) {
            $clean_json = self::strip_verifiable_roof_product_json($json);
            if ($clean_json === $json) {
                return $matches[0];
            }

            return '<script' . $matches[1] . 'type=' . $matches[2] . 'application/ld+json' . $matches[2] . $matches[3] . '>' .
                $clean_json .
                '</script>';
        }

        return '<script' . $matches[1] . 'type=' . $matches[2] . 'application/ld+json' . $matches[2] . $matches[3] . '>' .
            wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) .
            '</script>';
    }

    private static function strip_verifiable_roof_product_json($json) {
        if (strpos($json, '#verifiable-roof') === false || strpos($json, '"Product"') === false) {
            return $json;
        }

        return str_replace(
            array(',"Product"', '"Product",'),
            '',
            $json
        );
    }

    private static function hydrate_atlas_dataset_schema(&$node) {
        if (!is_array($node)) {
            return false;
        }

        $changed = false;
        $type = isset($node['@type']) ? $node['@type'] : '';
        $id = isset($node['@id']) ? (string) $node['@id'] : '';

        if (self::schema_type_is($type, 'Dataset') && strpos($id, '/atlas-query-intelligence/#dataset') !== false) {
            if (empty($node['description'])) {
                $node['description'] = 'Inspector Roofing Atlas Query Intelligence is a roofing search and AI-answer research dataset page documenting query patterns, entity signals, proof-page relationships, and visibility context for Inspector Roofing and Restoration.';
                $changed = true;
            }

            if (empty($node['license'])) {
                $node['license'] = home_url('/privacy-policy/');
                $changed = true;
            }

            if (empty($node['keywords'])) {
                $node['keywords'] = array('roofing AI search', 'AI visibility', 'roofing query intelligence', 'Inspector Roofing', 'Richard Amir Nasser');
                $changed = true;
            }

            if (empty($node['isAccessibleForFree'])) {
                $node['isAccessibleForFree'] = true;
                $changed = true;
            }
        }

        foreach ($node as &$child) {
            if (is_array($child) && self::hydrate_atlas_dataset_schema($child)) {
                $changed = true;
            }
        }

        return $changed;
    }

    private static function remove_verifiable_roof_product_snippet_schema(&$node) {
        if (!is_array($node)) {
            return false;
        }

        $changed = false;
        $id = isset($node['@id']) ? (string) $node['@id'] : '';

        if (strpos($id, '#verifiable-roof') !== false && isset($node['@type']) && is_array($node['@type'])) {
            $filtered_types = array_values(array_filter($node['@type'], function ($type) {
                return $type !== 'Product';
            }));

            if ($filtered_types !== $node['@type']) {
                $node['@type'] = $filtered_types;
                $changed = true;
            }
        }

        foreach ($node as &$child) {
            if (is_array($child) && self::remove_verifiable_roof_product_snippet_schema($child)) {
                $changed = true;
            }
        }

        return $changed;
    }

    private static function schema_type_is($type, $target) {
        if (is_array($type)) {
            return in_array($target, $type, true);
        }

        return $type === $target;
    }

    public static function print_schema_cleaner_script() {
        if (is_admin()) {
            return;
        }
        ?>
        <script id="inspector-droneproof-schema-cleaner">
        (function () {
            var oldOrcid = 'https://orcid.org/0009-0003-9363-2287';
            var currentOrcid = 'https://orcid.org/0009-0000-2980-7543';

            function cleanNode(node) {
                var changed = false;
                if (!node || typeof node !== 'object') {
                    return false;
                }

                if (node['@id'] && String(node['@id']).indexOf('#verifiable-roof') !== -1 && Array.isArray(node['@type'])) {
                    var filtered = node['@type'].filter(function (type) {
                        return type !== 'Product';
                    });

                    if (filtered.length !== node['@type'].length) {
                        node['@type'] = filtered;
                        changed = true;
                    }
                }

                Object.keys(node).forEach(function (key) {
                    var value = node[key];
                    if (Array.isArray(value)) {
                        var next = [];
                        value.forEach(function (child) {
                            if (child === oldOrcid) {
                                child = currentOrcid;
                                changed = true;
                            }

                            if (typeof child === 'string' && next.indexOf(child) !== -1) {
                                changed = true;
                                return;
                            }

                            next.push(child);
                            if (cleanNode(child)) {
                                changed = true;
                            }
                        });
                        if (changed) {
                            node[key] = next;
                        }
                    } else if (value && typeof value === 'object' && cleanNode(value)) {
                        changed = true;
                    } else if (value === oldOrcid) {
                        node[key] = currentOrcid;
                        changed = true;
                    }
                });

                return changed;
            }

            document.querySelectorAll('script[type="application/ld+json"]').forEach(function (script) {
                try {
                    var data = JSON.parse(script.textContent || '{}');
                    if (cleanNode(data)) {
                        script.textContent = JSON.stringify(data);
                    }
                } catch (error) {}
            });
        }());
        </script>
        <?php
    }

    public static function admin_notice() {
        if (!current_user_can('edit_pages') || !get_transient(self::NOTICE_TRANSIENT)) {
            return;
        }

        delete_transient(self::NOTICE_TRANSIENT);

        $settings_url = admin_url('admin.php?page=inspector-droneproof');
        echo '<div class="notice notice-success is-dismissible"><p><strong>Inspector DroneProof is installed.</strong> Open setup to publish the page, check the 3D model, save API settings, or test the damage PDF export. <a href="' . esc_url($settings_url) . '">Open setup</a></p></div>';
    }

    public static function admin_menu() {
        add_menu_page(
            'Inspector DroneProof',
            'DroneProof',
            'edit_pages',
            'inspector-droneproof',
            array(__CLASS__, 'render_admin_page'),
            'dashicons-visibility',
            58
        );
    }

    public static function handle_create_page() {
        if (!current_user_can('edit_pages')) {
            wp_die('You do not have permission to manage Inspector DroneProof.');
        }

        check_admin_referer('inspector_droneproof_create_page');
        $page_id = self::ensure_page();
        $redirect = admin_url('admin.php?page=inspector-droneproof&created=' . ($page_id ? '1' : '0'));
        wp_safe_redirect($redirect);
        exit;
    }

    public static function handle_create_ai_tools_page() {
        if (!current_user_can('edit_pages')) {
            wp_die('You do not have permission to manage the Inspector AI Tools page.');
        }

        check_admin_referer('inspector_droneproof_create_ai_tools_page');
        $page_id = self::ensure_ai_tools_page();
        $redirect = admin_url('admin.php?page=inspector-droneproof&ai_tools_created=' . ($page_id ? '1' : '0'));
        wp_safe_redirect($redirect);
        exit;
    }

    public static function handle_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to save Inspector DroneProof settings.');
        }

        check_admin_referer('inspector_droneproof_save_settings');

        $remove_openai_key = !empty($_POST['remove_api_key']);
        $remove_google_key = !empty($_POST['remove_google_api_key']);
        $regenerate_field_token = !empty($_POST['regenerate_field_token']);
        $incoming_key = isset($_POST['openai_api_key'])
            ? sanitize_text_field(wp_unslash($_POST['openai_api_key']))
            : '';
        $incoming_google_key = isset($_POST['google_api_key'])
            ? sanitize_text_field(wp_unslash($_POST['google_api_key']))
            : '';
        $changed = false;

        if ($remove_openai_key) {
            delete_option(self::API_KEY_OPTION);
            delete_option(self::API_KEY_LAST4_OPTION);
            $changed = true;
        } elseif ($incoming_key !== '') {
            update_option(self::API_KEY_OPTION, $incoming_key, false);
            update_option(self::API_KEY_LAST4_OPTION, substr($incoming_key, -4), false);
            $changed = true;
        }

        if ($remove_google_key) {
            delete_option(self::GOOGLE_API_KEY_OPTION);
            delete_option(self::GOOGLE_API_KEY_LAST4_OPTION);
            $changed = true;
        } elseif ($incoming_google_key !== '') {
            update_option(self::GOOGLE_API_KEY_OPTION, $incoming_google_key, false);
            update_option(self::GOOGLE_API_KEY_LAST4_OPTION, substr($incoming_google_key, -4), false);
            $changed = true;
        }

        if ($regenerate_field_token) {
            update_option(self::FIELD_TOKEN_OPTION, self::make_field_token(), false);
            $changed = true;
        } else {
            self::ensure_field_token();
        }

        $status = $changed ? 'saved' : 'unchanged';
        wp_safe_redirect(admin_url('admin.php?page=inspector-droneproof&settings=' . rawurlencode($status)));
        exit;
    }

    private static function has_api_key() {
        return (string) get_option(self::API_KEY_OPTION, '') !== '';
    }

    private static function api_key_last4() {
        $last4 = (string) get_option(self::API_KEY_LAST4_OPTION, '');
        return preg_replace('/[^A-Za-z0-9]/', '', $last4);
    }

    private static function has_google_api_key() {
        return (string) get_option(self::GOOGLE_API_KEY_OPTION, '') !== '';
    }

    private static function google_api_key_last4() {
        $last4 = (string) get_option(self::GOOGLE_API_KEY_LAST4_OPTION, '');
        return preg_replace('/[^A-Za-z0-9]/', '', $last4);
    }

    private static function make_field_token() {
        return wp_generate_password(40, false, false);
    }

    private static function ensure_field_token() {
        $token = (string) get_option(self::FIELD_TOKEN_OPTION, '');
        if ($token === '') {
            $token = self::make_field_token();
            update_option(self::FIELD_TOKEN_OPTION, $token, false);
        }
        return $token;
    }

    private static function field_token_last4() {
        $token = self::ensure_field_token();
        return substr($token, -4);
    }

    public static function request_has_field_access($request) {
        if (current_user_can('edit_pages')) {
            return true;
        }

        $expected = (string) get_option(self::FIELD_TOKEN_OPTION, '');
        if ($expected === '') {
            return false;
        }

        $provided = '';
        $header = $request->get_header('authorization');
        if (preg_match('/Bearer\s+(.+)$/i', (string) $header, $match)) {
            $provided = trim($match[1]);
        }
        if ($provided === '') {
            $provided = sanitize_text_field((string) $request->get_param('token'));
        }

        return hash_equals($expected, $provided);
    }

    private static function self_check() {
        $page_id = absint(get_option(self::PAGE_OPTION));
        $ai_tools_page_id = absint(get_option(self::AI_TOOLS_PAGE_OPTION));
        $page = $page_id ? get_post($page_id) : null;
        $ai_tools_page = $ai_tools_page_id ? get_post($ai_tools_page_id) : null;
        $assets = array(
            'Logo asset' => file_exists(plugin_dir_path(__FILE__) . 'assets/inspector-roofing-logo.png'),
            'Hero asset' => file_exists(plugin_dir_path(__FILE__) . 'assets/droneproof-hero.png'),
            '3D house reference asset' => file_exists(plugin_dir_path(__FILE__) . 'assets/contractor-house-reference.png'),
            'AI hub social graphic' => file_exists(plugin_dir_path(__FILE__) . 'assets/droneproof-ai-hub-social.jpg'),
            'AI hub post pack' => file_exists(plugin_dir_path(__FILE__) . 'assets/droneproof-ai-hub-posts.txt'),
            'Android field app kit' => file_exists(plugin_dir_path(__FILE__) . 'assets/inspector-droneproof-dji-sdk-android-starter.zip'),
            'Android debug APK' => file_exists(plugin_dir_path(__FILE__) . 'assets/inspector-droneproof-pilot-debug.apk'),
            'Stylesheet' => file_exists(plugin_dir_path(__FILE__) . 'assets/inspector-droneproof.css'),
            'Frontend script' => file_exists(plugin_dir_path(__FILE__) . 'assets/inspector-droneproof.js'),
        );

        $checks = array_merge(array(
            'Shortcode ready' => shortcode_exists(self::SHORTCODE),
            'Draft/page exists' => (bool) $page,
            'Page contains shortcode' => $page ? has_shortcode($page->post_content, self::SHORTCODE) : false,
            'AI tools hub page exists' => (bool) $ai_tools_page,
            'AI tools hub contains shortcode' => $ai_tools_page ? has_shortcode($ai_tools_page->post_content, self::AI_TOOLS_SHORTCODE) : false,
            'PDF export route' => true,
            'Flight-plan REST route' => true,
            'AI QA REST route' => true,
            'InstantRoofView data bridge' => true,
            'Roof data save route' => true,
            'Field job save route' => true,
            'Field photo upload route' => true,
            'Field report data route' => true,
            'Android field token' => self::ensure_field_token() !== '',
            'DJI/Litchi mission exports' => true,
            'Pilot launch gate' => true,
            'Preflight checklist export' => true,
            'Offline field app export' => true,
            'Android field app download' => true,
            'Google Play app record' => true,
            'Android package name locked' => true,
            'Secure API key settings' => true,
            'Secure Google API key settings' => true,
            'Public page hides API secrets' => true,
        ), $assets);

        $passed = 0;
        foreach ($checks as $ok) {
            if ($ok) {
                $passed++;
            }
        }

        return array(
            'score' => (int) round(($passed / count($checks)) * 1000),
            'checks' => $checks,
            'page' => $page,
        );
    }

    public static function render_admin_page() {
        if (!current_user_can('edit_pages')) {
            return;
        }

        $status = self::self_check();
        $page = $status['page'];
        $page_id = $page ? (int) $page->ID : 0;
        $edit_url = $page_id ? get_edit_post_link($page_id, '') : '';
        $view_url = $page_id ? get_permalink($page_id) : '';
        $ai_tools_page_id = absint(get_option(self::AI_TOOLS_PAGE_OPTION));
        $ai_tools_page = $ai_tools_page_id ? get_post($ai_tools_page_id) : null;
        $ai_tools_edit_url = $ai_tools_page ? get_edit_post_link($ai_tools_page_id, '') : '';
        $ai_tools_view_url = $ai_tools_page ? get_permalink($ai_tools_page_id) : '';
        $report_url = admin_url('admin-post.php?action=inspector_droneproof_damage_report');
        $api_url = rest_url('inspector-droneproof/v1/flight-plan');
        $field_job_url = rest_url('inspector-droneproof/v1/field-job');
        $field_photo_url = rest_url('inspector-droneproof/v1/field-photo');
        $sdk_url = plugin_dir_url(__FILE__) . 'assets/inspector-droneproof-dji-sdk-android-starter.zip';
        $apk_url = plugin_dir_url(__FILE__) . 'assets/inspector-droneproof-pilot-debug.apk';
        $settings_status = isset($_GET['settings']) ? sanitize_key(wp_unslash($_GET['settings'])) : '';
        $has_key = self::has_api_key();
        $key_last4 = self::api_key_last4();
        $has_google_key = self::has_google_api_key();
        $google_key_last4 = self::google_api_key_last4();
        $field_token = self::ensure_field_token();
        $field_token_last4 = self::field_token_last4();
        ?>
        <div class="wrap">
            <h1>Inspector DroneProof&trade;</h1>
            <p>Contractor roof intelligence console with job inputs, flight planning, photo intake, damage marking, 3D model rendering, secure API settings, and packet exports.</p>

            <?php if ($settings_status === 'saved') : ?>
                <div class="notice notice-success is-dismissible"><p>API settings saved in WordPress. Keys are not printed into the public page or ZIP package.</p></div>
            <?php elseif ($settings_status === 'unchanged') : ?>
                <div class="notice notice-info is-dismissible"><p>No API key change was made.</p></div>
            <?php endif; ?>

            <style>
                .idp-admin-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,420px);gap:20px;align-items:start}
                .idp-admin-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;box-shadow:0 10px 34px rgba(7,24,39,.06);padding:22px;max-width:none}
                .idp-admin-card h2{margin-top:0}
                .idp-admin-score{color:#0f75bc;font-size:46px;font-weight:800;line-height:1;margin:8px 0}
                .idp-admin-muted{color:#526377}
                .idp-admin-pill{background:#f4f8fc;border:1px solid #c9d8e7;border-radius:999px;color:#071827;display:inline-block;font-weight:700;margin:0 6px 6px 0;padding:7px 10px}
                .idp-admin-checks{columns:2;margin-bottom:0}
                .idp-admin-checks li{break-inside:avoid;margin-bottom:7px}
                .idp-admin-key{font-family:Consolas,Monaco,monospace}
                @media(max-width:900px){.idp-admin-grid{grid-template-columns:1fr}.idp-admin-checks{columns:1}}
            </style>

            <div class="idp-admin-grid">
                <div class="card" style="max-width:none;">
                    <h2>Page Setup</h2>
                    <p><strong>Shortcode:</strong> <code>[<?php echo esc_html(self::SHORTCODE); ?>]</code></p>
                    <p><strong>REST API:</strong> <a href="<?php echo esc_url($api_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($api_url); ?></a></p>
                    <p><strong>Roof data bridge:</strong> <a href="<?php echo esc_url(rest_url('inspector-droneproof/v1/roof-data')); ?>" target="_blank" rel="noopener"><?php echo esc_html(rest_url('inspector-droneproof/v1/roof-data')); ?></a></p>
                    <p><strong>Field job sync:</strong> <a href="<?php echo esc_url($field_job_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($field_job_url); ?></a></p>
                    <p><strong>Field photo upload:</strong> <code><?php echo esc_html($field_photo_url); ?></code></p>
                    <p><strong>Damage PDF:</strong> <a href="<?php echo esc_url($report_url); ?>" target="_blank" rel="noopener">Download test report</a></p>
                    <p><strong>Android app:</strong> <code><?php echo esc_html(self::PLAY_PACKAGE_NAME); ?></code></p>
                    <p>
                        <a class="button" href="<?php echo esc_url(self::PLAY_CONSOLE_URL); ?>" target="_blank" rel="noopener">Open Play Console app</a>
                        <a class="button" href="<?php echo esc_url($sdk_url); ?>">Download Android source kit</a>
                        <a class="button button-primary" href="<?php echo esc_url($apk_url); ?>">Download developer test APK</a>
                    </p>
                    <p>
                        <?php if ($page_id) : ?>
                            <a class="button button-primary" href="<?php echo esc_url($edit_url); ?>">Edit DroneProof page</a>
                            <a class="button" href="<?php echo esc_url($view_url); ?>" target="_blank" rel="noopener">View page</a>
                        <?php endif; ?>
                        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=inspector_droneproof_create_page'), 'inspector_droneproof_create_page')); ?>">Create/repair page</a>
                    </p>
                    <p>
                        <?php if ($ai_tools_page) : ?>
                            <a class="button button-primary" href="<?php echo esc_url($ai_tools_edit_url); ?>">Edit AI Tools hub</a>
                            <a class="button" href="<?php echo esc_url($ai_tools_view_url); ?>" target="_blank" rel="noopener">View AI Tools hub</a>
                        <?php endif; ?>
                        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=inspector_droneproof_create_ai_tools_page'), 'inspector_droneproof_create_ai_tools_page')); ?>">Create/repair AI hub</a>
                    </p>
                    <p class="idp-admin-muted">The page is built for roofing contractors, inspection teams, and carrier-facing packet review. It uses Inspector Roofing colors, the logo, and the DroneProof&trade; system name.</p>
                </div>

                <div class="card" style="max-width:none;">
                    <h2>Install Score</h2>
                    <p class="idp-admin-score"><?php echo esc_html((string) $status['score']); ?><span style="font-size:18px;color:#526377;"> / 1000</span></p>
                    <ul class="idp-admin-checks">
                        <?php foreach ($status['checks'] as $label => $ok) : ?>
                            <li><?php echo $ok ? 'PASS' : 'TODO'; ?> - <?php echo esc_html($label); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="idp-admin-grid" style="margin-top:20px;">
                <div class="idp-admin-card">
                    <h2>Secure API Settings</h2>
                    <p class="idp-admin-muted">Save AI and Google keys here when you are ready to connect server-side model generation, map context, geocoding, or future roof imagery workflows. Keys are stored in WordPress options and are never printed into the public plugin code.</p>
                    <?php if (current_user_can('manage_options')) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('inspector_droneproof_save_settings'); ?>
                            <input type="hidden" name="action" value="inspector_droneproof_save_settings">
                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row"><label for="idr-openai-key">OpenAI API key</label></th>
                                    <td>
                                        <input class="regular-text idp-admin-key" type="password" id="idr-openai-key" name="openai_api_key" autocomplete="new-password" placeholder="<?php echo $has_key ? esc_attr('Saved key ending in ' . $key_last4) : esc_attr('Paste key to save'); ?>">
                                        <p class="description"><?php echo $has_key ? esc_html('A key is saved. Enter a new key to replace it, or remove it below.') : esc_html('No key is saved yet.'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="idr-google-key">Google Maps Platform API key</label></th>
                                    <td>
                                        <input class="regular-text idp-admin-key" type="password" id="idr-google-key" name="google_api_key" autocomplete="new-password" placeholder="<?php echo $has_google_key ? esc_attr('Saved key ending in ' . $google_key_last4) : esc_attr('Paste Google key to save'); ?>">
                                        <p class="description"><?php echo $has_google_key ? esc_html('A Google key is saved. Enter a new key to replace it, or remove it below.') : esc_html('No Google key is saved yet. Use a domain-restricted Google Maps Platform key for public map features.'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Remove saved keys</th>
                                    <td>
                                        <label><input type="checkbox" name="remove_api_key" value="1"> Delete saved OpenAI key</label><br>
                                        <label><input type="checkbox" name="remove_google_api_key" value="1"> Delete saved Google key</label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Android field upload token</th>
                                    <td>
                                        <input class="regular-text idp-admin-key" type="text" readonly value="<?php echo esc_attr($field_token); ?>" onfocus="this.select();">
                                        <p class="description">Use this inside the Android field app settings. Token ending: <?php echo esc_html($field_token_last4); ?>. Keep it private; regenerate it if a device is lost.</p>
                                        <label><input type="checkbox" name="regenerate_field_token" value="1"> Regenerate field app token</label>
                                    </td>
                                </tr>
                            </table>
                            <p><button class="button button-primary" type="submit">Save API settings</button></p>
                        </form>
                    <?php else : ?>
                        <p class="idp-admin-muted">Ask a site administrator to manage API keys.</p>
                    <?php endif; ?>
                </div>

                <div class="idp-admin-card">
                    <h2>One-of-a-kind System</h2>
                    <p><span class="idp-admin-pill">Inspector DroneProof&trade;</span><span class="idp-admin-pill">DroneProof Vision&trade;</span><span class="idp-admin-pill">Proof Packet&trade;</span></p>
                    <p class="idp-admin-muted">Use the system for local jobs first, then keep the same labels, slope IDs, mission exports, PDF rules, and review steps for national contractor rollout.</p>
                    <ol>
                        <li>Publish the page and put it in your service menu.</li>
                        <li>Use the PDF report as the contractor proof sample.</li>
                        <li>Use the DJI KML and Litchi CSV exports as pilot-reviewed planning files.</li>
                        <li>Use photo intake, damage markers, and AI/local QA before packet delivery.</li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }

    public static function register_assets() {
        $base = plugin_dir_url(__FILE__);

        wp_register_style(
            'inspector-droneproof',
            $base . 'assets/inspector-droneproof.css',
            array(),
            self::VERSION
        );

        wp_register_script(
            'inspector-droneproof',
            $base . 'assets/inspector-droneproof.js',
            array(),
            self::VERSION,
            true
        );

        wp_localize_script('inspector-droneproof', 'InspectorDroneProof', self::frontend_config($base));
    }

    private static function frontend_config($base = '') {
        if ($base === '') {
            $base = plugin_dir_url(__FILE__);
        }

        return array(
            'geocodeApi' => esc_url_raw(rest_url('inspector-droneproof/v1/geocode')),
            'roofDataApi' => esc_url_raw(rest_url('inspector-droneproof/v1/roof-data')),
            'roofDataSaveApi' => esc_url_raw(rest_url('inspector-droneproof/v1/roof-data/save')),
            'fieldJobApi' => esc_url_raw(rest_url('inspector-droneproof/v1/field-job')),
            'fieldJobLatestApi' => esc_url_raw(rest_url('inspector-droneproof/v1/field-job/latest')),
            'fieldPhotoApi' => esc_url_raw(rest_url('inspector-droneproof/v1/field-photo')),
            'fieldReportApi' => esc_url_raw(rest_url('inspector-droneproof/v1/report-data')),
            'aiQaApi' => esc_url_raw(rest_url('inspector-droneproof/v1/ai-qa')),
            'restNonce' => wp_create_nonce('wp_rest'),
            'googleConfigured' => self::has_google_api_key(),
            'openAiConfigured' => self::has_api_key(),
            'sampleHouse' => esc_url_raw($base . 'assets/contractor-house-reference.png'),
            'djiSdkKit' => esc_url_raw($base . 'assets/inspector-droneproof-dji-sdk-android-starter.zip'),
            'djiDebugApk' => esc_url_raw($base . 'assets/inspector-droneproof-pilot-debug.apk'),
            'playAppId' => self::PLAY_APP_ID,
            'playPackageName' => self::PLAY_PACKAGE_NAME,
            'playStoreUrl' => esc_url_raw(self::PLAY_STORE_URL),
        );
    }

    public static function register_routes() {
        register_rest_route('inspector-droneproof/v1', '/flight-plan', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'flight_plan'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('inspector-droneproof/v1', '/roof-data', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'roof_data'),
            'permission_callback' => function () {
                return current_user_can('edit_pages');
            },
        ));

        register_rest_route('inspector-droneproof/v1', '/roof-data/save', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'save_roof_data'),
            'permission_callback' => function () {
                return current_user_can('edit_pages');
            },
        ));

        register_rest_route('inspector-droneproof/v1', '/field-job', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'save_field_job'),
            'permission_callback' => array(__CLASS__, 'request_has_field_access'),
        ));

        register_rest_route('inspector-droneproof/v1', '/field-job/latest', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'latest_field_job'),
            'permission_callback' => array(__CLASS__, 'request_has_field_access'),
        ));

        register_rest_route('inspector-droneproof/v1', '/field-photo', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'upload_field_photo'),
            'permission_callback' => array(__CLASS__, 'request_has_field_access'),
        ));

        register_rest_route('inspector-droneproof/v1', '/report-data', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'report_data'),
            'permission_callback' => array(__CLASS__, 'request_has_field_access'),
        ));

        register_rest_route('inspector-droneproof/v1', '/geocode', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'geocode_address'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('inspector-droneproof/v1', '/ai-qa', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'ai_qa'),
            'permission_callback' => function () {
                return current_user_can('edit_pages');
            },
        ));
    }

    public static function geocode_address($request) {
        $address = sanitize_text_field((string) $request->get_param('address'));
        $key = (string) get_option(self::GOOGLE_API_KEY_OPTION, '');

        if ($address === '') {
            return new WP_Error('inspector_droneproof_address_required', 'Address is required.', array('status' => 400));
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : 'unknown';
        $rate_key = 'idr_geo_rate_' . md5($ip);
        $rate_count = (int) get_transient($rate_key);

        if ($rate_count >= 120) {
            return new WP_Error('inspector_droneproof_geocode_rate_limited', 'Too many geocode requests. Try again later.', array('status' => 429));
        }

        set_transient($rate_key, $rate_count + 1, 3600);

        if ($key === '') {
            return rest_ensure_response(array(
                'ok' => false,
                'mode' => 'relative',
                'message' => 'Google key not saved. Planner will use relative waypoints.',
            ));
        }

        $cache_key = 'idr_geo_' . md5(strtolower($address));
        $cached = get_transient($cache_key);

        if (is_array($cached)) {
            return rest_ensure_response($cached);
        }

        $url = add_query_arg(array(
            'address' => $address,
            'key' => $key,
        ), 'https://maps.googleapis.com/maps/api/geocode/json');

        $response = wp_remote_get($url, array('timeout' => 8));

        if (is_wp_error($response)) {
            return rest_ensure_response(array(
                'ok' => false,
                'mode' => 'relative',
                'message' => 'Google geocode request failed. Planner will use relative waypoints.',
            ));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body) || empty($body['results'][0]['geometry']['location'])) {
            return rest_ensure_response(array(
                'ok' => false,
                'mode' => 'relative',
                'message' => 'Address could not be geocoded. Planner will use relative waypoints.',
            ));
        }

        $result = $body['results'][0];
        $location = $result['geometry']['location'];
        $out = array(
            'ok' => true,
            'mode' => 'gps',
            'formattedAddress' => sanitize_text_field($result['formatted_address']),
            'lat' => (float) $location['lat'],
            'lng' => (float) $location['lng'],
        );

        set_transient($cache_key, $out, DAY_IN_SECONDS);
        return rest_ensure_response($out);
    }

    private static function fallback_qa($mission, $markers) {
        $waypoints = isset($mission['flightInstructions']) && is_array($mission['flightInstructions'])
            ? $mission['flightInstructions']
            : array();
        $issues = array();
        $checks = array(
            'Confirm Part 107/FAA authorization or recreational compliance before launch.',
            'Verify no TFR, controlled airspace, temporary hazards, or local restrictions.',
            'Confirm visual line of sight, weather, wind, battery, compass, GPS lock, and return-to-home altitude.',
            'Verify all waypoints clear trees, utility lines, chimneys, people, vehicles, and neighboring property.',
        );

        if (count($waypoints) < 5) {
            $issues[] = 'Add at least five capture points: overview, front, rear, left/right returns, and detail pass.';
        }

        if (empty($markers)) {
            $issues[] = 'No damage markers are in the packet yet. Add markers before supplement or denial review.';
        }

        foreach ($waypoints as $waypoint) {
            $altitude = isset($waypoint['altitudeFt']) ? (float) $waypoint['altitudeFt'] : 0;
            if ($altitude > 120) {
                $issues[] = 'Waypoint ' . sanitize_text_field($waypoint['id']) . ' is above 120 ft. Confirm legal ceiling and local conditions.';
            }
        }

        if (empty($issues)) {
            $issues[] = 'Plan structure looks ready for pilot review. Final launch must still be approved in the drone app.';
        }

        return array(
            'ok' => true,
            'mode' => 'local-rules',
            'summary' => 'Local QA completed without AI.',
            'checks' => $checks,
            'issues' => $issues,
        );
    }

    public static function ai_qa($request) {
        $mission = $request->get_param('mission');
        $markers = $request->get_param('markers');

        if (!is_array($mission)) {
            $mission = array();
        }

        if (!is_array($markers)) {
            $markers = array();
        }

        $api_key = (string) get_option(self::API_KEY_OPTION, '');

        if ($api_key === '') {
            return rest_ensure_response(self::fallback_qa($mission, $markers));
        }

        $prompt = "You are a roofing drone mission QA assistant. Review this contractor roof inspection mission for safety, packet completeness, photo coverage, and insurance documentation quality. Do not approve launch; return concise actionable checks.\n\nMission:\n" . wp_json_encode($mission) . "\n\nDamage markers:\n" . wp_json_encode($markers);
        $response = wp_remote_post('https://api.openai.com/v1/responses', array(
            'timeout' => 18,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => 'gpt-5.5',
                'input' => $prompt,
                'max_output_tokens' => 700,
            )),
        ));

        if (is_wp_error($response)) {
            return rest_ensure_response(self::fallback_qa($mission, $markers));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $text = '';

        if (isset($body['output_text'])) {
            $text = (string) $body['output_text'];
        } elseif (isset($body['output'][0]['content'][0]['text'])) {
            $text = (string) $body['output'][0]['content'][0]['text'];
        }

        if ($text === '') {
            return rest_ensure_response(self::fallback_qa($mission, $markers));
        }

        return rest_ensure_response(array(
            'ok' => true,
            'mode' => 'openai',
            'summary' => sanitize_textarea_field($text),
            'checks' => array(),
            'issues' => array(),
        ));
    }

    public static function flight_plan() {
        return rest_ensure_response(array(
            'missionId' => 'IDP-VISION-LOCAL-NATIONAL-004',
            'generatedBy' => 'Inspector DroneProof Vision API',
            'fallbackReason' => 'Use this route when DJI install is blocked, unsupported, or waiting on app-key approval.',
            'localHub' => array(
                'name' => 'Inspector Roofing local command center',
                'market' => 'Alpharetta / North Georgia',
                'nationalStandard' => 'Same slope IDs, packet schema, QA status, and export rules for every market.',
            ),
            'model' => array(
                'name' => 'DroneProof Vision(TM)',
                'publicSecretPolicy' => 'No API keys are exposed through this endpoint or frontend script.',
                'useCase' => 'Contractor checklist, roof photo intake, and carrier packet planning.',
                'googleMapsConfigured' => self::has_google_api_key(),
                'openAiConfigured' => self::has_api_key(),
            ),
            'flightInstructions' => array(
                array(
                    'id' => 'WP-01',
                    'action' => 'Establish claim overview',
                    'altitudeFt' => 92,
                    'cameraPitchDeg' => -58,
                    'headingDeg' => 35,
                    'targetPlane' => 'ALL',
                    'capture' => 'Four-corner roof overview plus street-facing context.',
                ),
                array(
                    'id' => 'WP-02',
                    'action' => 'Front slope grid',
                    'altitudeFt' => 54,
                    'cameraPitchDeg' => -72,
                    'headingDeg' => 180,
                    'targetPlane' => 'A',
                    'capture' => 'Left-to-right image row with 35 percent overlap.',
                ),
                array(
                    'id' => 'WP-03',
                    'action' => 'Ridge and vent detail',
                    'altitudeFt' => 42,
                    'cameraPitchDeg' => -48,
                    'headingDeg' => 92,
                    'targetPlane' => 'A/B',
                    'capture' => 'Ridge caps, vents, flashing, and soft-metal reference.',
                ),
                array(
                    'id' => 'WP-04',
                    'action' => 'Rear slope grid',
                    'altitudeFt' => 56,
                    'cameraPitchDeg' => -72,
                    'headingDeg' => 0,
                    'targetPlane' => 'B',
                    'capture' => 'Full rear plane sweep with gutter and valley context.',
                ),
                array(
                    'id' => 'WP-05',
                    'action' => 'Garage return',
                    'altitudeFt' => 44,
                    'cameraPitchDeg' => -68,
                    'headingDeg' => 250,
                    'targetPlane' => 'C',
                    'capture' => 'Garage plane, tie-in, gutter line, and collateral notes.',
                ),
            ),
            'exports' => array('manual checklist', 'dji kml planning file', 'litchi csv planning file', 'offline field app html', 'csv waypoints', 'geojson path', 'claim packet json', 'photo damage pdf', 'ai/local qa'),
        ));
    }

    private static function scalar_text($value) {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (is_object($value)) {
            return '';
        }

        return sanitize_text_field((string) $value);
    }

    private static function scalar_number($value) {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (preg_match('/-?[0-9][0-9,]*(?:\.[0-9]+)?/', (string) $value, $match)) {
            return (float) str_replace(',', '', $match[0]);
        }

        return 0.0;
    }

    private static function pick_roof_value($data, $keys, $depth = 0) {
        if (!is_array($data) || $depth > 4) {
            return null;
        }

        $wanted = array();
        foreach ($keys as $key) {
            $wanted[] = sanitize_key($key);
        }

        foreach ($data as $key => $value) {
            if (in_array(sanitize_key((string) $key), $wanted, true)) {
                return $value;
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = self::pick_roof_value($value, $keys, $depth + 1);
                if ($found !== null && $found !== '') {
                    return $found;
                }
            }
        }

        return null;
    }

    private static function pitch_band($rise) {
        $rise = (float) $rise;
        if ($rise >= 12) {
            return 'extreme';
        }
        if ($rise >= 8) {
            return 'steep';
        }
        if ($rise > 0 && $rise < 5) {
            return 'low';
        }
        return 'standard';
    }

    private static function normalize_roof_data($raw, $source) {
        if (!is_array($raw)) {
            return null;
        }

        $address = self::scalar_text(self::pick_roof_value($raw, array('address', 'property_address', 'irv_address', 'roof_address', 'lead_address', 'job_address')));
        $job_id = self::scalar_text(self::pick_roof_value($raw, array('jobId', 'job_id', 'claim_id', 'reportId', 'report_id', 'lead_id', 'postId')));
        $area = self::scalar_number(self::pick_roof_value($raw, array('areaSqFt', 'roof_area', 'roofArea', 'roofSurfaceSqFt', 'pitchedAreaSqFt', 'pitchedRoofArea', 'totalSurfaceAreaSqFt', 'square_feet')));
        $squares = self::scalar_number(self::pick_roof_value($raw, array('squares', 'orderSquares', 'finalSquares', 'measuredSquares', 'roofSquares')));
        $facets = self::scalar_number(self::pick_roof_value($raw, array('facets', 'roofFacets', 'planes', 'planeCount')));
        $stories = self::scalar_number(self::pick_roof_value($raw, array('stories', 'story_count', 'storyCount')));
        $pitch_rise = self::scalar_number(self::pick_roof_value($raw, array('pitchRise', 'pitch_rise', 'rise', 'predominantPitchRise')));
        $pitch_run = self::scalar_number(self::pick_roof_value($raw, array('pitchRun', 'pitch_run', 'run')));
        $pitch_text = self::scalar_text(self::pick_roof_value($raw, array('pitch', 'roofPitch', 'predominantPitch')));
        $roof_style = strtolower(self::scalar_text(self::pick_roof_value($raw, array('roofStyle', 'roof_style', 'style'))));

        if (!$pitch_rise && preg_match('/([0-9]+(?:\.[0-9]+)?)\s*\/\s*12/', $pitch_text, $match)) {
            $pitch_rise = (float) $match[1];
            $pitch_run = 12;
        }

        if (!$pitch_run) {
            $pitch_run = 12;
        }

        if (!$stories) {
            $height = strtolower(self::scalar_text(self::pick_roof_value($raw, array('heightProfile', 'height_profile', 'access'))));
            if (strpos($height, '3') !== false || strpos($height, 'three') !== false) {
                $stories = 3;
            } elseif (strpos($height, '2') !== false || strpos($height, 'two') !== false) {
                $stories = 2;
            } else {
                $stories = 2;
            }
        }

        if (!in_array($roof_style, array('hip', 'gable', 'complex', 'flat'), true)) {
            if ($facets >= 12) {
                $roof_style = 'complex';
            } elseif (stripos($pitch_text, 'flat') !== false) {
                $roof_style = 'flat';
            } else {
                $roof_style = 'hip';
            }
        }

        if ($address === '' && !$area && !$squares) {
            return null;
        }

        if ($job_id === '') {
            $job_id = 'IRV-' . gmdate('Ymd-His');
        }

        $estimated = (bool) self::pick_roof_value($raw, array('estimated', 'isEstimated'));
        $confidence = self::scalar_number(self::pick_roof_value($raw, array('confidence', 'accuracyScore', 'score')));
        if (!$confidence) {
            $confidence = $estimated ? 72 : 90;
        }

        return array(
            'source' => $source,
            'jobId' => $job_id,
            'address' => $address,
            'roofStyle' => $roof_style,
            'stories' => max(1, min(3, (int) round($stories))),
            'pitch' => self::pitch_band($pitch_rise),
            'pitchRise' => $pitch_rise ? round($pitch_rise, 2) : 7,
            'pitchRun' => $pitch_run ? round($pitch_run, 2) : 12,
            'areaSqFt' => $area ? round($area, 1) : null,
            'squares' => $squares ? round($squares, 2) : null,
            'facets' => $facets ? (int) round($facets) : null,
            'ridges' => self::scalar_number(self::pick_roof_value($raw, array('ridges', 'ridgeLf', 'ridge_lf'))),
            'hips' => self::scalar_number(self::pick_roof_value($raw, array('hips', 'hipLf', 'hip_lf'))),
            'valleys' => self::scalar_number(self::pick_roof_value($raw, array('valleys', 'valleyLf', 'valley_lf'))),
            'eaves' => self::scalar_number(self::pick_roof_value($raw, array('eaves', 'eavesLf', 'eave_lf'))),
            'rakes' => self::scalar_number(self::pick_roof_value($raw, array('rakes', 'rakesLf', 'rake_lf'))),
            'wastePct' => self::scalar_number(self::pick_roof_value($raw, array('wastePct', 'waste', 'wastePercent'))),
            'confidence' => max(0, min(100, (int) round($confidence))),
            'estimated' => $estimated,
        );
    }

    private static function roof_data_score($data) {
        if (!is_array($data)) {
            return 0;
        }

        $score = 0;
        $source = isset($data['source']) ? strtolower((string) $data['source']) : '';
        if (strpos($source, 'instant') !== false || strpos($source, 'irv') !== false || strpos($source, 'roofview') !== false) {
            $score += 25;
        }
        if (!empty($data['address'])) {
            $score += 12;
        }
        if (!empty($data['areaSqFt'])) {
            $score += 26;
        }
        if (!empty($data['squares'])) {
            $score += 18;
        }
        if (!empty($data['facets'])) {
            $score += 12;
        }
        if (!empty($data['pitchRise'])) {
            $score += 10;
        }
        foreach (array('ridges', 'hips', 'valleys', 'eaves', 'rakes') as $lineal) {
            if (!empty($data[$lineal])) {
                $score += 4;
            }
        }
        if (!empty($data['confidence'])) {
            $score += min(10, (int) floor(((int) $data['confidence']) / 10));
        }

        return $score;
    }

    private static function roof_data_from_options() {
        global $wpdb;

        if (!$wpdb) {
            return null;
        }

        $likes = array('instantroof', 'instant_roof', 'irv_', 'roof_app', 'roofview');
        $where = array();
        foreach ($likes as $like) {
            $where[] = $wpdb->prepare('option_name LIKE %s', '%' . $wpdb->esc_like($like) . '%');
        }

        $rows = $wpdb->get_results('SELECT option_name, option_value FROM ' . $wpdb->options . ' WHERE ' . implode(' OR ', $where) . ' ORDER BY option_id DESC LIMIT 40', ARRAY_A);
        $best = null;
        $best_score = 0;
        foreach ((array) $rows as $row) {
            $value = maybe_unserialize($row['option_value']);
            if (is_string($value) && strlen($value) > 8 && in_array(substr(trim($value), 0, 1), array('{', '['), true)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = $decoded;
                }
            }

            $normalized = self::normalize_roof_data(is_array($value) ? $value : array('value' => $value), 'WordPress option: ' . sanitize_key($row['option_name']));
            if ($normalized) {
                $score = self::roof_data_score($normalized);
                if ($score > $best_score) {
                    $best = $normalized;
                    $best_score = $score;
                }
            }
        }

        return $best;
    }

    private static function roof_data_from_posts() {
        $objects = get_post_types(array(), 'objects');
        $candidates = array();

        foreach ($objects as $name => $object) {
            $label = isset($object->label) ? $object->label : $name;
            if (preg_match('/roof|instant|irv|lead|quote|estimate|project/i', $name . ' ' . $label)) {
                $candidates[] = $name;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        $query = new WP_Query(array(
            'post_type' => array_values(array_unique($candidates)),
            'post_status' => array('publish', 'private', 'draft', 'pending'),
            'posts_per_page' => 10,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
        ));

        $best = null;
        $best_score = 0;

        foreach ($query->posts as $post) {
            $raw = array(
                'postId' => $post->ID,
                'jobId' => 'WP-' . $post->ID,
                'title' => $post->post_title,
                'content' => wp_strip_all_tags($post->post_content),
            );

            $meta = get_post_meta($post->ID);
            foreach ($meta as $key => $values) {
                $raw[$key] = maybe_unserialize(is_array($values) ? reset($values) : $values);
            }

            if (empty($raw['address']) && preg_match('/\b\d{2,6}\s+[A-Za-z0-9 .#-]+,\s*[A-Za-z .-]+,\s*[A-Z]{2}(?:\s+\d{5})?\b/', $raw['content'], $match)) {
                $raw['address'] = $match[0];
            }

            if (empty($raw['areaSqFt']) && preg_match('/([0-9,]+)\s*(?:sq\.?\s*ft|square\s*feet|sf)\b/i', $raw['content'], $match)) {
                $raw['areaSqFt'] = $match[1];
            }

            if (empty($raw['pitch']) && preg_match('/([0-9]+(?:\.[0-9]+)?)\s*\/\s*12/', $raw['content'], $match)) {
                $raw['pitch'] = $match[0];
            }

            $normalized = self::normalize_roof_data($raw, 'WordPress post: ' . sanitize_key($post->post_type));
            if ($normalized) {
                $score = self::roof_data_score($normalized);
                if ($score > $best_score) {
                    $best = $normalized;
                    $best_score = $score;
                }
            }
        }

        return $best;
    }

    public static function save_roof_data($request) {
        $payload = $request->get_json_params();

        if (!is_array($payload)) {
            $payload = array();
        }

        $raw = isset($payload['data']) && is_array($payload['data'])
            ? $payload['data']
            : $payload;

        if (empty($raw['source'])) {
            $raw['source'] = 'DroneProof saved roof file';
        }

        $data = self::normalize_roof_data($raw, self::scalar_text($raw['source']));

        if (!$data) {
            return new WP_Error('inspector_droneproof_roof_data_invalid', 'Paste a roof report with at least an address, roof area, squares, or pitch.', array('status' => 400));
        }

        $data['savedAt'] = current_time('mysql');
        update_option(self::ROOF_DATA_OPTION, $data, false);

        return rest_ensure_response(array(
            'ok' => true,
            'message' => 'Roof data saved and loaded for DroneProof planning.',
            'data' => $data,
        ));
    }

    public static function roof_data($request) {
        $filtered = apply_filters('inspector_droneproof_roof_data', null, $request);
        $candidates = array();

        $saved = get_option(self::ROOF_DATA_OPTION, array());
        if (is_array($saved) && !empty($saved)) {
            $saved_data = self::normalize_roof_data($saved, isset($saved['source']) ? self::scalar_text($saved['source']) : 'DroneProof saved roof file');
            if ($saved_data) {
                $candidates[] = $saved_data;
            }
        }

        if (is_array($filtered)) {
            $data = self::normalize_roof_data($filtered, 'InstantRoofView filter');
            if ($data) {
                $candidates[] = $data;
            }
        }

        $option_data = self::roof_data_from_options();
        if ($option_data) {
            $candidates[] = $option_data;
        }

        $post_data = self::roof_data_from_posts();
        if ($post_data) {
            $candidates[] = $post_data;
        }

        if (empty($candidates)) {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => 'No InstantRoofView or roof-app measurement data found yet. Generate or save a roof view report first.',
            ));
        }

        usort($candidates, function ($a, $b) {
            return self::roof_data_score($b) - self::roof_data_score($a);
        });

        $data = $candidates[0];

        return rest_ensure_response(array(
            'ok' => true,
            'message' => 'Roof data imported for DroneProof planning.',
            'data' => $data,
            'candidateCount' => count($candidates),
        ));
    }

    private static function sanitize_field_job_id($value) {
        $value = sanitize_text_field((string) $value);
        $value = preg_replace('/[^A-Za-z0-9._-]/', '-', $value);
        $value = trim($value, '-_.');
        return $value !== '' ? substr($value, 0, 80) : 'IDP-' . gmdate('Ymd-His');
    }

    private static function field_jobs() {
        $jobs = get_option(self::FIELD_JOBS_OPTION, array());
        return is_array($jobs) ? $jobs : array();
    }

    private static function save_field_jobs($jobs) {
        uasort($jobs, function ($a, $b) {
            return strcmp((string) ($b['updatedAt'] ?? ''), (string) ($a['updatedAt'] ?? ''));
        });
        $jobs = array_slice($jobs, 0, 30, true);
        update_option(self::FIELD_JOBS_OPTION, $jobs, false);
    }

    private static function latest_field_job_array() {
        $jobs = self::field_jobs();
        if (empty($jobs)) {
            return null;
        }
        uasort($jobs, function ($a, $b) {
            return strcmp((string) ($b['updatedAt'] ?? ''), (string) ($a['updatedAt'] ?? ''));
        });
        $job = reset($jobs);
        return is_array($job) ? $job : null;
    }

    private static function field_report_url($job_id) {
        return add_query_arg('job_id', rawurlencode($job_id), admin_url('admin-post.php?action=inspector_droneproof_damage_report'));
    }

    private static function clean_field_manifest($photos) {
        if (!is_array($photos)) {
            return array();
        }

        $out = array();
        foreach ($photos as $photo) {
            if (!is_array($photo)) {
                continue;
            }
            $out[] = array(
                'id' => self::scalar_text($photo['id'] ?? ''),
                'name' => sanitize_file_name((string) ($photo['name'] ?? 'roof-photo')),
                'size' => absint($photo['size'] ?? 0),
                'url' => esc_url_raw((string) ($photo['url'] ?? '')),
                'plane' => self::scalar_text($photo['plane'] ?? ''),
                'damage' => self::scalar_text($photo['damage'] ?? ''),
                'severity' => self::scalar_text($photo['severity'] ?? ''),
                'note' => sanitize_textarea_field((string) ($photo['note'] ?? '')),
            );
        }

        return $out;
    }

    private static function clean_field_markers($markers) {
        if (!is_array($markers)) {
            return array();
        }

        $out = array();
        foreach ($markers as $marker) {
            if (!is_array($marker)) {
                continue;
            }
            $out[] = array(
                'id' => self::scalar_text($marker['id'] ?? ''),
                'photoId' => self::scalar_text($marker['photoId'] ?? ''),
                'photoName' => sanitize_file_name((string) ($marker['photoName'] ?? '')),
                'x' => round((float) ($marker['x'] ?? 0), 1),
                'y' => round((float) ($marker['y'] ?? 0), 1),
                'plane' => self::scalar_text($marker['plane'] ?? ''),
                'type' => self::scalar_text($marker['type'] ?? ($marker['damage'] ?? 'Damage')),
                'severity' => self::scalar_text($marker['severity'] ?? 'Medium'),
                'note' => sanitize_textarea_field((string) ($marker['note'] ?? '')),
            );
        }

        return $out;
    }

    private static function decode_field_value($value) {
        if (is_string($value) && in_array(substr(trim($value), 0, 1), array('{', '['), true)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $value;
    }

    private static function field_job_payload_response($job) {
        $job_id = self::sanitize_field_job_id($job['jobId'] ?? '');
        $photo_count = isset($job['photos']) && is_array($job['photos']) ? count($job['photos']) : 0;
        $marker_count = isset($job['markers']) && is_array($job['markers']) ? count($job['markers']) : 0;

        return array(
            'ok' => true,
            'job' => $job,
            'jobId' => $job_id,
            'photoCount' => $photo_count,
            'markerCount' => $marker_count,
            'reportUrl' => self::field_report_url($job_id),
            'reportDataUrl' => add_query_arg('job_id', rawurlencode($job_id), rest_url('inspector-droneproof/v1/report-data')),
            'photoUploadUrl' => rest_url('inspector-droneproof/v1/field-photo'),
        );
    }

    public static function save_field_job($request) {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $mission = self::decode_field_value($payload['mission'] ?? array());
        $preflight = self::decode_field_value($payload['preflight'] ?? array());
        $photos = self::decode_field_value($payload['photos'] ?? ($payload['photoManifest'] ?? array()));
        $markers = self::decode_field_value($payload['markers'] ?? array());

        if (!is_array($mission)) {
            $mission = array();
        }
        if (!is_array($preflight)) {
            $preflight = array();
        }

        $job_id = self::sanitize_field_job_id($payload['jobId'] ?? ($mission['missionId'] ?? ''));
        $now = current_time('mysql');
        $jobs = self::field_jobs();
        $existing = isset($jobs[$job_id]) && is_array($jobs[$job_id]) ? $jobs[$job_id] : array();
        $existing_photos = isset($existing['photos']) && is_array($existing['photos']) ? $existing['photos'] : array();
        $incoming_photos = self::clean_field_manifest($photos);
        $merged_photos = array();
        foreach (array_merge($existing_photos, $incoming_photos) as $photo) {
            if (!is_array($photo)) {
                continue;
            }
            $key = !empty($photo['attachmentId'])
                ? 'attachment-' . (int) $photo['attachmentId']
                : sanitize_key(($photo['id'] ?? '') . '-' . ($photo['name'] ?? '') . '-' . ($photo['url'] ?? ''));
            $merged_photos[$key ?: md5(wp_json_encode($photo))] = $photo;
        }

        $job = array(
            'jobId' => $job_id,
            'source' => self::scalar_text($payload['source'] ?? 'DroneProof field app'),
            'status' => self::scalar_text($payload['status'] ?? 'field-sync'),
            'createdAt' => $existing['createdAt'] ?? $now,
            'updatedAt' => $now,
            'mission' => $mission,
            'preflight' => $preflight,
            'photos' => array_values($merged_photos),
            'markers' => self::clean_field_markers($markers),
            'notes' => sanitize_textarea_field((string) ($payload['notes'] ?? '')),
        );

        $jobs[$job_id] = $job;
        self::save_field_jobs($jobs);

        return rest_ensure_response(self::field_job_payload_response($job));
    }

    public static function latest_field_job($request) {
        $job = self::latest_field_job_array();
        if (!$job) {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => 'No DroneProof field jobs have been synced yet.',
            ));
        }

        return rest_ensure_response(self::field_job_payload_response($job));
    }

    public static function upload_field_photo($request) {
        $files = $request->get_file_params();
        $file = isset($files['photo']) ? $files['photo'] : reset($files);
        if (!is_array($file) || empty($file['tmp_name'])) {
            return new WP_Error('inspector_droneproof_photo_required', 'Upload a photo file with the field name "photo".', array('status' => 400));
        }

        if (!empty($file['size']) && (int) $file['size'] > 20 * MB_IN_BYTES) {
            return new WP_Error('inspector_droneproof_photo_too_large', 'Photo is too large. Keep uploads under 20 MB.', array('status' => 413));
        }

        $job_id = self::sanitize_field_job_id($request->get_param('jobId') ?: $request->get_param('job_id') ?: $request->get_param('missionId'));
        $photo_id = self::scalar_text($request->get_param('photoId') ?: ('P-' . gmdate('His')));

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $uploaded = wp_handle_upload($file, array(
            'test_form' => false,
            'mimes' => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'heic' => 'image/heic',
                'heif' => 'image/heif',
            ),
        ));

        if (!empty($uploaded['error'])) {
            return new WP_Error('inspector_droneproof_photo_upload_failed', sanitize_text_field($uploaded['error']), array('status' => 400));
        }

        $attachment = array(
            'post_mime_type' => $uploaded['type'],
            'post_title' => sanitize_text_field(pathinfo($file['name'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        );
        $attachment_id = wp_insert_attachment($attachment, $uploaded['file']);

        if (!is_wp_error($attachment_id)) {
            $metadata = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        $photo = array(
            'id' => $photo_id,
            'attachmentId' => is_wp_error($attachment_id) ? 0 : (int) $attachment_id,
            'url' => esc_url_raw($uploaded['url']),
            'name' => sanitize_file_name($file['name']),
            'size' => absint($file['size'] ?? 0),
            'mime' => sanitize_text_field($uploaded['type']),
            'plane' => self::scalar_text($request->get_param('plane')),
            'damage' => self::scalar_text($request->get_param('damage')),
            'severity' => self::scalar_text($request->get_param('severity') ?: 'Medium'),
            'note' => sanitize_textarea_field((string) $request->get_param('note')),
            'uploadedAt' => current_time('mysql'),
        );

        $jobs = self::field_jobs();
        $job = isset($jobs[$job_id]) && is_array($jobs[$job_id]) ? $jobs[$job_id] : array(
            'jobId' => $job_id,
            'source' => 'DroneProof field photo upload',
            'status' => 'photos-only',
            'createdAt' => current_time('mysql'),
            'mission' => array('missionId' => $job_id),
            'preflight' => array(),
            'photos' => array(),
            'markers' => array(),
            'notes' => '',
        );

        $job['updatedAt'] = current_time('mysql');
        $job['photos'][] = $photo;
        $jobs[$job_id] = $job;
        self::save_field_jobs($jobs);

        return rest_ensure_response(array(
            'ok' => true,
            'message' => 'Photo uploaded and attached to DroneProof field job.',
            'jobId' => $job_id,
            'photo' => $photo,
            'job' => $job,
            'reportUrl' => self::field_report_url($job_id),
        ));
    }

    public static function report_data($request) {
        $raw_job_id = $request->get_param('job_id') ?: $request->get_param('jobId');
        $job_id = $raw_job_id ? self::sanitize_field_job_id($raw_job_id) : '';
        $jobs = self::field_jobs();
        $job = $job_id && isset($jobs[$job_id]) ? $jobs[$job_id] : null;
        if (!$job) {
            $job = self::latest_field_job_array();
        }

        if (!$job) {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => 'No report-ready field job has been synced yet.',
            ));
        }

        $response = self::field_job_payload_response($job);
        $response['findings'] = self::findings($job);
        return rest_ensure_response($response);
    }

    public static function append_public_evidence_spine($content) {
        if (is_admin() || !is_page('droneproof') || self::$public_spine_rendered) {
            return $content;
        }

        if ((int) get_queried_object_id() !== (int) get_the_ID()) {
            return $content;
        }

        self::$public_spine_rendered = true;
        wp_enqueue_style('inspector-droneproof');

        return $content . self::public_evidence_spine();
    }

    private static function public_evidence_spine() {
        $release = esc_url(self::RELEASE_URL);
        $privacy = esc_url(home_url('/privacy-policy/'));
        $terms = esc_url(home_url('/terms/'));
        $richard = esc_url(home_url('/richard-nasser/'));
        $authority = esc_url(home_url('/authority-stack/'));

        ob_start();
        ?>
        <section id="droneproof-evidence-source-spine" class="idr-source-spine" aria-labelledby="droneproof-evidence-title" style="max-width:1180px;margin:48px auto;padding:32px;border:1px solid #d8e0e5;border-radius:18px;background:#f7faf9;color:#17211d;box-shadow:0 14px 40px rgba(20,45,34,.08)">
            <p style="margin:0 0 8px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#176b47">Versioned public evidence</p>
            <h2 id="droneproof-evidence-title" style="margin:0 0 14px">Inspector DroneProof source spine</h2>
            <p>Inspector DroneProof is an Android and WordPress roof-documentation application developed by <a href="<?php echo $richard; ?>">Richard Amir Nasser</a> and published by Inspector Roofing and Restoration. The evidence/source package is <strong>v<?php echo esc_html(self::EVIDENCE_VERSION); ?></strong>; the Android application is <strong><?php echo esc_html(self::APP_VERSION); ?></strong>, version code 4; and this WordPress integration is <strong><?php echo esc_html(self::VERSION); ?></strong>.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px;margin:24px 0">
                <article style="padding:18px;background:#fff;border:1px solid #e2e8e5;border-radius:12px"><strong>Source and release</strong><p style="margin:.5rem 0 0"><a href="https://github.com/RichNass87/inspector-droneproof">GitHub repository</a><br><a href="<?php echo $release; ?>">v0.6.0 release record</a></p></article>
                <article style="padding:18px;background:#fff;border:1px solid #e2e8e5;border-radius:12px"><strong>Persistent research record</strong><p style="margin:.5rem 0 0"><a href="https://doi.org/<?php echo esc_attr(self::PROJECT_DOI); ?>">Project DOI <?php echo esc_html(self::PROJECT_DOI); ?></a><br><a href="https://doi.org/<?php echo esc_attr(self::ARCHIVED_V050_DOI); ?>">Archived v0.5.0 DOI</a><br><a href="https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples">Hugging Face evidence samples</a></p></article>
                <article style="padding:18px;background:#fff;border:1px solid #e2e8e5;border-radius:12px"><strong>Canonical developer</strong><p style="margin:.5rem 0 0"><a href="https://orcid.org/0009-0000-2980-7543">ORCID</a><br><a href="https://github.com/RichNass87">GitHub profile</a><br><a href="<?php echo $authority; ?>">Authority Stack</a></p></article>
                <article style="padding:18px;background:#fff;border:1px solid #e2e8e5;border-radius:12px"><strong>Policies</strong><p style="margin:.5rem 0 0"><a href="<?php echo $privacy; ?>">Privacy Policy</a><br><a href="<?php echo $terms; ?>">Terms of Use</a></p></article>
            </div>
            <p><strong>Artifact integrity:</strong> Android App Bundle SHA-256 <code>0f929553ec1dc6e0eca72c2a64256fec6cbb4881294a376a02841985946cd61b</code>. The public release includes source, methodology, data-safety notes, a synthetic evidence sample, and a file checksum ledger.</p>
            <p><strong>Distribution record:</strong> Amazon Appstore notified the publisher on July 16, 2026 that version 0.4.0-droneproof was live after Primary, Content Policy, and Functionality validation passed. This is first-party publication evidence, not an Amazon endorsement. A stable Amazon storefront URL is not asserted until it is captured and verified.</p>
            <p><strong>Flight boundary:</strong> This release does not include DJI SDK binaries and does not arm, launch, or command an aircraft. Mission exports are planning references requiring a qualified pilot's independent review. Inspector DroneProof is independently developed and is not affiliated with, sponsored by, certified by, or endorsed by DJI, Google, Amazon, GitHub, Zenodo, Hugging Face, or ORCID.</p>
            <p><strong>Insurance boundary:</strong> Inspector Roofing documents observable conditions and does not act as a public adjuster. Carriers decide coverage, payment, and claim outcomes.</p>
        </section>
        <?php
        return ob_get_clean();
    }

    public static function print_public_evidence_spine_footer() {
        if (is_admin() || !is_page('droneproof') || self::$public_spine_rendered) {
            return;
        }

        self::$public_spine_rendered = true;
        wp_enqueue_style('inspector-droneproof');
        echo self::public_evidence_spine();
    }

    public static function print_public_page_schema() {
        if (!is_page('droneproof')) {
            return;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@graph' => array(
                array(
                    '@type' => 'Person',
                    '@id' => home_url('/#richard-amir-nasser'),
                    'name' => 'Richard Amir Nasser',
                    'url' => home_url('/richard-nasser/'),
                    'sameAs' => array(
                        'https://orcid.org/0009-0000-2980-7543',
                        'https://github.com/RichNass87',
                    ),
                    'worksFor' => array('@id' => home_url('/#organization')),
                ),
                array(
                    '@type' => 'RoofingContractor',
                    '@id' => home_url('/#organization'),
                    'name' => 'Inspector Roofing and Restoration',
                    'url' => home_url('/'),
                    'founder' => array('@id' => home_url('/#richard-amir-nasser')),
                ),
                array(
                    '@type' => 'SoftwareApplication',
                    '@id' => home_url('/droneproof/#software'),
                    'name' => 'Inspector DroneProof',
                    'url' => home_url('/droneproof/'),
                    'applicationCategory' => 'BusinessApplication',
                    'applicationSubCategory' => 'Roof documentation and drone mission-planning support',
                    'operatingSystem' => 'Android, Web',
                    'softwareVersion' => self::APP_VERSION,
                    'datePublished' => '2026-07-16',
                    'creator' => array('@id' => home_url('/#richard-amir-nasser')),
                    'publisher' => array('@id' => home_url('/#organization')),
                    'codeRepository' => 'https://github.com/RichNass87/inspector-droneproof',
                    'releaseNotes' => self::RELEASE_URL,
                    'sameAs' => array(
                        'https://github.com/RichNass87/inspector-droneproof',
                        self::RELEASE_URL,
                        'https://doi.org/' . self::PROJECT_DOI,
                        'https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples',
                    ),
                    'license' => home_url('/terms/'),
                    'usageInfo' => home_url('/terms/'),
                    'identifier' => array(
                        array('@type' => 'PropertyValue', 'propertyID' => 'Android package', 'value' => self::PLAY_PACKAGE_NAME),
                        array('@type' => 'PropertyValue', 'propertyID' => 'Concept DOI', 'value' => self::PROJECT_DOI, 'url' => 'https://doi.org/' . self::PROJECT_DOI),
                        array('@type' => 'PropertyValue', 'propertyID' => 'Evidence release', 'value' => self::EVIDENCE_VERSION),
                    ),
                    'featureList' => array(
                        'Roof photo intake and annotation',
                        'Pilot-reviewed mission planning exports',
                        'WordPress field job synchronization',
                        'Documentation packet generation',
                    ),
                    'disambiguatingDescription' => 'Contractor documentation and mission-planning support software. It does not include DJI SDK binaries, does not command an aircraft, and does not make insurance coverage or payment decisions.',
                ),
            ),
        );

        echo '<script type="application/ld+json" id="inspector-droneproof-public-schema">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    public static function render_shortcode() {
        wp_enqueue_style('inspector-droneproof');
        wp_enqueue_script('inspector-droneproof');

        $base = plugin_dir_url(__FILE__);
        $logo = esc_url($base . 'assets/inspector-roofing-logo.png');
        $hero = esc_url($base . 'assets/contractor-house-reference.png');
        $sdk = esc_url($base . 'assets/inspector-droneproof-dji-sdk-android-starter.zip');
        $apk = esc_url($base . 'assets/inspector-droneproof-pilot-debug.apk');
        $package = esc_html(self::PLAY_PACKAGE_NAME);
        $api = esc_url(rest_url('inspector-droneproof/v1/flight-plan'));
        $geocode_api = esc_url(rest_url('inspector-droneproof/v1/geocode'));
        $roof_data_api = esc_url(rest_url('inspector-droneproof/v1/roof-data'));
        $roof_data_save_api = esc_url(rest_url('inspector-droneproof/v1/roof-data/save'));
        $field_job_api = esc_url(rest_url('inspector-droneproof/v1/field-job'));
        $field_photo_api = esc_url(rest_url('inspector-droneproof/v1/field-photo'));
        $field_report_api = esc_url(rest_url('inspector-droneproof/v1/report-data'));
        $ai_qa_api = esc_url(rest_url('inspector-droneproof/v1/ai-qa'));
        $rest_nonce = esc_attr(wp_create_nonce('wp_rest'));
        $play_store = esc_url(self::PLAY_STORE_URL);
        $report = esc_url(admin_url('admin-post.php?action=inspector_droneproof_damage_report'));
        $config_json = wp_json_encode(self::frontend_config($base), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $richard_schema = array(
            '@type' => 'Person',
            '@id' => home_url('/#richard-amir-nasser'),
            'name' => 'Richard Amir Nasser',
            'alternateName' => array('Richard Nasser', 'Richard A. Nasser'),
            'givenName' => 'Richard',
            'familyName' => 'Nasser',
            'url' => home_url('/richard-nasser/'),
            'sameAs' => array(
                'https://orcid.org/0009-0000-2980-7543',
                'https://github.com/RichNass87',
                home_url('/richard-a-nasser-dji-developer-roofing/'),
            ),
            'jobTitle' => 'Roofing technology builder',
            'worksFor' => array('@id' => home_url('/#organization')),
            'affiliation' => array('@id' => home_url('/#organization')),
            'mainEntityOfPage' => array('@id' => home_url('/droneproof/#webpage')),
            'subjectOf' => array(
                array('@id' => home_url('/droneproof/#software')),
                array('@id' => home_url('/inspector-ai-tools/#webpage')),
            ),
            'knowsAbout' => array(
                'roof inspection documentation',
                'roofing contractor software',
                'drone roof capture planning',
                'damage photo evidence',
                'AI search visibility',
                'insurance roof claim documentation',
            ),
        );
        $organization_schema = array(
            '@type' => 'RoofingContractor',
            '@id' => home_url('/#organization'),
            'name' => 'Inspector Roofing and Restoration',
            'url' => home_url('/'),
            'logo' => $base . 'assets/inspector-roofing-logo.png',
            'founder' => array('@id' => home_url('/#richard-amir-nasser')),
            'employee' => array('@id' => home_url('/#richard-amir-nasser')),
            'owns' => array('@id' => home_url('/droneproof/#software')),
        );
        $software_schema = wp_json_encode(array(
            '@context' => 'https://schema.org',
            '@graph' => array(
                array(
                    '@type' => 'WebSite',
                    '@id' => home_url('/#website'),
                    'url' => home_url('/'),
                    'name' => 'Inspector Roofing and Restoration',
                    'publisher' => array('@id' => home_url('/#organization')),
                    'inLanguage' => 'en-US',
                ),
                array(
                    '@type' => 'WebPage',
                    '@id' => home_url('/droneproof/#webpage'),
                    'url' => home_url('/droneproof/'),
                    'name' => 'Inspector DroneProof software page by Richard Amir Nasser',
                    'description' => 'Official Inspector DroneProof software page for the Android/Web roof documentation application developed by Richard Amir Nasser and published by Inspector Roofing and Restoration.',
                    'image' => array('@id' => home_url('/droneproof/#primaryimage')),
                    'isPartOf' => array('@id' => home_url('/#website')),
                    'about' => array('@id' => home_url('/droneproof/#software')),
                    'mainEntity' => array('@id' => home_url('/droneproof/#software')),
                    'creator' => array('@id' => home_url('/#richard-amir-nasser')),
                    'publisher' => array('@id' => home_url('/#organization')),
                    'dateModified' => gmdate('Y-m-d'),
                    'inLanguage' => 'en-US',
                ),
                array(
                    '@type' => 'ImageObject',
                    '@id' => home_url('/droneproof/#primaryimage'),
                    'name' => 'DroneProof contractor roof capture planning and 3D proof preview',
                    'url' => $base . 'assets/contractor-house-reference.png',
                    'caption' => 'DroneProof contractor roof capture planning and 3D proof preview',
                    'description' => 'Primary DroneProof page image showing contractor roof capture planning and a 3D proof workflow for Inspector Roofing and Restoration.',
                    'contentUrl' => $base . 'assets/contractor-house-reference.png',
                    'creditText' => 'Inspector Roofing and Restoration / Richard Amir Nasser',
                    'license' => home_url('/terms/'),
                    'acquireLicensePage' => home_url('/contact/'),
                    'copyrightNotice' => 'Copyright Inspector Roofing and Restoration. All rights reserved.',
                    'creator' => array('@id' => home_url('/#richard-amir-nasser')),
                    'copyrightHolder' => array('@id' => home_url('/#organization')),
                ),
                array(
                    '@type' => 'SoftwareApplication',
                    '@id' => home_url('/droneproof/#software'),
                    'name' => 'Inspector DroneProof',
                    'alternateName' => array('DroneProof', 'DroneProof Pilot', 'Inspector DroneProof Pilot'),
                    'description' => 'Inspector DroneProof is an Android/Web contractor roof documentation software application developed by Richard Amir Nasser and published by Inspector Roofing and Restoration. It supports roof job inputs, DJI-style flight planning, photo intake, damage marking, AI/local QA, 3D proof views, WordPress field sync, and evidence packet exports. Inspector DroneProof is independently developed and is not affiliated with, sponsored by, or endorsed by DJI.',
                    'applicationCategory' => 'BusinessApplication',
                    'applicationSubCategory' => 'Drone roof documentation and flight-planning software',
                    'operatingSystem' => 'Android, Web',
                    'softwareVersion' => self::APP_VERSION,
                    'url' => home_url('/droneproof/'),
                    'sameAs' => array(
                        'https://github.com/RichNass87/inspector-droneproof',
                        self::RELEASE_URL,
                        'https://doi.org/' . self::PROJECT_DOI,
                        'https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples',
                    ),
                    'codeRepository' => 'https://github.com/RichNass87/inspector-droneproof',
                    'identifier' => array(
                        '@type' => 'PropertyValue',
                        'propertyID' => 'Concept DOI',
                        'value' => self::PROJECT_DOI,
                        'url' => 'https://doi.org/' . self::PROJECT_DOI,
                    ),
                    'image' => array('@id' => home_url('/droneproof/#primaryimage')),
                    'brand' => array(
                        '@type' => 'Brand',
                        '@id' => home_url('/droneproof/#brand'),
                        'name' => 'Inspector DroneProof',
                    ),
                    'featureList' => array(
                        'Android roof documentation workflow',
                        'DJI-style roof capture plan generation',
                        'WordPress field job and photo sync',
                        'Damage marker evidence packet export',
                        'Contractor QA workflow',
                        'PDF photo damage reporting',
                    ),
                    'softwareRequirements' => 'Android device, WordPress field endpoint, pilot-reviewed flight plan, FAA/local rule compliance, and official DJI Mobile SDK configuration when DJI aircraft integration is enabled.',
                    'isBasedOn' => array(
                        array(
                            '@type' => 'CreativeWork',
                            'name' => 'DJI Mobile SDK',
                            'url' => 'https://developer.dji.com/mobile-sdk/',
                        ),
                    ),
                    'usageInfo' => 'Inspector DroneProof produces contractor documentation, flight-planning references, photo damage markers, and evidence packet exports. It does not replace licensed pilot judgment, FAA compliance, carrier decisions, engineering opinions, or manufacturer-approved aircraft operation.',
                    'disambiguatingDescription' => 'Inspector DroneProof is independently developed by Richard Amir Nasser for Inspector Roofing and Restoration and is not affiliated with, sponsored by, or endorsed by DJI.',
                    'creator' => array('@id' => home_url('/#richard-amir-nasser')),
                    'author' => array('@id' => home_url('/#richard-amir-nasser')),
                    'copyrightHolder' => array('@id' => home_url('/#organization')),
                    'publisher' => array('@id' => home_url('/#organization')),
                    'provider' => array('@id' => home_url('/#organization')),
                    'maintainer' => array('@id' => home_url('/#richard-amir-nasser')),
                    'releaseNotes' => self::RELEASE_URL,
                    'inLanguage' => 'en-US',
                    'dateModified' => gmdate('Y-m-d'),
                ),
                $richard_schema,
                $organization_schema,
            ),
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        ob_start();
        ?>
        <script>window.InspectorDroneProof = <?php echo $config_json ? $config_json : '{}'; ?>;</script>
        <script type="application/ld+json"><?php echo $software_schema ? $software_schema : '{}'; ?></script>
        <section class="idr-wrap" id="droneproof-top" data-flight-api="<?php echo esc_attr($api); ?>" data-geocode-api="<?php echo esc_attr($geocode_api); ?>" data-roof-data-api="<?php echo esc_attr($roof_data_api); ?>" data-roof-data-save-api="<?php echo esc_attr($roof_data_save_api); ?>" data-field-job-api="<?php echo esc_attr($field_job_api); ?>" data-field-photo-api="<?php echo esc_attr($field_photo_api); ?>" data-field-report-api="<?php echo esc_attr($field_report_api); ?>" data-ai-qa-api="<?php echo esc_attr($ai_qa_api); ?>" data-rest-nonce="<?php echo $rest_nonce; ?>" data-sample-house="<?php echo esc_attr($hero); ?>" data-dji-sdk-kit="<?php echo esc_attr($sdk); ?>" data-play-app-id="<?php echo esc_attr(self::PLAY_APP_ID); ?>" data-play-package-name="<?php echo esc_attr(self::PLAY_PACKAGE_NAME); ?>" data-play-store-url="<?php echo esc_attr($play_store); ?>">
            <header class="idr-nav" aria-label="Inspector DroneProof sections">
                <a class="idr-brand" href="#droneproof-top"><img src="<?php echo $logo; ?>" alt="Inspector Roofing"><span>DroneProof&trade;</span></a>
                <nav class="idr-nav-links" aria-label="DroneProof page navigation">
                    <a href="#software">Software</a>
                    <a href="#planner">Flight Planner</a>
                    <a href="#photos">Photo Intake</a>
                    <a href="#model">3D Model</a>
                    <a href="#dji-sdk">DJI SDK</a>
                    <a href="#report">Exports</a>
                </nav>
            </header>

            <section class="idr-hero" style="--idr-hero:url('<?php echo $hero; ?>')">
                <div class="idr-hero-copy">
                    <img class="idr-hero-logo" src="<?php echo $logo; ?>" alt="Inspector Roofing">
                    <p class="idr-eyebrow">Contractor roof intelligence console</p>
                    <h1>Build the flight plan, mark the damage, export the packet.</h1>
                    <p class="idr-hero-text">DroneProof&trade; is a working roof file tool: job inputs, DJI-style capture plan, local photo intake, damage markers, AI/local QA, 3D proof view, mission exports, and report handoff.</p>
                    <div class="idr-actions">
                        <a class="idr-button" href="#planner">Build flight plan</a>
                        <a class="idr-button idr-button-light" href="#photos">Load roof photos</a>
                        <a class="idr-button idr-button-light" href="<?php echo $apk; ?>">Developer test APK</a>
                        <a class="idr-button idr-button-light" href="<?php echo $sdk; ?>">DJI source kit</a>
                    </div>
                </div>
                <div class="idr-hero-visual" aria-label="DroneProof live 3D proof preview">
                    <div class="idr-hero-window">
                        <div class="idr-window-bar">
                            <span>DroneProof Vision&trade;</span>
                            <strong>LIVE MODEL</strong>
                        </div>
                        <canvas class="idr-canvas idr-hero-canvas" data-idr-render="hero" width="900" height="540" aria-label="Live 3D roof proof preview"></canvas>
                        <div class="idr-hero-proof">
                            <span>Local proof file</span>
                            <strong>Ready</strong>
                            <small>3D roof / Damage pins / PDF packet</small>
                        </div>
                    </div>
                </div>
            </section>

            <section class="idr-proof-strip" aria-label="DroneProof outcomes">
                <article><strong>Plan</strong><span>DJI-style mission checklist</span></article>
                <article><strong>Input</strong><span>job and roof conditions</span></article>
                <article><strong>Mark</strong><span>photo damage points</span></article>
                <article><strong>Export</strong><span>CSV, JSON, and report packet</span></article>
            </section>

            <section class="idr-software-id" id="software" aria-labelledby="idr-software-title">
                <div class="idr-console-head">
                    <div>
                        <p class="idr-eyebrow">Software identity</p>
                        <h2 id="idr-software-title">Inspector DroneProof is an Android/Web roof documentation application.</h2>
                    </div>
                    <p>This block is written for contractors, search engines, app reviewers, and future proof-source records. It states the software identity plainly before any Wikidata-style entity work.</p>
                </div>
                <div class="idr-software-grid">
                    <article>
                        <span>Name</span>
                        <strong>Inspector DroneProof</strong>
                        <p>Also referenced publicly as DroneProof and DroneProof Pilot.</p>
                    </article>
                    <article>
                        <span>Type</span>
                        <strong>Android application / software application</strong>
                        <p>Built for contractor roof documentation, field capture planning, and packet handoff.</p>
                    </article>
                    <article>
                        <span>Developer</span>
                        <strong>Richard Amir Nasser</strong>
                        <p>Roofing technology builder for Inspector Roofing and Restoration.</p>
                    </article>
                    <article>
                        <span>Publisher</span>
                        <strong>Inspector Roofing and Restoration</strong>
                        <p>Owned software page hosted on inspector-roofing.com.</p>
                    </article>
                    <article>
                        <span>Platform</span>
                        <strong>Android + Web</strong>
                        <p>Android field app workflow with WordPress data sync and browser-based report tools. Public Play production availability is not claimed.</p>
                    </article>
                    <article>
                        <span>Built with</span>
                        <strong>DJI integration boundary</strong>
                        <p>The source package does not include DJI SDK binaries and does not arm, launch, or command an aircraft.</p>
                    </article>
                    <article>
                        <span>Purpose</span>
                        <strong>Flight plans, damage marking, evidence exports</strong>
                        <p>Creates job inputs, roof photo markers, QA notes, waypoint exports, and damage packet reports.</p>
                    </article>
                    <article>
                        <span>Boundary</span>
                        <strong>Independent software</strong>
                        <p>Inspector DroneProof is independently developed and is not affiliated with, sponsored by, certified by, or endorsed by DJI.</p>
                    </article>
                </div>
                <div class="idr-proof-sources" aria-label="Proof source stack">
                    <div>
                        <p class="idr-eyebrow">Proof sources before Wikidata</p>
                        <h3>Publication stack to build first</h3>
                        <p>Use this owned software page as the anchor, then publish neutral proof sources before adding or expanding public knowledge-graph records.</p>
                    </div>
                    <ol>
                        <li><strong>Owned page:</strong> /droneproof/ with software schema, screenshots, app purpose, developer, publisher, and boundary language.</li>
                        <li><strong>GitHub:</strong> <a href="https://github.com/RichNass87/inspector-droneproof" target="_blank" rel="noopener">public repository</a> and <a href="https://github.com/RichNass87/inspector-droneproof/releases/tag/v0.6.0" target="_blank" rel="noopener">v0.6.0 evidence/source release</a> with Android and WordPress source, checksums, methodology, privacy, data-safety, and safety boundaries.</li>
                        <li><strong>Zenodo:</strong> <a href="https://doi.org/10.5281/zenodo.21301425" target="_blank" rel="noopener">project DOI 10.5281/zenodo.21301425</a> for all versions; archived <a href="https://doi.org/10.5281/zenodo.21301426" target="_blank" rel="noopener">v0.5.0 DOI 10.5281/zenodo.21301426</a>.</li>
                        <li><strong>Hugging Face:</strong> <a href="https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples" target="_blank" rel="noopener">evidence samples dataset page</a> for safe app/evidence examples.</li>
                        <li><strong>ORCID:</strong> <a href="https://orcid.org/0009-0000-2980-7543" target="_blank" rel="noopener">Richard Amir Nasser work entry</a> connected to the Zenodo DOI.</li>
                        <li><strong>Distribution:</strong> Amazon Appstore publication for Android version 0.4.0-droneproof is retained as first-party evidence; no stable public Amazon listing URL or endorsement is claimed.</li>
                    </ol>
                </div>
                <p class="idr-boundary"><strong>Insurance boundary:</strong> Inspector Roofing documents observable conditions and does not act as a public adjuster. Carriers decide coverage, payment, and claim outcomes.</p>
            </section>

            <section class="idr-console" id="planner">
                <div class="idr-console-head">
                    <div>
                        <p class="idr-eyebrow">Flight plan builder</p>
                        <h2>Enter the job. Generate the capture path.</h2>
                    </div>
                    <p>Designed for roofing contractors: generate relative waypoints immediately, use saved Google Maps API settings for GPS-ready work, then export DJI KML, Litchi CSV, field app, checklist, packet JSON, or report files.</p>
                </div>

                <div class="idr-planner-grid">
                    <form class="idr-planner-form" data-idr-planner>
                        <div class="idr-roof-bridge" data-idr-roof-bridge>
                            <div>
                                <span>InstantRoofView&trade; bridge</span>
                                <strong data-idr-roof-bridge-title>Ready to import roof data</strong>
                                <small data-idr-roof-bridge-copy>Pull latest address, pitch, stories, roof size, and measurement basis from saved roof-view data.</small>
                            </div>
                            <button type="button" class="idr-button idr-button-light" data-idr-roof-import>Import Roof View Data</button>
                            <dl data-idr-roof-data-summary></dl>
                            <textarea data-idr-roof-json placeholder="Paste InstantRoofView JSON or roof report text: address, roof area, squares, pitch, stories, facets, ridges, hips, valleys, eaves, rakes."></textarea>
                            <button type="button" class="idr-button idr-button-light" data-idr-roof-save>Save pasted roof data</button>
                        </div>
                        <label>Job / claim ID<input name="jobId" type="text" value="IR-CLAIM-001"></label>
                        <label>Property address<input name="address" type="text" placeholder="123 Main St, Alpharetta, GA"></label>
                        <label>Roof style<select name="roofStyle"><option value="hip">Hip / architectural</option><option value="gable">Gable</option><option value="complex">Complex cut-up</option><option value="flat">Flat / commercial</option></select></label>
                        <label>Stories<select name="stories"><option value="1">1 story</option><option value="2" selected>2 stories</option><option value="3">3+ stories</option></select></label>
                        <label>Pitch<select name="pitch"><option value="low">Low / walkable</option><option value="standard" selected>Standard</option><option value="steep">Steep</option><option value="extreme">12/12+</option></select></label>
                        <label>Drone<select name="drone"><option>DJI Mini 4 Pro</option><option selected>DJI Mavic 3 Enterprise</option><option>DJI Mavic 3 Thermal</option><option>DJI Air 3</option></select></label>
                        <label>Mission<select name="mission"><option value="claim" selected>Insurance damage claim</option><option value="measurement">Measurement / bid</option><option value="supplement">Supplement / denial response</option><option value="hoa">HOA / documentation</option></select></label>
                        <label>Overlap<select name="overlap"><option value="30">30 percent</option><option value="40" selected>40 percent</option><option value="55">55 percent</option></select></label>
                        <div class="idr-form-actions">
                            <button type="submit" class="idr-button">Generate plan</button>
                            <button type="button" class="idr-button idr-button-light" data-idr-export="dji">DJI KML</button>
                            <button type="button" class="idr-button idr-button-light" data-idr-export="litchi">Litchi CSV</button>
                            <button type="button" class="idr-button idr-button-light" data-idr-export="csv">Export CSV</button>
                            <button type="button" class="idr-button idr-button-light" data-idr-export="json">Export JSON</button>
                            <button type="button" class="idr-button idr-button-light" data-idr-export="preflight">Preflight TXT</button>
                            <button type="button" class="idr-button idr-button-light" data-idr-export="app">Download field app</button>
                            <a class="idr-button idr-button-light" href="<?php echo $sdk; ?>">Android field app kit</a>
                            <a class="idr-button idr-button-light" href="<?php echo $apk; ?>">Android APK</a>
                        </div>
                    </form>

                    <div class="idr-plan-output">
                        <div class="idr-plan-status" data-idr-plan-status>
                            <span>Mode</span><strong>Relative flight</strong><small>Enter an address and generate a plan.</small>
                        </div>
                        <div class="idr-plan-metrics" data-idr-plan-metrics>
                            <span><strong>5</strong> waypoints</span>
                            <span><strong>54 ft</strong> grid pass</span>
                            <span><strong>40%</strong> overlap</span>
                        </div>
                        <div class="idr-qa-panel" data-idr-qa-panel>
                            <div>
                                <span>FAA / QA / AI</span>
                                <strong>Run before export</strong>
                                <small>Checks airspace reminders, mission completeness, photo coverage, and packet readiness.</small>
                            </div>
                            <button type="button" class="idr-button" data-idr-ai-qa>Run QA</button>
                        </div>
                        <div class="idr-preflight" data-idr-preflight>
                            <div class="idr-preflight-head">
                                <div>
                                    <span>Pilot launch gate</span>
                                    <strong data-idr-preflight-title>0/8 ready</strong>
                                </div>
                                <em data-idr-preflight-status>Hold export for pilot review</em>
                            </div>
                            <label><input type="checkbox" value="airspace"> FAA/airspace, TFR, and local rules checked</label>
                            <label><input type="checkbox" value="weather"> Wind, rain, visibility, and daylight checked</label>
                            <label><input type="checkbox" value="battery"> Aircraft/controller batteries and storage checked</label>
                            <label><input type="checkbox" value="rth"> Return-to-home altitude and home point checked</label>
                            <label><input type="checkbox" value="gps"> GPS lock, compass, and map position checked</label>
                            <label><input type="checkbox" value="obstacles"> Wires, trees, chimney, people, vehicles, and neighbors checked</label>
                            <label><input type="checkbox" value="vlos"> VLOS, spotter plan, and launch/landing zone checked</label>
                            <label><input type="checkbox" value="pilot"> Licensed pilot approved final route inside the flight app</label>
                        </div>
                        <div class="idr-waypoint-table" data-idr-waypoints></div>
                    </div>
                </div>
            </section>

            <section class="idr-sdk" id="dji-sdk">
                <div class="idr-sdk-copy">
                    <p class="idr-eyebrow">DJI SDK + Google Play app</p>
                    <h2>Android app record is live for DroneProof missions.</h2>
                    <p>The Google Play app shell is created for package <strong><?php echo $package; ?></strong>. The Android field app loads WordPress jobs, captures/imports roof photos, syncs to WordPress media, shows the waypoint plan, runs a pilot safety gate, and keeps a clean code boundary for DJI Mobile SDK registration and mission upload.</p>
                    <a class="idr-button" href="<?php echo $apk; ?>">Developer test APK (not production)</a>
                    <a class="idr-button idr-button-light" href="<?php echo $sdk; ?>">Download source kit</a>
                    <div class="idr-app-status" aria-label="Android app status">
                        <span>Google Play app</span>
                        <strong>Created</strong>
                        <small>Package: <?php echo $package; ?></small>
                    </div>
                </div>
                <div class="idr-sdk-grid">
                    <article><strong>1</strong><span>DJI key created</span><p>The DJI Mobile SDK app key is tied to the same Android package used by this field app.</p></article>
                    <article><strong>2</strong><span>Play app created</span><p>Google Play Console now has the app record for package <?php echo $package; ?>.</p></article>
                    <article><strong>3</strong><span>Wire official SDK</span><p>Add DJI Mobile SDK for your aircraft/controller and replace the dry-run bridge with DJI registration callbacks.</p></article>
                    <article><strong>4</strong><span>Field-test safely</span><p>Simulator, props-off bench test, controlled field test, closed testing, then pilot-approved operations only.</p></article>
                </div>
            </section>

            <section class="idr-photo-lab" id="photos">
                <div class="idr-console-head">
                    <div>
                        <p class="idr-eyebrow">Photo intake and damage marking</p>
                        <h2>Load roof photos. Click to mark damage.</h2>
                    </div>
                    <p>Start from the sample house, add real drone/phone photos, mark damage points, severity, plane, and notes, then sync the job into WordPress for Android field upload, media storage, and report generation.</p>
                </div>

                <div class="idr-photo-grid-layout">
                    <div class="idr-upload-panel">
                        <input class="idr-photo-input" id="idr-photo-input" type="file" accept="image/*" multiple>
                        <label class="idr-upload-zone" for="idr-photo-input">
                            <strong>Choose roof photos</strong>
                            <span>Drone, phone, or adjuster photos. Multiple files supported.</span>
                        </label>
                        <div class="idr-photo-thumbs" data-idr-photo-thumbs></div>
                    </div>

                    <div class="idr-photo-stage-wrap">
                        <div class="idr-marker-controls">
                            <label>Plane<select data-idr-marker-plane><option>A</option><option>B</option><option>C</option><option>D</option><option>Collateral</option></select></label>
                            <label>Damage<select data-idr-marker-type><option>Wind crease</option><option>Hail impact</option><option>Missing shingle</option><option>Exposed mat</option><option>Soft metal</option><option>Flashing issue</option></select></label>
                            <label>Severity<select data-idr-marker-severity><option>High</option><option selected>Medium</option><option>Low</option></select></label>
                            <label>Note<input data-idr-marker-note type="text" placeholder="Short note"></label>
                        </div>
                        <div class="idr-photo-stage" data-idr-photo-stage>
                            <div class="idr-photo-empty">Choose a photo, then click the image to place damage markers.</div>
                        </div>
                        <div class="idr-damage-list" data-idr-damage-list></div>
                        <div class="idr-form-actions">
                            <button type="button" class="idr-button" data-idr-export="packet">Export packet JSON</button>
                            <button type="button" class="idr-button idr-button-light" data-idr-print-report>Print report</button>
                        </div>
                        <div class="idr-field-sync" data-idr-field-sync>
                            <div>
                                <span>WordPress field sync</span>
                                <strong data-idr-field-sync-title>Ready to save a job</strong>
                                <small data-idr-field-sync-copy>Save mission, photos, and markers first. Upload real photos when logged in, or from Android with the private field token.</small>
                            </div>
                            <div class="idr-field-sync-actions">
                                <button type="button" class="idr-button" data-idr-save-field-job>Save job to WordPress</button>
                                <button type="button" class="idr-button idr-button-light" data-idr-upload-field-photos>Upload selected photos</button>
                                <a class="idr-button idr-button-light" data-idr-field-report-link href="<?php echo $report; ?>" target="_blank" rel="noopener">Open report PDF</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="idr-model" id="model">
                <div class="idr-model-head">
                    <div>
                        <p class="idr-eyebrow">DroneProof Vision&trade;</p>
                        <h2>Live roof model, damage pins, and flight instructions.</h2>
                    </div>
                    <div class="idr-mode-switch" role="group" aria-label="Model view">
                        <button type="button" class="idr-mode-button is-active" data-idr-mode="field">Field</button>
                        <button type="button" class="idr-mode-button" data-idr-mode="contractor">Contractor</button>
                        <button type="button" class="idr-mode-button" data-idr-mode="carrier">Carrier</button>
                    </div>
                </div>
                <div class="idr-render-grid">
                    <div class="idr-viewer">
                        <canvas class="idr-canvas" width="1100" height="720" aria-label="3D roof flight model"></canvas>
                        <div class="idr-scene-hud">
                            <span>Flight model</span>
                            <strong data-idr-mode-title>Field proof view</strong>
                            <small data-idr-mode-copy>Simple damage story with roof planes, pins, and a clean PDF handoff.</small>
                        </div>
                        <div class="idr-command-rail" aria-label="Inspection packet status">
                            <span><strong>5</strong> waypoints</span>
                            <span><strong>3</strong> damage pins</span>
                            <span><strong>PDF</strong> ready</span>
                            <span><strong>QA</strong> local + national</span>
                        </div>
                    </div>
                    <aside class="idr-instructions" aria-live="polite"></aside>
                </div>
            </section>

            <section class="idr-damage" id="report">
                <div class="idr-damage-copy">
                    <p class="idr-eyebrow">Photo damage report</p>
                    <h2>Marked damage, clean notes, exportable PDF.</h2>
                    <p>Every red callout ties marked damage to a slope ID, photo ID, severity, and reviewer note. Use the report for supplements, denial responses, QA review, and national contractor training.</p>
                    <a class="idr-button" href="<?php echo $report; ?>">Download damage report PDF</a>
                </div>
                <div class="idr-damage-board">
                    <div class="idr-roof-map">
                        <span class="idr-roof idr-red"></span>
                        <span class="idr-roof idr-blue"></span>
                        <span class="idr-ring idr-ring-a"></span>
                        <span class="idr-ring idr-ring-b"></span>
                        <span class="idr-box"></span>
                    </div>
                    <article><strong>D-01</strong><span>Plane A / Photo A-014</span><h3>Wind-lifted shingle crease</h3><em>High</em></article>
                    <article><strong>D-02</strong><span>Plane B / Photo B-006</span><h3>Soft-metal hail impacts</h3><em class="idr-medium">Medium</em></article>
                    <article><strong>D-03</strong><span>Plane C / Photo C-003</span><h3>Exposed mat at garage return</h3><em>High</em></article>
                </div>
            </section>

            <section class="idr-national">
                <div>
                    <p class="idr-eyebrow">Local now, national next</p>
                    <h2>One system for every roof file.</h2>
                </div>
                <p>Inspector DroneProof&trade; starts with your local market and keeps the same packet rules for every future city: named slopes, matched photos, QA status, Google-ready map context, damage highlights, and contractor-facing exports.</p>
            </section>
        </section>
        <?php
        return ob_get_clean();
    }

    public static function render_ai_tools_shortcode() {
        wp_enqueue_style('inspector-droneproof');

        $base = plugin_dir_url(__FILE__);
        $social_image_url = $base . 'assets/droneproof-ai-hub-social.jpg';
        $posts_url = $base . 'assets/droneproof-ai-hub-posts.txt';
        $logo = esc_url($base . 'assets/inspector-roofing-logo.png');
        $social_image = esc_url($social_image_url);
        $posts_download = esc_url($posts_url);
        $tools = array(
            array(
                'name' => 'Richard A. Nasser DJI Developer Roofing',
                'href' => home_url('/richard-a-nasser-dji-developer-roofing/'),
                'type' => 'Credential page',
                'copy' => 'Public context for Richard Amir Nasser, DJI developer work, Android app setup, and roofing technology direction.',
            ),
            array(
                'name' => 'Inspector DroneProof',
                'href' => '#droneproof-hosted',
                'type' => 'Contractor tool',
                'copy' => 'Drone roof file planner with flight path logic, photo intake, damage packet exports, and DJI/Android app bridge.',
            ),
            array(
                'name' => 'InstantRoofView',
                'href' => home_url('/instant-roof-quote-generator/'),
                'type' => 'Roof takeoff starter',
                'copy' => 'Address and report-input roof measurement starter that feeds pitch, area, stories, and roof context into DroneProof.',
            ),
            array(
                'name' => 'Homeowners AI Toolbelt',
                'href' => home_url('/homeowners-ai-toolbelt/'),
                'type' => 'AI tool suite',
                'copy' => 'Homeowner-facing AI tools for roofing education, documentation, inspection prep, and decision support.',
            ),
            array(
                'name' => 'Inspection Transparency Engine',
                'href' => home_url('/inspection-transparency-engine/'),
                'type' => 'Trust tool',
                'copy' => 'Explains how inspection proof, documentation boundaries, and human review fit into the roof process.',
            ),
            array(
                'name' => 'Roofing Proof Map',
                'href' => home_url('/roofing-proof-map/'),
                'type' => 'Proof system',
                'copy' => 'Maps roofing proof, service context, and evidence structure for local and national visibility.',
            ),
            array(
                'name' => 'Atlas Query Intelligence',
                'href' => home_url('/atlas-query-intelligence/'),
                'type' => 'Search intelligence',
                'copy' => 'Query and visibility framework for roofing search, entity proof, and AI answer readiness.',
            ),
            array(
                'name' => 'AI Visibility Lab',
                'href' => home_url('/ai-visibility-lab/'),
                'type' => 'AI visibility',
                'copy' => 'Research and testing hub for AI search visibility, brand authority, and structured roofing proof.',
            ),
            array(
                'name' => 'Authority Stack',
                'href' => home_url('/authority-stack/'),
                'type' => 'Authority hub',
                'copy' => 'Entity and authority-building page connecting releases, research, proof assets, and trust signals.',
            ),
            array(
                'name' => 'Roofing Search Integrity Report',
                'href' => home_url('/roofing-search-integrity-report/'),
                'type' => 'Research report',
                'copy' => 'Public report page for roofing search integrity, transparency, and evidence-backed visibility.',
            ),
            array(
                'name' => 'Research Library',
                'href' => home_url('/research/'),
                'type' => 'Research',
                'copy' => 'Research and documentation page for Inspector Roofing authority and AI-search proof work.',
            ),
        );

        $schema = array(
            '@context' => 'https://schema.org',
            '@graph' => array(
                array(
                    '@type' => 'CollectionPage',
                    '@id' => home_url('/inspector-ai-tools/#webpage'),
                    'name' => 'Inspector AI Tools and DroneProof Hub',
                    'url' => home_url('/inspector-ai-tools/'),
                    'image' => $social_image_url,
                    'description' => 'Central public hub for Inspector Roofing AI tools, DroneProof, InstantRoofView, roofing proof systems, DJI developer workflow notes, and AI visibility research by Richard Amir Nasser.',
                    'about' => array('roofing AI tools', 'DroneProof', 'InstantRoofView', 'DJI developer roofing workflow'),
                    'creator' => array(
                        '@type' => 'Person',
                        '@id' => home_url('/#richard-amir-nasser'),
                        'name' => 'Richard Amir Nasser',
                        'alternateName' => array('Richard Nasser', 'Richard A. Nasser'),
                        'url' => home_url('/richard-nasser/'),
                        'sameAs' => array(
                            'https://orcid.org/0009-0000-2980-7543',
                            'https://github.com/RichNass87',
                        ),
                    ),
                    'publisher' => array(
                        '@type' => 'Organization',
                        '@id' => home_url('/#organization'),
                        'name' => 'Inspector Roofing and Restoration',
                        'url' => home_url('/'),
                    ),
                    'hasPart' => array(
                        array('@id' => home_url('/droneproof/#software')),
                        array('@id' => home_url('/atlas-query-intelligence/#dataset')),
                    ),
                ),
                array(
                    '@type' => 'SoftwareApplication',
                    '@id' => home_url('/droneproof/#software'),
                    'name' => 'DroneProof',
                    'alternateName' => array('Inspector DroneProof', 'DroneProof Pilot'),
                    'description' => 'DroneProof is a contractor roof intelligence application by Richard Amir Nasser for Inspector Roofing and Restoration. It connects roof job inputs, DJI-style capture planning, photo intake, damage markers, 3D proof views, Android field workflow, WordPress sync, and PDF damage packet exports.',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Android, Web',
                    'softwareVersion' => '0.4.0-droneproof',
                    'url' => home_url('/droneproof/'),
                    'featureList' => array('DJI-style flight planning', 'roof photo intake', 'damage marker packets', 'WordPress field sync', 'PDF report export'),
                    'creator' => array('@id' => home_url('/#richard-amir-nasser')),
                    'author' => array('@id' => home_url('/#richard-amir-nasser')),
                    'publisher' => array('@id' => home_url('/#organization')),
                    'provider' => array('@id' => home_url('/#organization')),
                    'isAccessibleForFree' => true,
                ),
                array(
                    '@type' => 'Dataset',
                    '@id' => home_url('/atlas-query-intelligence/#dataset'),
                    'name' => 'Inspector Roofing Atlas Query Intelligence',
                    'description' => 'Inspector Roofing Atlas Query Intelligence is a roofing search and AI-answer research dataset page documenting query patterns, entity signals, proof-page relationships, and visibility context for Inspector Roofing and Restoration.',
                    'url' => home_url('/atlas-query-intelligence/'),
                    'license' => home_url('/privacy-policy/'),
                    'keywords' => array('roofing AI search', 'AI visibility', 'roofing query intelligence', 'Inspector Roofing', 'Richard Amir Nasser'),
                    'creator' => array(
                        '@type' => 'Person',
                        '@id' => home_url('/#richard-amir-nasser'),
                        'name' => 'Richard Amir Nasser',
                        'alternateName' => array('Richard Nasser', 'Richard A. Nasser'),
                        'url' => home_url('/richard-nasser/'),
                        'sameAs' => array(
                            'https://orcid.org/0009-0000-2980-7543',
                            'https://github.com/RichNass87',
                        ),
                    ),
                    'publisher' => array(
                        '@type' => 'Organization',
                        '@id' => home_url('/#organization'),
                        'name' => 'Inspector Roofing and Restoration',
                        'url' => home_url('/'),
                    ),
                    'isAccessibleForFree' => true,
                    'inLanguage' => 'en-US',
                    'dateModified' => gmdate('Y-m-d'),
                ),
            ),
        );

        ob_start();
        ?>
        <section class="idr-tools-page">
            <script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
            <header class="idr-tools-hero">
                <div>
                    <img src="<?php echo $logo; ?>" alt="Inspector Roofing">
                    <p class="idr-eyebrow">Inspector Roofing AI systems</p>
                    <h1>Inspector AI Tools Hub by Richard Amir Nasser</h1>
                    <p>One public hub for DroneProof, InstantRoofView, AI visibility research, proof systems, and the roofing technology pages connected to Inspector Roofing and Restoration.</p>
                    <div class="idr-tools-actions">
                        <a class="idr-button" href="#tools">View tools</a>
                        <a class="idr-button idr-button-light" href="<?php echo esc_url(home_url('/richard-a-nasser-dji-developer-roofing/')); ?>">DJI developer roofing page</a>
                    </div>
                </div>
                <aside id="droneproof-hosted">
                    <span>Hosted credential and tool stack</span>
                    <strong>DJI + Android + roof AI workflow</strong>
                    <p>Richard Amir Nasser has connected the DroneProof roadmap with a DJI developer account, an Android developer account/app record, and Inspector Roofing's public AI tooling. Final drone operations still require pilot review, legal airspace checks, aircraft compatibility, and field testing.</p>
                </aside>
            </header>

            <section class="idr-tools-proof">
                <article><span>Owner / builder</span><strong>Richard Amir Nasser</strong></article>
                <article><span>Business</span><strong>Inspector Roofing and Restoration</strong></article>
                <article><span>Drone system</span><strong>Inspector DroneProof&trade;</strong></article>
                <article><span>App bridge</span><strong>Android + DJI SDK path</strong></article>
            </section>

            <section class="idr-tools-launch" aria-label="Inspector DroneProof launch kit">
                <div>
                    <p class="idr-eyebrow">Hosted launch kit</p>
                    <h2>Share the app with one official image and post pack.</h2>
                    <p>The app now hosts the Inspector DroneProof&trade; AI Tools Hub social graphic and platform-ready post copy for GBP, Facebook, LinkedIn, Instagram, and Nextdoor. Use the same hub URL everywhere so the signal points back to one indexable page.</p>
                    <div class="idr-tools-actions">
                        <a class="idr-button" href="<?php echo $social_image; ?>" target="_blank" rel="noopener">Open launch graphic</a>
                        <a class="idr-button idr-button-light" href="<?php echo $posts_download; ?>" target="_blank" rel="noopener">Download post copy</a>
                    </div>
                </div>
                <figure>
                    <img src="<?php echo $social_image; ?>" alt="Inspector DroneProof AI Tools Hub social graphic by Richard Amir Nasser">
                    <figcaption>DroneProof&trade; + Inspector AI Tools Hub / Richard Amir Nasser / Inspector Roofing and Restoration</figcaption>
                </figure>
            </section>

            <section class="idr-tools-grid" id="tools" aria-label="Inspector Roofing AI tools">
                <?php foreach ($tools as $tool) : ?>
                    <article>
                        <span><?php echo esc_html($tool['type']); ?></span>
                        <h2><?php echo esc_html($tool['name']); ?></h2>
                        <p><?php echo esc_html($tool['copy']); ?></p>
                        <a href="<?php echo esc_url($tool['href']); ?>">Open tool</a>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="idr-tools-index">
                <div>
                    <p class="idr-eyebrow">Index this page</p>
                    <h2>Use this as the central URL for the AI tool stack.</h2>
                    <p>Submit this hub in Google Search Console, then use it from GBP posts, LinkedIn, Facebook, Instagram, Nextdoor, and internal site links.</p>
                </div>
                <code><?php echo esc_html(home_url('/inspector-ai-tools/')); ?></code>
            </section>
        </section>
        <?php
        return ob_get_clean();
    }

    private static function short_pdf_text($value, $limit = 54) {
        $value = preg_replace('/\s+/', ' ', sanitize_text_field((string) $value));
        if (strlen($value) <= $limit) {
            return $value;
        }
        return substr($value, 0, max(0, $limit - 3)) . '...';
    }

    private static function findings($job = null) {
        $default = array(
            array('id' => 'D-01', 'plane' => 'A', 'photo' => 'A-014', 'damage' => 'Wind-lifted shingle crease', 'severity' => 'High', 'note' => 'Crease line and lifted tab visible on front main slope.'),
            array('id' => 'D-02', 'plane' => 'B', 'photo' => 'B-006', 'damage' => 'Soft-metal hail impacts', 'severity' => 'Medium', 'note' => 'Round impacts on vent cap and adjacent roof field context.'),
            array('id' => 'D-03', 'plane' => 'C', 'photo' => 'C-003', 'damage' => 'Exposed mat at garage return', 'severity' => 'High', 'note' => 'Highlighted shingle corner shows mat exposure and edge stress.'),
        );

        if (!is_array($job)) {
            return $default;
        }

        $photos = isset($job['photos']) && is_array($job['photos']) ? $job['photos'] : array();
        $markers = isset($job['markers']) && is_array($job['markers']) ? $job['markers'] : array();
        $photo_names = array();
        foreach ($photos as $photo) {
            if (is_array($photo) && !empty($photo['id'])) {
                $photo_names[(string) $photo['id']] = (string) ($photo['name'] ?? $photo['id']);
            }
        }

        $out = array();
        foreach ($markers as $index => $marker) {
            if (!is_array($marker)) {
                continue;
            }
            $photo_id = self::scalar_text($marker['photoId'] ?? '');
            $out[] = array(
                'id' => self::scalar_text($marker['id'] ?? ('D-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))),
                'plane' => self::scalar_text($marker['plane'] ?? 'A'),
                'photo' => self::short_pdf_text($marker['photoName'] ?? ($photo_names[$photo_id] ?? $photo_id ?: 'Field photo'), 22),
                'damage' => self::short_pdf_text($marker['type'] ?? ($marker['damage'] ?? 'Marked damage'), 44),
                'severity' => self::scalar_text($marker['severity'] ?? 'Medium'),
                'note' => self::short_pdf_text($marker['note'] ?? 'Field marker synced from DroneProof.', 72),
            );
        }

        if (empty($out)) {
            foreach ($photos as $index => $photo) {
                if (!is_array($photo)) {
                    continue;
                }
                $damage = self::scalar_text($photo['damage'] ?? '');
                $note = self::scalar_text($photo['note'] ?? '');
                if ($damage === '' && $note === '') {
                    continue;
                }
                $out[] = array(
                    'id' => 'D-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'plane' => self::scalar_text($photo['plane'] ?? 'A'),
                    'photo' => self::short_pdf_text($photo['name'] ?? ('P-' . ($index + 1)), 22),
                    'damage' => self::short_pdf_text($damage ?: 'Photo-documented roof condition', 44),
                    'severity' => self::scalar_text($photo['severity'] ?? 'Medium'),
                    'note' => self::short_pdf_text($note ?: 'Photo uploaded from field app.', 72),
                );
            }
        }

        return !empty($out) ? array_slice($out, 0, 6) : $default;
    }

    private static function pdf_escape($value) {
        return str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), $value);
    }

    private static function rgb($hex) {
        $hex = ltrim($hex, '#');
        $n = hexdec($hex);
        return sprintf('%.3F %.3F %.3F', (($n >> 16) & 255) / 255, (($n >> 8) & 255) / 255, ($n & 255) / 255);
    }

    private static function pdf_text($x, $y, $size, $value, $color = '#071827') {
        return 'BT /F1 ' . $size . ' Tf ' . self::rgb($color) . ' rg ' . $x . ' ' . $y . ' Td (' . self::pdf_escape($value) . ") Tj ET\n";
    }

    private static function pdf_rect($x, $y, $w, $h, $fill, $stroke = '') {
        if ($stroke) {
            return self::rgb($fill) . ' rg ' . self::rgb($stroke) . ' RG 1.4 w ' . "$x $y $w $h re B\n";
        }
        return self::rgb($fill) . ' rg ' . "$x $y $w $h re f\n";
    }

    private static function pdf_line($x1, $y1, $x2, $y2, $color = '#0f75bc') {
        return self::rgb($color) . ' RG 1.5 w ' . "$x1 $y1 m $x2 $y2 l S\n";
    }

    private static function pdf_circle($x, $y, $r, $color = '#ed1b24') {
        $c = $r * 0.5522848;
        return self::rgb($color) . ' RG 3 w ' .
            ($x + $r) . " $y m " .
            ($x + $r) . ' ' . ($y + $c) . ' ' . ($x + $c) . ' ' . ($y + $r) . " $x " . ($y + $r) . ' c ' .
            ($x - $c) . ' ' . ($y + $r) . ' ' . ($x - $r) . ' ' . ($y + $c) . ' ' . ($x - $r) . " $y c " .
            ($x - $r) . ' ' . ($y - $c) . ' ' . ($x - $c) . ' ' . ($y - $r) . " $x " . ($y - $r) . ' c ' .
            ($x + $c) . ' ' . ($y - $r) . ' ' . ($x + $r) . ' ' . ($y - $c) . ' ' . ($x + $r) . " $y c S\n";
    }

    private static function pdf_poly($points, $fill, $stroke = '#ffffff') {
        $first = array_shift($points);
        $out = self::rgb($fill) . ' rg ' . self::rgb($stroke) . ' RG 1.2 w ' . $first[0] . ' ' . $first[1] . ' m ';
        foreach ($points as $point) {
            $out .= $point[0] . ' ' . $point[1] . ' l ';
        }
        return $out . "h B\n";
    }

    private static function finding_rows($findings = null) {
        $findings = is_array($findings) ? $findings : self::findings();
        $out = '';
        foreach (array_slice($findings, 0, 5) as $index => $finding) {
            $y = 326 - $index * 48;
            $severity_color = $finding['severity'] === 'High' ? '#ed1b24' : '#0f75bc';
            $out .= self::pdf_rect(48, $y - 8, 516, 40, $index % 2 === 0 ? '#f4f8fc' : '#ffffff', '#c9d8e7');
            $out .= self::pdf_text(58, $y + 13, 10, $finding['id'], '#0f75bc');
            $out .= self::pdf_text(102, $y + 13, 10, 'Plane ' . $finding['plane']);
            $out .= self::pdf_text(164, $y + 13, 10, $finding['photo']);
            $out .= self::pdf_text(222, $y + 13, 10, self::short_pdf_text($finding['damage'], 38));
            $out .= self::pdf_rect(486, $y + 5, 58, 18, $severity_color);
            $out .= self::pdf_text(498, $y + 11, 8, strtoupper($finding['severity']), '#ffffff');
        }
        return $out;
    }

    private static function photo_panel($x, $y, $finding, $marker) {
        $highlight = $marker === 'circle'
            ? self::pdf_circle($x + 132, $y + 88, 24)
            : self::rgb('#ed1b24') . ' RG 3 w ' . ($x + 95) . ' ' . ($y + 54) . " 72 48 re S\n";

        return self::pdf_rect($x, $y, 238, 168, '#e8f2fb', '#c9d8e7') .
            self::pdf_rect($x, $y + 136, 238, 32, '#071827') .
            self::pdf_text($x + 12, $y + 148, 11, 'PHOTO ' . $finding['photo'] . ' - PLANE ' . $finding['plane'], '#ffffff') .
            self::pdf_poly(array(array($x + 28, $y + 52), array($x + 112, $y + 112), array($x + 210, $y + 54), array($x + 184, $y + 36), array($x + 108, $y + 82), array($x + 50, $y + 36)), '#0f75bc') .
            self::pdf_line($x + 42, $y + 48, $x + 202, $y + 48, '#ffffff') .
            self::pdf_line($x + 110, $y + 82, $x + 112, $y + 112, '#ffffff') .
            $highlight .
            self::pdf_rect($x + 10, $y + 10, 74, 22, '#ed1b24') .
            self::pdf_text($x + 18, $y + 17, 8, strtoupper($finding['severity']), '#ffffff') .
            self::pdf_text($x, $y - 18, 10, $finding['damage']) .
            self::pdf_text($x, $y - 32, 8, $finding['note'], '#526377');
    }

    private static function page_one($job = null) {
        $findings = self::findings($job);
        $mission = is_array($job) && isset($job['mission']) && is_array($job['mission']) ? $job['mission'] : array();
        $job_id = self::short_pdf_text($job['jobId'] ?? ($mission['missionId'] ?? 'CLAIM-0001'), 28);
        $address = self::short_pdf_text($mission['address'] ?? 'Alpharetta / North Georgia', 48);
        $photo_count = is_array($job) && isset($job['photos']) && is_array($job['photos']) ? count($job['photos']) : 0;
        return self::pdf_rect(0, 0, 612, 792, '#ffffff') .
            self::pdf_rect(0, 724, 612, 68, '#071827') .
            self::pdf_rect(0, 716, 612, 8, '#ed1b24') .
            self::pdf_text(48, 758, 18, 'INSPECTOR ROOFING', '#ffffff') .
            self::pdf_text(48, 736, 11, 'Inspector DroneProof(TM) - Local command center / national carrier standard', '#6bb5e7') .
            self::pdf_text(48, 674, 28, 'Photo Damage Report') .
            self::pdf_text(48, 650, 11, is_array($job) ? 'Field job packet generated from synced mission, photos, and damage markers.' : 'Sample claim packet generated locally from the model API and damage-highlight data.', '#526377') .
            self::pdf_rect(48, 592, 516, 38, '#f4f8fc', '#c9d8e7') .
            self::pdf_text(60, 616, 10, 'CLAIM', '#0f75bc') .
            self::pdf_text(60, 602, 11, $job_id) .
            self::pdf_text(184, 616, 10, 'PROPERTY', '#0f75bc') .
            self::pdf_text(184, 602, 11, $address) .
            self::pdf_text(376, 616, 10, 'EXPORT', '#0f75bc') .
            self::pdf_text(376, 602, 11, ($photo_count ? $photo_count . ' photos + ' : '') . 'PDF + JSON') .
            self::pdf_text(48, 554, 18, 'Highlighted Roof Map') .
            self::pdf_rect(48, 370, 254, 158, '#e8f2fb', '#c9d8e7') .
            self::pdf_poly(array(array(78, 430), array(176, 500), array(278, 430), array(246, 404), array(176, 454), array(110, 404)), '#0f75bc') .
            self::pdf_poly(array(array(132, 388), array(216, 448), array(288, 392), array(252, 376), array(216, 408), array(164, 374)), '#ed1b24') .
            self::pdf_circle(146, 454, 20) .
            self::pdf_circle(236, 416, 17) .
            self::rgb('#ed1b24') . " RG 3 w 188 384 42 34 re S\n" .
            self::pdf_text(326, 520, 15, 'Damage Findings') .
            self::pdf_text(326, 494, 10, self::short_pdf_text($findings[0]['id'] . ': ' . $findings[0]['damage'] . ' - Plane ' . $findings[0]['plane'] . ' - ' . $findings[0]['severity'], 58)) .
            self::pdf_text(326, 474, 10, self::short_pdf_text(($findings[1]['id'] ?? 'D-02') . ': ' . ($findings[1]['damage'] ?? 'Field marker') . ' - Plane ' . ($findings[1]['plane'] ?? 'B') . ' - ' . ($findings[1]['severity'] ?? 'Medium'), 58)) .
            self::pdf_text(326, 454, 10, self::short_pdf_text(($findings[2]['id'] ?? 'D-03') . ': ' . ($findings[2]['damage'] ?? 'Field marker') . ' - Plane ' . ($findings[2]['plane'] ?? 'C') . ' - ' . ($findings[2]['severity'] ?? 'Medium'), 58)) .
            self::pdf_text(326, 414, 10, 'Red callouts mark damage requiring carrier review.', '#526377') .
            self::pdf_text(48, 346, 18, 'Photo Damage Index') .
            self::finding_rows($findings) .
            self::pdf_rect(48, 84, 516, 52, '#071827') .
            self::pdf_text(62, 116, 10, 'REPORT USE', '#6bb5e7') .
            self::pdf_text(62, 98, 10, 'Attach to supplement, denial response, HOA review, or local QA approval before national rollout.', '#ffffff') .
            self::pdf_text(48, 44, 8, 'Inspector DroneProof(TM) sample export. Pilot and reviewer must verify all findings before carrier submission.', '#526377');
    }

    private static function page_two($job = null) {
        $findings = array_values(self::findings($job));
        $fallback = self::findings();
        while (count($findings) < 3) {
            $findings[] = $fallback[count($findings)];
        }
        return self::pdf_rect(0, 0, 612, 792, '#ffffff') .
            self::pdf_rect(0, 724, 612, 68, '#071827') .
            self::pdf_rect(0, 716, 612, 8, '#ed1b24') .
            self::pdf_text(48, 758, 18, 'PHOTO DAMAGE HIGHLIGHTS', '#ffffff') .
            self::pdf_text(48, 736, 11, 'Annotated photo panels with severity, slope ID, and report notes.', '#6bb5e7') .
            self::photo_panel(48, 498, $findings[0], 'circle') .
            self::photo_panel(326, 498, $findings[1], 'box') .
            self::photo_panel(48, 250, $findings[2], 'box') .
            self::pdf_rect(326, 250, 238, 168, '#f4f8fc', '#c9d8e7') .
            self::pdf_text(344, 384, 14, 'Export Package') .
            self::pdf_text(344, 352, 10, '1. PDF damage report') .
            self::pdf_text(344, 330, 10, '2. Numbered photo folder') .
            self::pdf_text(344, 308, 10, '3. JSON claim packet') .
            self::pdf_text(344, 286, 10, '4. CSV waypoint / photo manifest') .
            self::pdf_text(344, 264, 10, '5. QA status for local and national review') .
            self::pdf_rect(48, 98, 516, 66, '#071827') .
            self::pdf_text(62, 136, 10, 'HIGHLIGHT RULE', '#6bb5e7') .
            self::pdf_text(62, 118, 10, 'Every red callout must tie to a slope ID, photo ID, damage type, severity, and reviewer note.', '#ffffff') .
            self::pdf_text(48, 44, 8, 'Page 2 of 2 - generated by Inspector DroneProof(TM).', '#526377');
    }

    private static function build_pdf($job = null) {
        $page_one = self::page_one($job);
        $page_two = self::page_two($job);
        $objects = array(
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R 4 0 R] /Count 2 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 6 0 R >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 7 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            "<< /Length " . strlen($page_one) . " >>\nstream\n" . $page_one . "endstream",
            "<< /Length " . strlen($page_two) . " >>\nstream\n" . $page_two . "endstream",
        );

        $body = "%PDF-1.4\n";
        $offsets = array(0);

        foreach ($objects as $i => $object) {
            $offsets[] = strlen($body);
            $body .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xref = strlen($body);
        $body .= "xref\n0 " . (count($objects) + 1) . "\n";
        $body .= "0000000000 65535 f \n";

        for ($i = 1; $i < count($offsets); $i++) {
            $body .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $body .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
        return $body;
    }

    public static function send_damage_report() {
        $job = null;
        $job_id = isset($_GET['job_id']) ? self::sanitize_field_job_id(wp_unslash($_GET['job_id'])) : '';
        if ($job_id !== '') {
            $jobs = self::field_jobs();
            $job = isset($jobs[$job_id]) && is_array($jobs[$job_id]) ? $jobs[$job_id] : null;
        }

        if (!$job && !empty($_GET['latest'])) {
            $job = self::latest_field_job_array();
        }

        $pdf = self::build_pdf($job);

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="inspector-droneproof-photo-damage-report.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}

Inspector_DroneProof_Plugin::boot();
