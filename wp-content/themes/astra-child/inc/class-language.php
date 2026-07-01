<?php

class Language {
    private static $instance = null;
    private $current_lang = 'en';
    private $available_languages = ['en', 'vi'];
    private $translations = [];
    private $language_names = [
        'en' => 'English',
        'vi' => 'Tiếng Việt'
    ];

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->detect_language();
        $this->load_translations();
        $this->setup_hooks();
    }

    private function detect_language() {
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->available_languages, true)) {
            $this->current_lang = $_GET['lang'];
            $_SESSION['lang'] = $this->current_lang;
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['lang'])) {
            $this->current_lang = $_SESSION['lang'];
            return;
        }

        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (in_array($browser_lang, $this->available_languages, true)) {
                $this->current_lang = $browser_lang;
                return;
            }
        }

        $wp_lang = get_locale();
        $short_lang = substr($wp_lang, 0, 2);
        if (in_array($short_lang, $this->available_languages, true)) {
            $this->current_lang = $short_lang;
            return;
        }

        $this->current_lang = 'en';
    }

    private function load_translations() {
        $file_path = get_stylesheet_directory() . '/languages/' . $this->current_lang . '.php';
        if (file_exists($file_path)) {
            $this->translations = include $file_path;
        } else {
            $fallback_path = get_stylesheet_directory() . '/languages/en.php';
            if (file_exists($fallback_path)) {
                $this->translations = include $fallback_path;
            }
        }
    }

    private function setup_hooks() {
        add_action('admin_bar_menu', [$this, 'add_language_switcher_to_admin_bar'], 100);
        add_filter('body_class', [$this, 'add_language_body_class']);
    }

    public function get($key, $default = '') {
        return isset($this->translations[$key]) ? $this->translations[$key] : $default;
    }

    public function e($key, $default = '') {
        echo esc_html($this->get($key, $default));
    }

    public function get_current_language() {
        return $this->current_lang;
    }

    public function get_available_languages() {
        return $this->available_languages;
    }

    public function get_language_name($code) {
        return isset($this->language_names[$code]) ? $this->language_names[$code] : $code;
    }

    public function get_switch_url($lang) {
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $url = remove_query_arg('lang', $current_url);
        return add_query_arg('lang', $lang, $url);
    }

    public function add_language_switcher_to_admin_bar($wp_admin_bar) {
        if (!is_admin_bar_showing()) {
            return;
        }

        $wp_admin_bar->add_node([
            'id' => 'language-switcher',
            'title' => '🌐 ' . $this->language_names[$this->current_lang],
            'href' => '#',
        ]);

        foreach ($this->available_languages as $lang) {
            if ($lang === $this->current_lang) {
                continue;
            }
            $wp_admin_bar->add_node([
                'id' => 'lang-' . $lang,
                'parent' => 'language-switcher',
                'title' => $this->language_names[$lang],
                'href' => $this->get_switch_url($lang),
            ]);
        }
    }

    public function add_language_body_class($classes) {
        $classes[] = 'lang-' . $this->current_lang;
        return $classes;
    }
}