<?php

/**
 * Admin actions
 *
 * @return void
 */
function bswp_admin_action() {
    if (! empty($_POST['bswp_submit'])) {
        $action = $_POST['bswp_submit'];

        switch ($action) {
            case 'connect':
                if (bswp_do_connect($_POST['bswp_email'], $_POST['bswp_password'])) {
                    wp_redirect('admin.php?page=bswp-settings');
                }
                break;

            case 'disconnect':
                bswp_do_disconnect();
                wp_redirect('admin.php?page=bswp-settings');
                break;

            case 'save_options':
                bswp_save_options();
                wp_redirect('admin.php?page=bswp-settings&msg=options_updated');
            
            default:
                # code...
                break;
        }
    }
}
add_action('admin_init', 'bswp_admin_action');

/**
 * Admin notice
 *
 * @return void
 */
function bswp_admin_notice() {
    if (isset($_GET['page']) && $_GET['page'] == 'bswp-settings') {
        if (isset($_GET['error']) && $_GET['error'] == 'cant_connect') {
            echo '<div class="notice notice-error">';
            echo '<p>There was error connecting your BirdSend account. Please try again or contact us if the problem persists!</p>';
            echo '</div>';
        }

        if (isset($_GET['msg']) && $_GET['msg'] == 'options_updated') {
            echo '<div class="notice notice-success">';
            echo '<p>Success! BirdSend Pixel Settings has been updated.</p>';
            echo '</div>';
        }
    }
}
add_action('admin_notices', 'bswp_admin_notice');

/**
 * Oauth URL
 *
 * @param atring $path Path
 *
 * @return string
 */
function bswp_oauth_url($path) {
    return BSWP_OAUTH_URL . $path;
}

/**
 * Connect using password grant token
 *
 * @param string $email    Email
 * @param string $password Password
 * @param string $scope    Scope
 *
 * @return array
 */
function bswp_do_connect($email, $password, $scope = '') {
    $http = new GuzzleHttp\Client;
    try {
        $response = $http->post(bswp_oauth_url('oauth/token'), [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => BSWP_CLIENT_ID,
                'client_secret' => BSWP_CLIENT_SECRET,
                'username' => $email,
                'password' => $password,
                'scope' => $scope
            ]
        ]);
        
        $response = json_decode((string) $response->getBody(), true);
        update_option('bswp_token', $response);

        return $response;
    } catch (GuzzleHttp\Exception\ClientException $e) {
        if (WP_DEBUG) {
            echo $e->getMessage();
        } else {
            wp_redirect(admin_url('admin.php?page=bswp-settings&error=cant_connect'));
            exit;
        }
    }
}

/**
 * Disconnect birdsend token
 *
 * @return void
 */
function bswp_do_disconnect() {
    delete_option('bswp_token');
    delete_option('bswp_pixel_code');
}

/**
 * Get and verify token
 *
 * @return string|bool
 */
function bswp_token() {
    if (! ($token = get_option('bswp_token')) || ! isset($token['access_token'])) {
        return false;
    }
    return $token['access_token'];
}

/**
 * Get pixel code
 *
 * @return string
 */
function bswp_pixel_code() {
    if (! $pixel_code = get_option('bswp_pixel_code')) {
        return bswp_get_pixel_code();
    }
    return $pixel_code;
}

/**
 * Get pixel code
 *
 * @return string
 */
function bswp_get_pixel_code() {
    if ($response = bswp_api_request('GET', 'pixels/code')) {
        update_option('bswp_pixel_code', $response['code']);
        return $response['code'];
    }
    return false;
}

/**
 * API request
 *
 * @param string $method Method
 * @param string $path   Path
 * @param array  $data   Data
 *
 * @return array
 */
function bswp_api_request($method, $path, $data = array()) {
    if (! $token = bswp_token()) {
        return;
    }
    $http = new GuzzleHttp\Client;
    try {
        $options = array(
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            )
        );

        if ($data) {
            $options['form_params'] = $data;
        }

        $response = $http->request($method, bswp_oauth_url('v1/' . $path), $options);
        $response = json_decode((string) $response->getBody(), true);

        return $response;
    } catch (GuzzleHttp\Exception\ClientException $e) {
        if (WP_DEBUG) {
            echo $e->getMessage();
        }
    }
    return false;
}

/**
 * Save options
 *
 * @return void
 */
function bswp_save_options() {
    $options = $_POST['bswp_options'];
    if (! isset($options['enabled'])) {
        $options['enabled'] = false;
    }
    return update_option('bswp_options', $options);
}

/**
 * Get plugin options
 *
 * @return array
 */
function bswp_options() {
    return get_option('bswp_options', array());
}

/**
 * Is enabled
 *
 * @return bool
 */
function bswp_is_enabled() {
    $options = bswp_options();
    return isset($options['enabled']) ? $options['enabled'] : !!bswp_token();
}

/**
 * Inject pixel into all posts/pages/customs
 *
 * @return void
 */
function bswp_inject_pixel() {
    if (! bswp_is_enabled() )
        return;

    // Make sure this is a single post/page
    if ( !is_single() )
        return;

    // Get the post data
    $post = get_post();
    if (empty($post->post_type))
        return;

    $options = bswp_options();
    $key = in_array($post->post_type, array('post', 'page'))
        ? 'excluded_' . $post->post_type . 's'
        : 'excluded_custom_' . $post->post_type;

    if (isset($options[$key]) && in_array($post->ID, $options[$key]))
        return;

    echo '<!-- BirdSend Pixel Start -->' . "\n";
    echo bswp_pixel_code() . "\n";
    echo '<!-- BirdSend Pixel End -->' . "\n";
}
add_action('wp_head', 'bswp_inject_pixel');