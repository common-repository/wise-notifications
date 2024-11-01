<?php
// adds a new settings page this plugin
// option added - wise_notifications_name
// options keys: topicId, apiKey, subscribeLink
// inspired by https://wordpress.stackexchange.com/questions/262759/add-your-own-settings-page-for-plugin
class WiseNotificationsSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
    */
    private $options;

    /**
        * Start up
        */
    public function __construct()
    {
        add_action('admin_menu', [ $this, 'add_plugin_page' ]);
        add_action('admin_init', [ $this, 'page_init' ]);
    }

    /**
        * Add options page
        */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Wise Notifications Setup',
            'Wise Notifications',
            'manage_options',
            'wise-notifications-admin',
            [ $this, 'create_admin_page' ]
        );
    }

    /**
        * Options page callback
        */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option('wise_notifications_name');
        ?>
        <div class="wrap">
            <h1>Now Push Notify</h1>
            <h4>If you need help filling these, review the WordPress setup instructions from your <a href="https://notify.nowpush.app/?utm_source=wpsettings" target="_blank">Now Push Notify account</a>.</h4>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields('wise_notifications_group');
                do_settings_sections('wise-notifications-admin');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'wise_notifications_group', // Option group
            'wise_notifications_name', // Option name
            [ $this, 'sanitize' ] // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Setup', // Title
            [ $this, 'print_section_info' ], // Callback
            'wise-notifications-admin' // Page
        );

        add_settings_field(
            'topicId', // ID
            'Site ID', // Title
            [ $this, 'topicId_callback' ], // Callback
            'wise-notifications-admin', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            'apiKey',
            'API Key',
            [ $this, 'apiKey_callback' ],
            'wise-notifications-admin',
            'setting_section_id'
        );

        add_settings_field(
            'subscribeLink',
            'Subscribe URL',
            [ $this, 'subscribeLink_callback' ],
            'wise-notifications-admin',
            'setting_section_id'
        );

        $post_types = get_post_types_by_support(['title', 'thumbnail']);

        add_settings_field(
            'postTypes',
            'Post Types',
            [ $this, 'wisePostTypes_callback' ],
            'wise-notifications-admin',
            'setting_section_id',
            [
                'label_for' => 'postTypes',
                'post_types' => $post_types,
            ]
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        $new_input = [];

        if (isset($input['topicId'])) {
            $new_input['topicId'] = sanitize_text_field($input['topicId']);
        }

        if (isset($input['apiKey'])) {
            $new_input['apiKey'] = sanitize_text_field($input['apiKey']);
        }

        if (isset($input['subscribeLink'])) {
            $new_input['subscribeLink'] = sanitize_text_field($input['subscribeLink']);
        }

        if (isset($input['postTypes'])) {
            $new_input['postTypes'] = $input['postTypes'];
        }

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your site details:';
    }

    public function topicId_callback()
    {
        printf(
            '<input type="text" id="topicId" name="wise_notifications_name[topicId]" value="%s" class="regular-text" />',
            isset($this->options['topicId']) ? esc_attr($this->options['topicId']) : ''
        );
    }

    public function apiKey_callback()
    {
        printf(
            '<input type="text" id="apiKey" name="wise_notifications_name[apiKey]" value="%s" class="regular-text" />',
            isset($this->options['apiKey']) ? esc_attr($this->options['apiKey']) : ''
        );
    }

    public function subscribeLink_callback()
    {
        printf(
            '<input type="text" id="subscribeLink" name="wise_notifications_name[subscribeLink]" value="%s" class="regular-text" />',
            isset($this->options['subscribeLink']) ? esc_attr($this->options['subscribeLink']) : ''
        );
    }
    
    public function wisePostTypes_callback($args) {
        echo '<fieldset>';
        echo '<legend>Select the post types you want to automatically send notifications when published.<br>You can override these settings on each post before publishing.</legend>';
        
        foreach ($args['post_types'] as $post_type) {
            echo "<label for='".$post_type."'><input type='checkbox' id='postTypes[]' value='".$post_type."' name='wise_notifications_name[postTypes][]'";
            if ( isset($this->options['postTypes']) && in_array($post_type, $this->options['postTypes']) ) {
                echo ' checked';
            }
            echo '/> '.ucfirst($post_type).'</label><br />';
        }
        echo '</fieldset>';
    }
}

$wiseNotificationsSettingsPage = new WiseNotificationsSettingsPage();