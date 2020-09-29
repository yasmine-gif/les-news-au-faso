<?php
/* Plugin Name: Theme and plugin translation for Polylang (TTfP)
Description: Polylang - theme and plugin translation for WordPress
Version: 3.2.4
Author: Marcin Kazmierski
License: GPL2
*/

defined('ABSPATH') or die('No script kiddies please!');

include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'polylang-tt-access.php';
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Polylang_TT_importer.php';
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Polylang_TT_exporter.php';
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Polylang_TT_theme.php';


/**
 * Class Polylang_Theme_Translation.
 */
class Polylang_Theme_Translation
{
    const SETTINGS_OPTION = 'custom_pll_settings';

    protected $plugin_path;

    protected $files_extensions = array(
        'php',
        'inc',
        'twig',
    );

    const EXCLUDE_PLUGINS = array(
        'polylang',
        'polylang-theme-translation'
    );

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->plugin_path = __DIR__;
    }

    /**
     * Run plugin.
     */
    public function run()
    {
        $this->run_theme_scanner();
        $this->run_plugin_scanner();
    }


    /**
     * Find strings in themes.
     * @return array
     */
    public function run_theme_scanner()
    {
        $data = [];
        $themes = wp_get_themes();

        $settings = get_option(Polylang_Theme_Translation::SETTINGS_OPTION, []);

        foreach ($themes as $name => $theme) {
            if (!isset($settings['themes']) || in_array($name, $settings['themes'])) {
                $theme_path = $theme->theme_root . DIRECTORY_SEPARATOR . $name;
                $files = $this->get_files_from_dir($theme_path);
                $strings = $this->file_scanner($files);
                $this->add_to_polylang_register($strings, $name);
                $data[$name] = $strings;
            }
        }

        return $data;
    }

    /**
     * Find strings in plugins.
     * @return array
     */
    public function run_plugin_scanner()
    {
        $plugins = wp_get_active_and_valid_plugins();
        $data = [];

        $settings = get_option(Polylang_Theme_Translation::SETTINGS_OPTION, []);

        foreach ($plugins as $plugin) {
            $pluginDir = dirname($plugin);
            $pluginName = pathinfo($plugin, PATHINFO_FILENAME);
            if (!isset($settings['plugins']) || in_array($pluginName, $settings['plugins'])) {
                if (!in_array($pluginName, self::EXCLUDE_PLUGINS) && $pluginDir !== WP_PLUGIN_DIR) {
                    $files = $this->get_files_from_dir($pluginDir);
                    $strings = $this->file_scanner($files);
                    $this->add_to_polylang_register($strings, $pluginName);
                    $data[$pluginName] = $strings;
                }
            }
        }

        return $data;
    }

    /**
     * Get files from dictionary recursive.
     */
    protected function get_files_from_dir($dir_name)
    {
        $results = [];
        $files = scandir($dir_name);
        foreach ($files as $key => $value) {
            $path = realpath($dir_name . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $path_parts = pathinfo($path);
                if (!empty($path_parts['extension']) && in_array($path_parts['extension'], $this->files_extensions)) {
                    $results[] = $path;
                }
            } else if ($value != "." && $value != "..") {
                $temp = $this->get_files_from_dir($path);
                $results = array_merge($results, $temp);
            }
        }
        return $results;
    }

    /**
     *  Get strings from polylang methods.
     */
    protected function file_scanner($files)
    {
        $strings = [];
        foreach ($files as $file) {
            // find polylang functions
            preg_match_all("/[\s=\(]+pll_[_e][\s]*\([\s]*[\'\"](.*?)[\'\"][\s]*\)/s", file_get_contents($file), $matches);
            if (!empty($matches[1])) {
                $strings = array_merge($strings, $matches[1]);
            }

            // find wp functions: __(), _e()
            preg_match_all("/[\s=\(\.]+_[_e][\s]*\([\s]*[\'\"](.*?)[\'\"][\s]*,[\s]*[\'\"](.*?)[\'\"][\s]*\)/s", file_get_contents($file), $matches);
            if (!empty($matches[1])) {
                $strings = array_merge($strings, $matches[1]);
            }

            // find wp functions: esc_html_e
            preg_match_all("/[\s=\(\.]+esc_html_[_e][\s]*\([\s]*[\'\"](.*?)[\'\"][\s]*,[\s]*[\'\"](.*?)[\'\"][\s]*\)/s", file_get_contents($file), $matches);
            if (!empty($matches[1])) {
                $strings = array_merge($strings, $matches[1]);

            }

            // find wp functions: _n: single + plural
            preg_match_all("/[\s=\(\.]+_n[\s]*\([\s]*[\'\"](.*?)[\'\"][\s]*,[\s]*[\'\"](.*?)[\'\"][\s]*,(.*?)\)/s", file_get_contents($file), $matches);
            if (!empty($matches[1])) {
                $strings = array_merge($strings, $matches[1]);
                $strings = array_merge($strings, $matches[2]);
            }
        }
        return $strings;
    }

    /**
     * Add strings to polylang register.
     */
    protected function add_to_polylang_register($strings, $context)
    {
        if (!empty($strings)) {
            foreach ($strings as $string) {
                pll_register_string($string, $string, __('TTfP:', 'polylang-tt') . ' ' . $context);
            }
        }
    }
}

/**
 * Init Polylang Theme Translation plugin.
 */
add_action('init', 'process_polylang_theme_translation');

function process_polylang_theme_translation()
{
    if (Polylang_TT_access::get_instance()->is_polylang_page()) {
        if (Polylang_TT_access::get_instance()->chceck_plugin_access() && current_user_can('manage_options')) {
            $plugin_obj = new Polylang_Theme_Translation();
            $plugin_obj->run();
        }
    }


}

add_action('wp_loaded', 'process_polylang_theme_translation_wp_loaded');
function process_polylang_theme_translation_wp_loaded()
{
    if (isset($_POST) && isset($_POST['export_strings']) && (int)$_POST['export_strings'] === 1 && current_user_can('manage_options')) {
        $translation = new Polylang_Theme_Translation();
        $exporter = new Polylang_TT_exporter($translation);
        $exporter->export();
    }

    if (isset($_POST["action_import_strings"])) {
        if (PLL() instanceof PLL_Settings) {
            $fileName = $_FILES["import_strings"]["tmp_name"];
            if ($_FILES["import_strings"]["size"] > 0 && $fileName) {
                $importer = new Polylang_TT_importer();
                $counter = $importer->import($fileName);

                wp_redirect((add_query_arg(['_msg' => 'translations-imported', 'items' => $counter], wp_get_referer())));
                exit;
            }
        }
        wp_redirect((add_query_arg('_msg', 'translations-import-error', wp_get_referer())));
        exit;
    }


    if (isset($_POST['action_settings'])) {
        $settings = [
            'themes' => [],
            'plugins' => [],
        ];
        $t = isset($_POST['themes']) ? $_POST['themes'] : [];
        foreach ($t as $item) {
            if (in_array($item, pll_get_themes())) {
                $settings['themes'][] = $item;
            }
        }

        $t = isset($_POST['plugins']) ? $_POST['plugins'] : [];
        foreach ($t as $item) {
            if (in_array($item, pll_get_plugins())) {
                $settings['plugins'][] = $item;
            }
        }

        update_option(Polylang_Theme_Translation::SETTINGS_OPTION, $settings);

        wp_redirect((add_query_arg(['_msg' => 'settings-saved'], wp_get_referer())));
        exit;
    }

}

add_filter('pll_settings_tabs', 'import_export_strings', 10, 1);
function import_export_strings(array $tabs)
{
    $tabs['import_export_strings'] = __("Export/import translations", 'polylang-tt');
    return $tabs;
}

/**
 * Get list of plugins
 * @return array
 */
function pll_get_plugins()
{
    $pluginsNames = [];
    $plugins = wp_get_active_and_valid_plugins();

    foreach ($plugins as $plugin) {
        $pluginDir = dirname($plugin);
        $pluginName = pathinfo($plugin, PATHINFO_FILENAME);
        if (!in_array($pluginName, Polylang_Theme_Translation::EXCLUDE_PLUGINS) && $pluginDir !== WP_PLUGIN_DIR) {
            $pluginsNames[] = $pluginName;
        }
    }

    return $pluginsNames;
}

/**
 * Get list of themes
 * @return array
 */
function pll_get_themes()
{
    $themesNames = [];
    $themes = wp_get_themes();

    foreach ($themes as $name => $theme) {
        $themesNames[] = $name;
    }

    return $themesNames;
}


add_action('pll_settings_active_tab_import_export_strings', 'custom_pll_settings_active_tab_import_export_strings', 10, 0);
function custom_pll_settings_active_tab_import_export_strings()
{
    if (get_option(Polylang_Theme_Translation::SETTINGS_OPTION) === false) {
        $settings = [
            'themes' => pll_get_themes(),
            'plugins' => pll_get_plugins(),
        ];
        add_option(Polylang_Theme_Translation::SETTINGS_OPTION, $settings);
    } else {
        $settings = get_option(Polylang_Theme_Translation::SETTINGS_OPTION);
    }

    $data = [
        'settings' => $settings,
        'themes' => pll_get_themes(),
        'plugins' => pll_get_plugins(),
        'items' => (int)(isset($_GET['items']) ? $_GET['items'] : 0),
        'msg' => filter_var(isset($_GET['_msg']) ? $_GET['_msg'] : '', FILTER_SANITIZE_STRING),
    ];
    print Polylang_TT_theme::includeTemplates('admin-import-export-page', $data);
}


add_action('plugins_loaded', 'plugins_loaded_tt_for_polylang');
function plugins_loaded_tt_for_polylang()
{
    load_plugin_textdomain('polylang-tt', false, basename(__DIR__) . '/languages');
}

add_filter('gettext', 'tt_pll_gettext_filter', 1, 2);
function tt_pll_gettext_filter($original, $text)
{
    $translations = get_translations_for_domain('pll_string');

    $tt = $translations->translate($text);

    if (empty($tt) || $tt === $text) {
        $translation = $translations->translate($original);
    } else {
        $translation = $translations->translate($text);
    }

    if (empty($translation)) {
        return $original;
    }

    return $translation;
}

//fiter ngettext
add_filter('ngettext', 'tt_pll_ngettext_filter', 1, 4);
function tt_pll_ngettext_filter($translation, $single, $plural, $number)
{
    $translations = get_translations_for_domain('pll_string');
    $translation = $translations->translate_plural($translations->translate($single), $translations->translate($plural), $number);
    return $translation;
}