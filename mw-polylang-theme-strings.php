<?php

    /*
    Plugin Name: Polylang Theme Strings
    Plugin URI: http://modeewine.com/en-polylang-theme-strings
    Description: Automatic scanning of strings translation in the theme and registration of them in Polylang plugin.
    Version: 1.0
    Author: Modeewine
    Author URI: http://modeewine.com
    License: GPL2
    */

    new MW_Polylang_Theme_Strings();

    class MW_Polylang_Theme_Strings
    {
        private $prefix = 'mw_polylang_strings_';
        private $pll_f = 'pll_register_string';
        private $paths;
        private $db;

        function __construct()
        {
            $this->Init();
        }

        public function Install()
        {
            if (!version_compare(phpversion(), '5', '>='))
            {
                echo 'Your PHP version (' . phpversion() . ') is incompatible with the plug-in code.';
                echo '<br />';
                echo 'The minimum supported PHP version is 5.0.';
                exit;
            }
        }

        public function Uninstall()
        {
            $this->db->query("DELETE FROM `{$this->db->prefix}options` WHERE `option_name` LIKE '{$this->prefix}%'");
        }

        private function Init()
        {
            global $wpdb;
            $this->db = &$wpdb;

            $this->Paths_Init();
            $this->Plugin_Hooks_Init();
            $this->PLL_Strings_Scan();
        }

        private function Paths_Init()
        {
            $theme = realpath(get_template_directory());
            $theme_dir_name = preg_split("/[\/\\\]/uis", $theme);
            $theme_dir_name = (string)$theme_dir_name[count($theme_dir_name) - 1];

            $this->paths = Array(
                'plugin_file_index' => __FILE__,
                'theme'             => $theme,
                'theme_dir_name'    => $theme_dir_name,
                'theme_name'        => wp_get_theme()->Name
            );
        }

        private function Plugin_Hooks_Init()
        {
            register_activation_hook($this->Path_Get('plugin_file_index'), array('MW_Polylang_Theme_Strings', 'Install'));
            register_uninstall_hook($this->Path_Get('plugin_file_index'), array('MW_Polylang_Theme_Strings', 'Uninstall'));
            add_action('init', array($this, 'PLL_Strings_Init'));
        }

        public function Path_Get($key)
        {
            if (isset($this->paths[$key]))
            {
                return $this->paths[$key];
            }
        }

        private function PLL_Strings_Scan()
        {
            if
            (
                is_admin() &&
                function_exists($this->pll_f) &&
                $_REQUEST['page'] == 'mlang' &&
                $_REQUEST['tab'] == 'strings'
            )
            {
                $data = array();
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->Path_Get('theme')));

                foreach($files as $k => $v)
                {
                    if (preg_match("/\/.*?\.(php|inc)$/uis", $k))
                    {
                        preg_match_all("/pll_[_e][\s]*\([\s]*[\'\"](.*?)[\'\"][\s]*\)/uis", file_get_contents($k), $m);

                        if (count($m[0]))
                        {
                            foreach ($m[1] as $mv)
                            {
                                if (!in_array($mv, $data))
                                {
                                    $data[] = $mv;
                                }
                            }
                        }
                    }
                }

                update_option($this->prefix . $this->Path_Get('theme_dir_name') . '_data', $data);
            }
        }

        public function PLL_Strings_Init()
        {
            if (function_exists($this->pll_f))
            {
                $data = get_option($this->prefix . $this->Path_Get('theme_dir_name') . '_data');

                if (is_array($data) && count($data))
                {
                    foreach ($data as $v)
                    {
                        pll_register_string($v, $v, __('Theme') . ' (' . $this->Path_Get('theme_name') . ')');
                    }
                }
            }
        }
    }
