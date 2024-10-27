<?php
/*
Plugin Name: Arena Smart Lists
Description: Sidebar widget for recent posts, related posts, popular/trending posts, post thumbnail display and ajax view counter.
Author: Html5Arena <info@html5arena.com>
Version: 1.0
Author URI: https://www.html5arena.com/
*/

class ARENA_Widget_Smart_List extends WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'classname' => 'arenawidget_smart_entries',
			'description' => __( 'Your site&#8217;s most recent Posts.' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'arena-smart-lists', __( 'Arena Smart Lists' ), $widget_ops );
		$this->alt_option_name = 'widget_smart_entries';
	}

	public function widget( $args, $instance ) {

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Posts' );

		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		
		if ( ! $number )
			$number = 5;

		$hide_img = isset( $instance['hide_img'] ) ? $instance['hide_img'] : false;

		$hide_onblog = isset( $instance['hide_onblog'] ) ? $instance['hide_onblog'] : false;

		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;

		$query_type = (!empty( $instance['query_type'] )) ? $instance['query_type'] : 'Recent';

		/* This global var is for collecting the post IDs for posts so that post will */
		/* not be repeated when multi widgets are used */
		global $arena_cnt;

		$qargs = array ('posts_per_page'      => $number,
				'no_found_rows'       => true,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true);
		
		if (get_the_ID() !== false)
		{
			$post_id = get_the_ID();
			$arena_cnt[] = get_the_ID();
			$qargs ['post__not_in'] = $arena_cnt;
		}

		if ($query_type == 'Related')
		{
			$cats = get_the_category();
			if ( ! empty( $cats )  )
			{
				$first_cat = $cats[0]->term_id;
				$qargs['category__in'] = array($first_cat);
			}
		} else if ($query_type == 'Popular') {
			$qargs['meta_key' ]= 'post_views';
			$qargs['orderby'] = 'meta_value_num' ;
			$qargs['order'] = 'DESC';
		} else if ($query_type == 'Trending') {
			$qargs['meta_key' ]= 'post_view_7days';
			$qargs['orderby'] = 'meta_value_num' ;
			$qargs['order'] = 'DESC';
		}

		$hide_blog = false;
		if (is_home() && $hide_onblog == true) $hide_blog = true;

		if ($hide_blog == false) {
		$r = new WP_Query( apply_filters( 'widget_posts_args', $qargs) );

		if ($r->have_posts()) :
		?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} ?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php if ($hide_img !== true) the_post_thumbnail( 'thumbnail');?><?php get_the_title() ? the_title() : the_ID(); ?></a>
			<?php if ( $show_date ) : ?><br>
				<span class="post-date"><?php echo get_the_date(); ?></span>
			<?php endif; array_push($arena_cnt,get_the_ID());?>
			</li>
		<?php endwhile; ?>
		</ul>
		<?php echo $args['after_widget']; ?>
		<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;
	    }
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$instance['hide_img'] = isset( $new_instance['hide_img'] ) ? (bool) $new_instance['hide_img'] : false;
		$instance['hide_onblog'] = isset( $new_instance['hide_onblog'] ) ? (bool) $new_instance['hide_onblog'] : false;
		$instance['query_type'] = $new_instance['query_type'];
		return $instance;
	}

	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		$query_type = isset( $instance['query_type'] ) ? $instance['query_type'] : 'Recent';
		$hide_img = isset( $instance['hide_img'] ) ? (bool) $instance['hide_img'] : false;
		$hide_onblog = isset( $instance['hide_onblog'] ) ? (bool) $instance['hide_onblog'] : false;

?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?' ); ?></label></p>

		<p>
		<label for="<?php echo $this->get_field_id( 'query_type' ); ?>"><?php _e( 'Choose query type' ); ?></label>
		<select id="<?php echo $this->get_field_id( 'query_type' ); ?>" name="<?php echo $this->get_field_name( 'query_type' ); ?>">
		<option value="Recent" <?php if ($query_type == 'Recent') echo "selected";?> >Recent</option>
		<option value="Related" <?php if ($query_type == 'Related') echo "selected";?>>Related</option>
		<option value="Popular" <?php if ($query_type == 'Popular') echo "selected";?>>Popular</option>
		<option value="Trending" <?php if ($query_type == 'Trending') echo "selected";?>>Trending</option>
		</select></p>
		<p><input class="checkbox" type="checkbox"<?php checked( $hide_img ); ?> id="<?php echo $this->get_field_id( 'hide_img' ); ?>" name="<?php echo $this->get_field_name( 'hide_img' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'hide_img' ); ?>"><?php _e( 'Hide featured images?' ); ?></label></p>
		<p><input class="checkbox" type="checkbox"<?php checked( $hide_onblog ); ?> id="<?php echo $this->get_field_id( 'hide_onblog' ); ?>" name="<?php echo $this->get_field_name( 'hide_onblog' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'hide_onblog' ); ?>"><?php _e( 'Hide On Blog page ?' ); ?></label></p>

<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("ARENA_Widget_Smart_List");'));

add_action('wp_head', 'arena_custom_styles', 100);

function arena_custom_styles(){

 echo "<style>.arenawidget_smart_entries ul li:hover img{opacity:0.7}.arenawidget_smart_entries ul li img {float:left;margin-right:0.5em;margin-top:4px;width:100px;height:70px;}.arenawidget_smart_entries ul li {min-height:92px;font-size:90%} </style>";
}
add_action('wp_footer', 'arena_ajax_footer', 100);
function arena_ajax_footer()
{
	if (is_single() && !is_admin())
	{
	 echo "<script>jQuery( document ).ready(  function() {
		var post_id = ". get_the_ID() . ";
		jQuery.ajax({
			url : '" . admin_url( 'admin-ajax.php' ) ." ',
			type : 'post',
			data : {
				action : 'post_arena_add_view',
				post_id : post_id
			},
			success : function( response ) {
			}
		});
	})</script>";
	}
}

add_action( 'wp_ajax_nopriv_post_arena_add_view', 'post_arena_add_view' );
add_action( 'wp_ajax_post_arena_add_view', 'post_arena_add_view' );

function post_arena_add_view() {
	
	$post_id = -1;
	if (is_int($_POST['post_id']))
		$post_id = $_POST['post_id'];
	
	if ($post_id == -1)
		return;
	
	$views = get_post_meta( $post_id, 'post_views', true );
	$views++;
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { 
		update_post_meta( $post_id, 'post_views', $views );
		echo $views;
	}
	$last_view = get_post_meta( $post_id, 'last_view', true );
	$last7days = get_post_meta( $post_id, 'last7days', true );
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {	
		if ($last_view == date("Y/m/d"))
		{
			$total7days = 1;
			if (empty ($last7days))
				$last7days = "1,0,0,0,0,0,0";
			else
			{ 
				$total7days = 0;
				$last7counts = explode (",",$last7days);
				$last7counts[0] ++;
				for ($t=0;$t<count($last7counts);$t++)
					$total7days = $total7days + $last7counts[$t];
				$last7days = implode (',',$last7counts);

			}
			update_post_meta( $post_id, 'last7days', $last7days );
			update_post_meta( $post_id, 'post_view_7days', $total7days );
		} 
		else 
		{
			if ( empty ($last_view) ) 
			{	
				update_post_meta( $post_id, 'last7days', '1,0,0,0,0,0,0' );
			}
			else 
			{
				$date1 = new DateTime($last_view);
				$date2 = new DateTime(date("Y/m/d"));
				$diff = $date2->diff($date1)->format("%a");
				
				if ($diff < 7) 
				{
					$olast7days = explode (',',$last7days);
					$last7days = "1,";
					$total7days = 0;	
					for ($x=1; $x<6; $x++)
					{
						if ( $x < $diff ) {$last7days .= '0,';} else { $last7days.= $olast7days[intval($x-$diff)] .','; $total7days += $olast7days[$x-$diff];}
					}
					if (6 < $diff) { $last7days.='0';} else { $last7days .= $olast7days[6-$diff];$total7days += $olast7days[6-$diff];}
			
					update_post_meta( $post_id, 'post_view_7days', $total7days );
					update_post_meta( $post_id, 'last7days',$last7days );

				}
			}
			update_post_meta( $post_id, 'last_view', date ("Y/m/d") );
		}
	}
	die();
}

?>
