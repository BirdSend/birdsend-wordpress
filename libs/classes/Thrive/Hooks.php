<?php
namespace BSWP\Thrive;

class Hooks
{
    /**
     * Init hooks
     *
     * @return void
     */
    public static function init()
    {
        /* integrate with TCB editor - add a HTML template and functionality for Lead Generation API Connections */
        add_action( 'tcb_editor_enqueue_scripts', [ __CLASS__, 'enqueue_architect_scripts' ] );

        add_filter( 'tcb_lead_generation_apis_with_tag_support', [ __CLASS__, 'tcb_apis_with_tags' ] );
    }

    /**
     * Enqueue an additional script inside Thrive Architect in order to add some custom hooks which integrate Clever-Reach with the Lead Generation element API Connections.
     */
    public static function enqueue_architect_scripts() {
        wp_enqueue_script( 'thrive-architect-api-integration', BSWP_URL . 'assets/js/thrive.js', [ 'tve_editor' ] );

        $localized_data = [
            'api_logo' => BSWP_URL . 'assets/img/birdsend-icon.svg',
            'api_key' => App::API_KEY,
        ];

        wp_localize_script( 'thrive-architect-api-integration', 'thrive_birdsend_data', $localized_data );
    }

    /**
     * Add Clever-Reach to the list of supported APIs with tags. Required inside TCB.
     *
     * @param $apis
     *
     * @return mixed
     */
    public static function tcb_apis_with_tags( $apis ) {
        $apis[] = 'birdsend';

        return $apis;
    }
}