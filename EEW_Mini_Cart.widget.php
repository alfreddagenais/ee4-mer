<?php if (!defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license				http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link						http://www.eventespresso.com
 * @ version		 	3.2.P
 *
 * ------------------------------------------------------------------------
 *
 * EEW_Mini_Cart class
 *
 * @package			Event Espresso
 * @subpackage		includes/classes
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EEW_Mini_Cart extends WP_Widget {

	/**
	 * @access protected
	 * @var \EE_Cart
	 */
	protected $_cart = NULL;



	/**
	*		EE_Mini_Cart
	*
	*		@access public
	*		@return void
	*/
	function EEW_Mini_Cart() {
		$widget_options = array(
			'classname' => 'espresso-mini-cart',
			'description' => __('A widget for displaying the Event Espresso Mini Cart.', 'event_espresso')
		);
		$this->WP_Widget( 'espresso_minicart', 'Event Espresso Mini Cart Widget', $widget_options );

	}



	/**
	 *        build the widget settings form
	 *
	 * @access public
	 * @param array $instance
	 * @return string|void
	 */
	function form( $instance ) {

		$defaults = array( 'title' => 'Your Registrations', 'template' => 'widget_minicart' );

		$instance = wp_parse_args( (array)$instance, $defaults );

		echo '
	<p>' . __('Mini Cart Title:', 'event_espresso') . '
		<input id="'.$this->get_field_id('title').'" class="widefat" name="'.$this->get_field_name('title').'"  type="text" value="'.esc_attr( $instance['title'] ).'" />
	</p>';

//		$default_templates = glob( EE_MER_PATH.'templates/widget_minicart*.template.php' );
//		$custom_templates = glob( EE_MER_PATH.'widget_minicart*.template.php' );
//
//		$minicart_templates = array_merge( $default_templates, $custom_templates );
//		rsort( $minicart_templates, SORT_STRING );
//
//		$find = array ( EE_MER_PATH.'templates/widget', EE_MER_PATH.'widget', '.template.php', '-', '_' );
//		$replace = array( '', '', '', ' ', ' ' );
//
//		echo '
//	<p>' . __('Mini Cart Template:', 'event_espresso') . '<br />
//		<select name="'.$this->get_field_name( 'template' ).'">';
//
//		foreach ( $minicart_templates as $minicart_template ) {
//
//			$template = str_replace( $find, $replace, $minicart_template );
//
//			echo "\n\t\t\t".'<option value="'.$minicart_template.'" '.selected( $instance['template'], $minicart_template ).'>'.$template.'&nbsp;&nbsp;&nbsp;</option>';
//
//		}
//
//		echo '
//		</select>
//	</p>
//';
	}



	/**
	 *        save the widget settings
	 *
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['template'] = ! empty( $new_instance[ 'template' ] ) ? strip_tags( $new_instance['template'] ) : EE_MER_PATH . 'templates' . DS . 'widget_minicart_table.template.php';
		return $instance;
	}



	/**
	 *        display the widget
	 *
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @throws \EE_Error
	 */
	function widget( $args, $instance ) {
		if ( isset( $_REQUEST[ 'event_queue' ] ) ) {
			return;
		}
		EE_Registry::instance()->load_core( 'Cart' );
		EE_Registry::instance()->load_helper( 'Line_Item' );

		extract($args);
		/** @type string $before_widget */
		/** @type string $after_widget */
		/** @type string $before_title */
		/** @type string $after_title */

		$template_args = array();
		$template_args['before_widget'] = $before_widget;
		$template_args['after_widget'] = $after_widget;
		$template_args['before_title'] = $before_title;
		$template_args['after_title'] = $after_title;
		$template_args['title'] = apply_filters( 'widget_title', $instance['title'] );

		$template_args[ 'total_items' ] = EE_Registry::instance()->CART->all_ticket_quantity_count();
		$template_args[ 'events_list_url' ] = EE_EVENTS_LIST_URL;
		$template_args[ 'register_url' ] = EE_EVENT_QUEUE_BASE_URL;
		$template_args[ 'view_event_queue_url' ] = add_query_arg( array( 'event_queue' => 'view' ), EE_EVENT_QUEUE_BASE_URL );

		switch ( $instance[ 'template' ] ) {

			case '' :
				$mini_cart_line_item_display_strategy = '';
				break;

			case EE_MER_PATH . 'templates' . DS . 'widget_minicart_table.template.php' :
			default :
			$mini_cart_line_item_display_strategy = 'EE_Mini_Cart_Table_Line_Item_Display_Strategy';
				break;

		}
		$template_args[ 'event_queue' ] = $this->_get_event_queue(
			apply_filters(
				'FHEE__EEW_Mini_Cart__widget__mini_cart_line_item_display_strategy',
				$mini_cart_line_item_display_strategy
			)
		);
		//espresso_display_template( $instance['template'], $template_args );
		$mini_cart_css = '
<style>
.mini-cart-tbl-qty-th,
.mini-cart-tbl-qty-td {
	padding: 0 .25em;
}
#mini-cart-whats-next-buttons {
	text-align: right;
}
.mini-cart-button {
	box-sizing: border-box;
	display: inline-block;
	padding: 8px;
	margin: 4px 0 4px 4px;
	vertical-align: middle;
	line-height: 8px;
	font-size: 12px;
	font-weight: normal;
	-webkit-user-select: none;
	border-radius: 2px !important;
	text-align: center !important;
	cursor: pointer !important;
	white-space: nowrap !important;
}
</style>
		';
		echo apply_filters( 'FHEE__EEW_Mini_Cart__widget__mini_cart_css', $mini_cart_css );
		echo EEH_Template::display_template( $instance[ 'template' ], $template_args, true );

	}



	/**
	 *    _get_event_queue
	 *
	 * @access        protected
	 * @param string $line_item_display_strategy
	 * @return string
	 * @throws \EE_Error
	 */
	protected function _get_event_queue( $line_item_display_strategy = 'EE_Mini_Cart_Table_Line_Item_Display_Strategy' ) {
		$line_item = EE_Registry::instance()->CART->get_grand_total();
		// autoload Line_Item_Display classes
		EEH_Autoloader::register_line_item_display_autoloaders();
		$Line_Item_Display = new EE_Line_Item_Display( 'event_queue', $line_item_display_strategy );
		if ( ! $Line_Item_Display instanceof EE_Line_Item_Display && WP_DEBUG ) {
			throw new EE_Error( __( 'A valid instance of EE_Event_Queue_Line_Item_Display_Strategy could not be obtained.', 'event_espresso' ) );
		}
		return $Line_Item_Display->display_line_item( $line_item );
	}

}

/* End of file EE_Mini_Cart_widget.class.php */
/* Location: /includes/classes/EE_Mini_Cart_widget.class.php */