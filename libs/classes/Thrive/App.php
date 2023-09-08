<?php
namespace BSWP\Thrive;

/**
 * Class App
 * @package BSWP\Thrive
 */
class App extends Autoresponder {
    const API_KEY = 'birdsend';

    /**
     * @return string
     */
    public function get_title() {
        return 'BirdSend';
    }

    public function get_key() {
        return static::API_KEY;
    }

    /**
     * Create new App instance
     *
     * @return void
     */
    public function __construct()
    {
        Hooks::init();
    }

    /**
     * @return boolean
     */
    public function is_connected()
    {
        return bswp_is_connected();
    }

    public function test_connection()
    {
        // 
    }

    /**
     * @param string $list_identifier - the ID of the mailing list
     * @param array  $data            - an array of what we want to send as subscriber data
     * @param bool   $is_update
     *
     * @return boolean
     */
    public function add_subscriber( $list_identifier, $data, $is_update = false )
    {
        $data = $this->process_subscriber_data( $list_identifier, $data );

        try {
            $params = [
                'search_by' => 'email',
                'keyword' => $data['email'],
                'limit' => 1,
            ];

            $response = bswp_api_request( 'GET', 'contacts', $params );

            if ( ! $response['data'] ) {
                // Create contact API
                return bswp_api_request( 'POST', 'contacts', $data );
            }

            $contact = $response['data'][0];

            if ( $is_update ) {
                // Update contact API
                bswp_api_request( 'PATCH', 'contacts/'.$contact['contact_id'], $data );
            }

            if ( ! empty( $data['form_id'] ) ) {
                bswp_api_request( 'POST', 'contacts/'.$contact['contact_id'].'/subscribe', $data );
            }

            if ( ! empty( $data['tags'] ) ) {
                $params = [
                    'tags' => $data['tags'],
                ];
                // Add tag API
                return bswp_api_request( 'POST', 'contacts/'.$contact['contact_id'].'/tags', $params );
            }
        } catch ( \Exception $e ) {
            error_log( $e->getMessage() );
        }
    }

    /**
     * @param mixed $list_identifier
     * @param array $data
     *
     * @return mixed
     */
    public function process_subscriber_data( $list_identifier, $data ) {
        if ( isset( $data[$this->get_tags_key()] ) ) {
            $data['tags'] = $this->parse_tags( $data[$this->get_tags_key()] );
        }

        if ( $list_identifier ) {
            $data['form_id'] = $list_identifier;
        }

        if ( ! empty( $data['tve_mapping'] ) ) {
            /**
             * When the request is coming from a Thrive Architect form, if it contains the 'tve_mapping' field it means that there are encoded custom fields inside.
             * In that case, the custom field helper class parses the data and returns the custom fields that must be added here.
             */
            $custom_fields = $this->get_custom_field_instance()->parse_custom_fields( $data );

            if ( ! empty( $custom_fields ) ) {
                $data['fields'] = $custom_fields;
            }
            unset( $data['tve_mapping'] );
        } else if ( ! empty( $data['automator_custom_fields'] ) ) {
            /**
             * If the request contains custom fields data from automator, we can add it directly since it's sent in the proper format.
             * The contents are processed beforehand inside the 'build_automation_custom_fields' function.
             */
            $data['fields'] = $data['automator_custom_fields'];
            unset( $data['automator_custom_fields'] );
        }

        return $data;
    }

    /**
     * @return array
     */
    public function get_lists()
    {
        $params = [
            'search_by' => 'active',
            'keyword' => 1,
            'order_by' => 'name',
            'sort' => 'asc',
            'per_page' => 500,
            'novalidate' => 'per_page',
        ];
        $response = bswp_api_request( 'GET', 'forms', $params );
        return array_map(
            function ($form) {
                $form['id'] = $form['form_id'];
                return $form;
            }, $response['data']
        );
    }

    /**
     * False by default.
     *
     * In order to implement tags:
     * - set this to true;
     * - implement get_tags_key();
     * - process the 'tags' field inside add_subscriber() and add it to the request;
     * - override get_automator_add_autoresponder_mapping_fields() to also contain the 'tag_input' key ( for Thrive Automator );
     * - implement get_automator_tag_autoresponder_mapping_fields() ( for Thrive Automator );
     * - implement update_tags() ( for Thrive Automator );
     * - implement push_tags() ( for Thrive Quiz Builder );
     * - if needed, adapt autoresponders\clever-reach\assets\js\editor.js to suit your API - used by Thrive Architect
     *
     * A working example can be found in the clever-reach folder.
     *
     * @return bool
     */
    public function has_tags() {
        return true;
    }

    /**
     * API-unique tag identifier.
     *
     * @return string
     */
    public function get_tags_key() {
        return $this->get_key().'_tags';
    }

    /**
     * Parse tags
     *
     * @param mixed $tags
     *
     * @return array
     */
    protected function parse_tags( $tags )
    {
        if ( is_string( $tags ) ) {
            $tags = array_filter( array_map( 'trim', explode( ',', $tags ) ) );
        }
        return $tags;
    }

    /**
     * This is called from Thrive Automator when the 'Tag user' automation is triggered.
     * In this case, we want to add the received tags to the received subscriber and mailing list.
     * This is only done if the subscriber already exists.
     *
     * @param string $email
     * @param string $tags
     * @param array  $extra
     *
     * @return bool
     */
    public function update_tags( $email, $tags = '', $extra = [] ) {
        try {
            $params = [
                'search_by' => 'email',
                'keyword' => $email,
                'limit' => 1,
            ];

            $response = bswp_api_request( 'GET', 'contacts', $params );

            if ( $response['data'] ) {
                $contact = $response['data'][0];
                $params = [
                    'tags' => $this->parse_tags( $tags ),
                ];
                return bswp_api_request( 'POST', 'contacts/'.$contact['contact_id'].'/tags', $params );
            }
        } catch ( \Exception $e ) {
            error_log( $e->getMessage() );
        }

        $args = [
            'email' => $email,
            $this->get_tags_key() => $tags,
        ];

        return $this->add_subscriber( '', $args, true );
    }

    /**
     * This is called from Thrive Quiz Builder and it is used to add an array of tags to already existing ones.
     * Only used if has_tags() is enabled.
     *
     * @param array|string $tags
     * @param array        $data
     *
     * @return array
     */
    public function push_tags( $tags, $data = [] ) {
        if ( empty( $tags ) || ! $this->has_tags() ) {
            return $data;
        }

        if ( is_array( $tags ) ) {
            $tags = implode( ',', $tags );
        } else if ( ! is_string( $tags ) ) {
            $tags = '';
        }

        $tag_key = $this->get_tags_key();

        if ( empty( $data[ $tag_key ] ) ) {
            $tag_data = $tags;
        } else {
            $tag_data = $data[ $tag_key ] . ( empty( $tags ) ? '' : ',' . $tags );
        }

        $data[ $tag_key ] = trim( $tag_data );

        return $data;
    }

    /**
     * Enables the mailing list, forms, opt-in type and tag features inside Thrive Automator.
     * Check the parent method for an explanation of the config structure.
     *
     * @return \string[][]
     */
    public function get_automator_add_autoresponder_mapping_fields() {
        return [ 'autoresponder' => [ 'mailing_list' => [ 'api_fields' ], 'tag_input' => [] ] ];
    }

    /**
     * Get field mappings specific to an API with tags. Has to be set like this in order to enable tags inside Automator.
     * @return string[][]
     */
    public function get_automator_tag_autoresponder_mapping_fields() {
        return [ 'autoresponder' => [ 'tag_input' ] ];
    }

    /**
     * Since custom fields are enabled, this is set to true.
     *
     * @return bool
     */
    public function has_custom_fields() {
        return true;
    }

    /**
     * Since the implementation covers the clever-reach global custom fields, this function returns all of them.
     *
     * @return array
     */
    public function get_custom_fields_by_list() {
        return $this->get_api_custom_fields();
    }

    /**
     * Returns all the types of custom field mappings
     *
     * @return \string[][]
     */
    public function get_custom_fields() {
        return CustomFields::get_custom_field_types();
    }

    /**
     * Retrieves all the used custom fields. Currently it returns all the inter-group (global) ones.
     *
     * @param array $params  which may contain `list_id`
     * @param bool  $force
     * @param bool  $get_all whether to get lists with their custom fields
     *
     * @return array
     */
    public function get_api_custom_fields( $params = [], $force = false, $get_all = true ) {
        $custom_fields = [];

        try {
            $custom_fields = $this->get_custom_field_instance()->get_custom_fields();
        } catch ( \Exception $e ) {
            error_log( $e->getMessage() );
        }

        return $custom_fields;
    }

    /**
     * Builds custom fields mapping for automations.
     * Called from Thrive Automator when the custom fields are processed.
     *
     * @param $automation_data
     *
     * @return array
     */
    public function build_automation_custom_fields( $automation_data ) {
        return $this->get_custom_field_instance()->build_automation_fields( $automation_data );
    }

    /**
     * @return CustomFields
     */
    public function get_custom_field_instance() {
        return new CustomFields( $this->get_key() );
    }

    /**
     * This function is somehow required by thrive leads
     *
     * @return array
     */
    public function get_custom_fields_mapping()
    {
        return [];
    }

    /**
     * This function is somehow required by thrive leads
     *
     * @return array
     */
    public function get_default_fields_mapper()
    {
        return [];
    }

    /**
     * Thumbnail shown in the Thrive Dashboard API connections tab.
     *
     * @return string
     */
    public static function get_thumbnail() {
        return BSWP_IMG.'logo-for-thrive.svg';
    }

    /**
     * @return string
     */
    public static function get_link_to_controls_page() {
        return admin_url( 'admin.php?page=bswp-settings' );
    }

    /**
     * @return string
     */
    public static function get_type() {
        return 'autoresponder';
    }
}