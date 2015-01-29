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
 * EEW_Minicart class
 *
 * @package			Event Espresso
 * @subpackage		includes/classes
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EEW_Minicart extends WP_Widget {

	/**
	 * @access protected
	 * @var \EE_Cart
	 */
	protected $_cart = NULL;



	/**
	*		EE_Minicart
	*
	*		@access public
	*		@return void
	*/
	function EEW_Minicart() {
		// if cart ( and sessions ) is not instantiated
		$this->_cart = EE_Registry::instance()->load_core( 'Cart' );
		$widget_options = array(
													'classname' => 'espresso-mini-cart  ui-widget',
													'description' => __('A widget for displaying Event Espresso regsitrations and purchases.', 'event_espresso')
												);
		$this->WP_Widget( 'espresso_minicart', 'Event Espresso Mini Cart Widget', $widget_options );

	}





	/**
	*		build the widget settings form
	*
	*		@access public
	*		@return void
	*/
	function form( $instance ) {

//		echo '<h1>'. __CLASS__ .'->'.__FUNCTION__.'</h1>';

		$defaults = array( 'title' => 'Event Queue', 'template' => 'widget_minicart' );

		$instance = wp_parse_args( (array)$instance, $defaults );

		echo '
	<p>' . __('Mini Cart Title:', 'event_espresso') . '
		<input id="'.$this->get_field_id('title').'" class="widefat" name="'.$this->get_field_name('title').'"  type="text" value="'.esc_attr( $instance['title'] ).'" />
	</p>
	<p>' . __('Display content for the following carts:', 'event_espresso') . '

	<ul>';

	$this->_cart->get_cart_types();



		$cart_contents = array();
		$cart_type = 'tickets';
		//foreach ( $this->_cart->cart as $cart_type => $cart_contents ) {

			$label = isset( $cart_contents['title'] ) ? $cart_contents['title'] : '' ;
			$chk = 'display-'.$cart_type.'-chk';
			$txt = 'cart-name-'.$cart_type.'-txt';
			if ( ! isset( $instance[$chk] )) {
				$instance[$chk] = FALSE;
			}
			if ( ! isset( $instance[$txt] )) {
				$instance[$txt] = 'Your Registrations:';
			}

		echo '
		<li>
			<label>Custom Title:
				<input id="'.$this->get_field_id($txt).'" class="widefat" name="'.$this->get_field_name($txt).'"  type="text" value="'.esc_attr( $instance[$txt] ).'" />
			</label>
		</li>
';

		$default_templates = glob( MER_DIR_PATH.'templates/widget_minicart*.template.php' );
		$custom_templates = glob( MER_DIR_PATH.'widget_minicart*.template.php' );

		$minicart_templates = array_merge( $default_templates, $custom_templates );
		rsort( $minicart_templates, SORT_STRING );

		$find = array ( MER_DIR_PATH.'templates/widget', MER_DIR_PATH.'widget', '.template.php', '-', '_' );
		$replace = array( '', '', '', ' ', ' ' );

		echo '
	</ul>
	</p>
	<p>' . __('Mini Cart Template:', 'event_espresso') . '
		<select name="'.$this->get_field_name( 'template' ).'">';

		foreach ( $minicart_templates as $minicart_template ) {

			$template = str_replace( $find, $replace, $minicart_template );

			echo "\n\t\t\t".'<option value="'.$minicart_template.'" '.selected( $instance['template'], $minicart_template ).'>'.$template.'&nbsp;&nbsp;&nbsp;</option>';

		}

		echo '
		</select>
	</p>
';
	}





	/**
	*		save the widget settings
	*
	*		@access public
	*		@return void
	*/
	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		$cart_types = $this->_cart->get_cart_types();

		foreach ( $cart_types as $cart_type ) {

			$cart_contents = $this->_cart->whats_in_the_cart( $cart_type );
		//	foreach ( $this->_cart->cart as $cart_type => $cart_contents ) {

			$chk = 'display-'.$cart_type.'-chk';
			$instance[$chk] = strip_tags( $new_instance[$chk] );

			$txt = 'cart-name-'.$cart_type.'-txt';
			$instance[$txt] = strip_tags( $new_instance[$txt] );

		}

		$instance['template'] = strip_tags( $new_instance['template'] );

		return $instance;
	}





	/**
	*		display the widget
	*
	*		@access public
	*		@return void
	*/
	function widget( $args, $instance ) {

		if ( isset( $_GET['e_reg'] )) {
			$regevent = $_GET['e_reg'];
		} else {
			$regevent = FALSE;
		}

		$no_minicart_pages = array( 'event_queue', 'register' );

		if ( ! in_array( $regevent, $no_minicart_pages )) {

			extract($args);
			/** @type string $before_widget */
			/** @type string $after_widget */
			/** @type string $before_title */
			/** @type string $after_title */

			$template_args = array();
			$mini_cart = array();

			global $org_options;

			do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');

			$template_args['currency_symbol'] = $org_options[ 'currency_symbol' ];
			$template_args['before_widget'] = $before_widget;
			$template_args['after_widget'] = $after_widget;

			$template_args['title'] = $before_title . apply_filters( 'widget_title', $instance['title'] ) . $after_title;

			$grand_total = 0;
			$total_items = 0;

			$cart_types = $this->_cart->get_cart_types();

			foreach ( $cart_types as $cart_type ) {

				$cart_contents = $this->_cart->whats_in_the_cart( $cart_type );

				$chk = 'display-'.$cart_type.'-chk';
				$txt = 'cart-name-'.$cart_type.'-txt';

				if ( $instance[$chk] == $cart_type ) {

					if ( isset( $instance[$txt] ) && $instance[$txt] != '' ) {
						$cart_title = $instance[$txt];
					} elseif ( isset( $cart_contents['title'] ) && $cart_contents['title'] != '' ) {
						$cart_title = __($cart_contents['title'], 'event_espresso');
					} else {
						$cart_title = '';
					}

					$mini_cart[$cart_type]['title'] = $cart_title;

					if ( $cart_contents['total_items'] !== 0 ) {

						$mini_cart[$cart_type]['has_items'] = TRUE;

						foreach ( $cart_contents['items'] as $item ) {

							$mini_cart[$cart_type]['items'][ $item['line_item'] ]['name'] = $item['name'];
							$mini_cart[$cart_type]['items'][ $item['line_item'] ]['price'] = number_format( $item['price'], 2, '.', '' );
							$mini_cart[$cart_type]['items'][ $item['line_item'] ]['qty'] = $item['qty'];
							$mini_cart[$cart_type]['items'][ $item['line_item'] ]['line_total'] = number_format( $item['line_total'], 2, '.', '' );

						}

						$mini_cart[$cart_type]['total_items'] = $cart_contents['total_items'];
						$mini_cart[$cart_type]['sub_total'] = number_format( $cart_contents['sub_total'], 2, '.', '' );

					} else {
						// empty
						$mini_cart[$cart_type]['has_items'] = FALSE;
					}

					$total_items = $total_items + $cart_contents['total_items'];
					$grand_total = $grand_total + $cart_contents['sub_total'];

				} else {
					$mini_cart[$cart_type]['title'] = '';
					$mini_cart[$cart_type]['has_items'] = FALSE;
				}

				$mini_cart[$cart_type]['empty_msg'] = $cart_contents['empty_msg'];

			}

			$template_args['total_items'] = $total_items;
			$template_args['grand_total'] = number_format( $grand_total, 2, '.', '' );
			$template_args['mini_cart'] = $mini_cart;
			$template_args['nmbr_of_carts'] = count($mini_cart);

			$event_page_id = $org_options['event_page_id'];
			$permalink = get_permalink( $event_page_id );
			$template_args['view_event_queue_url'] = $permalink . '?e_reg=event_queue';
			$template_args['empty_event_queue_url'] = $permalink . '?e_reg=eq_empty_event_queue';

			espresso_display_template( $instance['template'], $template_args );

		}

	}



}

/* End of file EE_Minicart_widget.class.php */
/* Location: /includes/classes/EE_Minicart_widget.class.php */