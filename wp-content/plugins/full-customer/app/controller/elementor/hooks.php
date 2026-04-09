<?php

namespace Full\Customer\Elementor;

defined('ABSPATH') || exit;

add_filter('full-customer/active-widgets-menu', 'Full\Customer\Elementor\Actions\addMenuPages');
add_action('admin_enqueue_scripts', '\Full\Customer\Elementor\Actions\adminEnqueueScripts');

add_action('elementor/editor/before_enqueue_styles', 'Full\Customer\Elementor\Actions\editorBeforeEnqueueStyles');
add_action('elementor/preview/enqueue_styles', 'Full\Customer\Elementor\Actions\editorBeforeEnqueueStyles');
add_action('elementor/editor/after_enqueue_scripts', 'Full\Customer\Elementor\Actions\editorAfterEnqueueScripts');
add_action('elementor/editor/footer', 'Full\Customer\Elementor\Actions\editorFooter');

add_filter('manage_elementor_library_posts_columns', 'Full\Customer\Elementor\Filters\manageElementorLibraryPostsColumns');
add_action('manage_elementor_library_posts_custom_column', 'Full\Customer\Elementor\Actions\manageElementorLibraryPostsCustomColumn', 10, 2);
