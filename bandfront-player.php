function bfp_enqueue_sidebar_profile_widget_styles() {
    wp_enqueue_style(
        'bfp-sidebar-profile-widget',
        plugins_url('assets/css/sidebar-profile-widget.css', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'assets/css/sidebar-profile-widget.css')
    );
}
add_action('wp_enqueue_scripts', 'bfp_enqueue_sidebar_profile_widget_styles'); 