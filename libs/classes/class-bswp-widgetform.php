<?php

/**
 * BSWP_WidgetForm
 */
class BSWP_WidgetForm extends WP_Widget {

	/**
	 * Create new BSWP_WidgetForm instance
	 */
	public function __construct() {
		$instance = array( 'description' => 'Drag n drop this widget to display BirdSend form.' );
		parent::__construct( 'bswp-form', 'BirdSend - Form', $instance );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		echo '<div class="textwidget bswp-form-widget">';

		if ( ! empty( $instance['form_id'] ) ) {
			echo '<div data-birdsend-form-widget="' . esc_attr( $instance['form_id'] ) . '"></div>';
		}

		echo '</div>';

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form in the admin
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$form_id = ! empty( $instance['form_id'] ) ? $instance['form_id'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>">Form:</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'form_id' ) ); ?>">
				<option value="">None</option>
			</select>
		</p>
		<p>
			<a href="<?php echo esc_url( bswp_app_url( 'forms/new' ) ); ?>" target="_blank">Create New Form</a>
		</p>
		<script>
		(function () {
			var data = { 'action': 'bswp_ajax_get_forms' },
				id = '<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>',
				value = '<?php echo esc_attr( $form_id ); ?>';
			jQuery.post(ajaxurl, data, function(response) {
				JSON.parse(response).forEach(function (form) {
					var option = '<option value="' + form.form_id + '"' + ( form.form_id == value ? ' selected' : '' ) + '>' + form.name + '</option>';
					jQuery('#' + id).append(option);
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Processes widget options to be saved
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance            = array();
		$instance['form_id'] = ( ! empty( $new_instance['form_id'] ) ) ? wp_strip_all_tags( $new_instance['form_id'], true ) : '';
		return $instance;
	}
}

/**
 * Register BirdSend form widget
 */
function bswp_register_widget() {
	register_widget( 'BSWP_WidgetForm' );
}
add_action( 'widgets_init', 'bswp_register_widget' );
