<?php

/**
 * BSWP_WidgetForm
 */
class BSWP_WidgetForm extends WP_Widget
{
	/**
	 * Create new BSWP_WidgetForm instance
	 *
	 * @return void
	 */
	function __construct()
	{
		$instance = array('description' => 'Drag n drop this widget to display BirdSend form.' );
		parent::__construct('bswp-form', 'BirdSend - Form', $instance);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param mixed $args
	 * @param mixed $instance
	 *
	 * @return mixed
	 */
	public function widget( $args, $instance )
	{
		echo $args['before_widget'];
 
		echo '<div class="textwidget bswp-form-widget">';

		if (! empty( $instance[ 'form_id' ] ) ) {
			echo '<div data-birdsend-form-widget="' . esc_attr( $instance[ 'form_id' ] ) . '"></div>';
		}
 
		echo '</div>';
 
		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form in the admin
	 *
	 * @param mixed $instance
	 *
	 * @return mixed
	 */
	public function form( $instance )
	{
		$form_id = ! empty( $instance['form_id'] ) ? $instance['form_id'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>">Form:</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'form_id' ) ); ?>">
				<option value="">None</option>
				<?php foreach (bswp_get_forms() as $form) { ?>
				<?php if ($form['active']) { ?>
				<option value="<?php echo $form['form_id']; ?>"<?php echo ( $form['form_id'] == $form_id ? ' selected' : '' ) ?>><?php echo $form['name']; ?></option>
				<?php } ?>
				<?php } ?>
			</select>
		</p>
		<p>
			<a href="<?php echo bswp_app_url( 'forms/new' ); ?>" target="_blank">Create New Form</a>
		</p>
		<?php
	}

	/**
	 * Processes widget options to be saved
	 *
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 *
	 * @return mixed
	 */
	public function update( $new_instance, $old_instance )
	{
		$instance = [];
		$instance['form_id'] = ( !empty( $new_instance['form_id'] ) ) ? strip_tags( $new_instance['form_id'] ) : '';
		return $instance;
	}
}

add_action( 'widgets_init', 'bswp_register_widget' );
function bswp_register_widget() {
	register_widget( 'BSWP_WidgetForm' );
}