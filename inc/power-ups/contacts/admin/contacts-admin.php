<?php
if(!defined('ABSPATH') || !defined('INBOUNDROCKET_PATH')) die('Security'); 

//=============================================
// WPInboundRocketAdmin Class
//=============================================
class WPContactsAdmin extends WPInboundRocketAdmin {
       
    /**
     * Class constructor
     */
    var $action;

    function __construct ()
    {
        //=============================================
        // Hooks & Filters
        //=============================================
        
        if ( is_admin() )
        {
	        add_action('admin_print_scripts', array(&$this, 'add_inboundrocket_admin_scripts'));
        }
    }

    //=============================================
    // Settings Page
    //=============================================

    /**
     * Creates settings page
     */
    function power_up_setup_callback ()
    {
        WPContactsAdmin::inboundrocket_contacts_page();
    }


    //=============================================
    // Contacts Page
    //=============================================

    /**
     * Shared functionality between contact views 
     */
    function inboundrocket_contacts_page ()
    {
        global  $wp_version;

        $this->action = $this->inboundrocket_current_action();
        if ( $this->action == 'delete' )
        {
            $contact_id = ( isset($_GET['contact']) ? absint(sanitize_text_field($_GET['contact'])) : FALSE );
            $this->delete_lead($contact_id);
        }

        echo '<div id="inboundrocket" class="wrap '. ( $wp_version < 3.8 && !is_plugin_active('mp6/mp6.php')  ? 'pre-mp6' : ''). '">';

            if ( $this->action !== 'view' ) {
                // inboundrocket_track_plugin_activity("Loaded Contact List Page");
                $this->inboundrocket_render_list_page();
            }
            else {
                // inboundrocket_track_plugin_activity("Loaded Contact Detail Page");
                $this->inboundrocket_render_contact_detail( sanitize_text_field($_GET['lead']) );
            }


            $this->inboundrocket_footer();

        echo '</div>';
    }

    /**
     * Creates view a contact's details + timeline history
     *
     * @param   int
     */
    function inboundrocket_render_contact_detail ( $lead_id )
    {
		$css_class = '';
	    $ir_contact = new IR_Contact();
        $ir_contact->set_hashkey_by_id($lead_id);
        $ir_contact->get_contact_history();
        $lead_email = $ir_contact->history->lead->lead_email;
        $lead_firstname = $ir_contact->history->lead->lead_first_name;
        $lead_lastname = $ir_contact->history->lead->lead_last_name;
        // @TODO temporary hack
        preg_match("/\w*[@](\w*)/", $lead_email, $output_array);
        $company_name = $output_array[1];
        $lead_source = inboundrocket_strip_params_from_url($ir_contact->history->lead->lead_source);
        $gravatar_hash = md5( strtolower( trim( $lead_email ) ) );
        
        $lead_ip = $ir_contact->history->lead->lead_ip;
        
        if ($lead_ip == '127.0.0.1') {
        	// workaround for local development showing Inbound Rocket HQ ^_^
        	$lead_city = 'Fort Myers';
        	$lead_state = 'FL';
        	$lead_country = 'US';
        	$lead_loc = '26.555200,-81.896340';
        } else {
        	$geoip = json_decode(file_get_contents("http://ipinfo.io/{$lead_ip}"));
			$lead_city = isset($geoip->city) ? $geoip->city : '';
			$lead_state = isset($geoip->region) ? $geoip->region : '';
        	$lead_country = isset($geoip->country) ? $geoip->country : '';
        	$lead_loc = isset($geoip->loc) ? $geoip->loc : '';
        	$lead_telcom = isset($geoip->org) ? $geoip->org : '';
        	$lead_hostname = isset($geoip->hostname) ? $geoip->hostname : '';
        	$lead_zipcode = isset($geoip->postal) ? $geoip->postal : '';
        }
        
        if ( isset($_POST['edit_lists']) )
        {
            $updated_tags = array();

            foreach ( $_POST as $name => $value )
            {
                if ( strstr($name, 'tag_slug_') ) 
                {
                    array_push($updated_tags, $value);
                }
            }

            $ir_contact->update_contact_tags($lead_id, $updated_tags);
            $ir_contact->history->tags = $ir_contact->get_contact_tags($ir_contact->hashkey);
            echo '<script type="text/javascript"> location.reload(); </script>';
        }
?>
        <div class="ir-content">
	        <div class="ir-frame">
		        <div class="header">
					<nav role="navigation" class="header-nav drawer-nav nav-horizontal">
						<ul class="main-nav">
							<li class="inboundrocket-logo"><a href="<?=admin_url('admin.php?page=inboundrocket_stats');?>" title="<?php _e( 'Inbound Rocket Stats', 'inboundrocket' );?>"><span>Inbound Rocket</span></a></li>
						</ul>
					</nav>
				</div><!-- header -->
				<div class="clouds-sm"></div>
				<div class="wrapper">
<?php
        if ( isset($_GET['stats_dashboard']) )
            echo '<a href="' . admin_url('admin.php?page=inboundrocket_stats') . '">&larr; '. __( 'Stats Dashboard', 'inboundrocket' ) .'</a>';
        else
        {
            if ( isset($_GET['redirect_to']) )
            {
                if ( strstr($_GET['redirect_to'], 'contact_type') )
                {
                    $url_parts = parse_url(esc_url($_GET['redirect_to']));
                    parse_str($url_parts['query'], $url_vars);

                    if ( isset($url_vars['contact_type']) && $url_vars['contact_type'] )
                        echo '<a href="' . esc_url($_GET['redirect_to']) . '">&larr; ' . __( 'All', 'inboundrocket' ).''. ucwords($url_vars['contact_type']) . '</a>';
                    else
                        echo '<a href="' . esc_url($_GET['redirect_to']) . '">&larr; ' . __( 'All Contacts', 'inboundrocket' ) .'</a>';
                }
                else
                    echo '<a href="' . esc_url($_GET['redirect_to']) . '">&larr; ' . __( 'All Contacts', 'inboundrocket' ) .'</a>';
                
            }
            else
                echo '<a href="' . admin_url('/admin.php?page=inboundrocket_contacts') . '">&larr; ' . __( 'All Contacts', 'inboundrocket' ) .'</a>';
        }
        
        if(isset($lead_firstname) && isset($lead_lastname)){
			$lead_full_name = esc_html($lead_firstname)." ".esc_html($lead_lastname);
		} else {
			$lead_full_name = '';
		}
		
		echo '<h2 class="' . $css_class . '">' . __( 'Contact info of:', 'inboundrocket' ) .' ';
        echo !empty($lead_full_name) ? $lead_full_name : !empty($lead_firstname) ? esc_html($lead_firstname)." " : !empty($lead_lastname) ? esc_html($lead_lastname)." " : 'Unknown';
        if(!empty($lead_email)) {
			echo esc_html($lead_email);
		}
        echo '</h2>';
        echo '<div class="contact-header-wrap">';

            echo '<div class="contact-header-info">';
                echo '<div class="contact-lists">';
                 if(isset($ir_contact->history->lead_lists)):
                    foreach( $ir_contact->history->lead_lists as $list ) {
                        if ($list->tag_set)
                            echo '<a class="contact-list" href="' . admin_url('/admin.php?page=inboundrocket_contacts&contact_type=' . $list->tag_slug) . '"><span class="icon-profile"></span>' . $list->tag_text . '</a>';
                    }
                 endif;
                    ?>

                    <?php add_thickbox(); ?>
                    <div id="edit-contact-lead-lists" style="display:none;">
                        <h2>Edit Lists - <?php echo $ir_contact->history->lead->lead_email; ?></h2>
                        <form id="edit_lists" action="" target="_parent" method="POST">

                            <?php
                            if(!empty($ir_contact->history->lead_lists)):
                            foreach( $ir_contact->history->lead_lists as $list ) 
                            {
                                echo '<p>';
                                    echo '<label for="tag_slug_' . $list->tag_slug . '">';
                                    echo '<input name="tag_slug_' . $list->tag_slug . '" type="checkbox" id="tag_slug_' . $list->tag_slug . '" value="' . $list->tag_id . '" ' . ( $list->tag_set ? ' checked' : '' ) . '>' . $list->tag_text . '</label>';
                                echo '</p>';
                            }
                            endif;

                            ?>

                            <input type="hidden" name="edit_lists" value="1"/>
                            <p class="submit">
                                <input type="submit" name="submit" id="submit" class="button button-primary" value=<?php _e( 'Save To List(s)', 'inboundrocket' ); ?>"">
                            </p>
                        </form>
                    </div>

                    <a class="thickbox contact-edit-lists" href="#TB_inline?width=300&height=450&inlineId=edit-contact-lead-lists"><?php _e( 'edit lead lists', 'inboundrocket' ); ?></a>

                    <?php

                echo '</div>';
            echo '</div>';
        echo '</div>';
		
		echo '<div id="col-container">';
		echo	'<div id="col-right">';
		echo		'<div class="col-header">Lead Activity</div>';
		echo		'<div class="col-wrap contact-history">';
		echo			'<ul class="sessions">';
                    $sessions = $ir_contact->history->sessions;
                    
                    foreach ( $sessions as &$session )
                    {
                        $first_event = end($session['events']);
                        $first_event_date = $first_event['event_date'];
                        $session_date = date('F j, Y, g:ia', strtotime($first_event['event_date']));
                        $session_start_time = date('g:ia', strtotime($first_event['event_date']));

                        $last_event = array_values($session['events']);
                        $session_end_time = date('g:ia', strtotime($last_event[0]['event_date']));

                        echo '<li class="session">';
                        echo '<h3 class="session-date">' . $session_date . ( $session_start_time != $session_end_time ? ' - ' . $session_end_time : '' ) . '</h3>';

                        echo '<ul class="events">';

                        $events = $session['events'];
                                                                        
                        foreach ( $events as &$event )
                        {
                            if ( $event['event_type'] == 'pageview' )
                            {
                                $pageview = $event['activities'][0];
                                
                                echo '<li class="event pageview">';
                                if(!empty($pageview['event_date'])) echo '<div class="event-time">' . date('g:ia', strtotime($pageview['event_date'])) . '</div>';
                                echo '<div class="event-content">';
                                if(!empty($pageview['pageview_title'])) echo '<p class="event-title">' . $pageview['pageview_title'] . '</p>';
                                if(!empty($pageview['pageview_url'])) echo '<a class="event-detail pageview-url" target="_blank" href="' . $pageview['pageview_url'] . '">' . inboundrocket_strip_params_from_url($pageview['pageview_url']) . '</a>';
                                    echo '</div>';
                                echo '</li>';

                                if ( isset($pageview['event_date']) && $pageview['event_date'] == $first_event['event_date'] )
                                {
                                    echo '<li class="event source">';
                                        echo '<div class="event-time">' . date('g:ia', strtotime($pageview['event_date'])) . '</div>';
                                        echo '<div class="event-content">';
                                            echo '<p class="event-title">' . __( 'Traffic Source', 'inboundrocket' ) .': ' . ( $pageview['pageview_source'] ? '<a href="' . $pageview['pageview_source'] . '">' . inboundrocket_strip_params_from_url($pageview['pageview_source']) : 'Direct' ) . '</a></p>';
                                            $url_parts = parse_url($pageview['pageview_source']);
                                            if ( isset($url_parts['query']) )
                                            {
                                                if ( $url_parts['query'] )
                                                {
                                                    parse_str($url_parts['query'], $url_vars);
                                                    if ( count($url_vars) )
                                                    {
                                                        echo '<ul class="event-detail fields">';
                                                            foreach ( $url_vars as $key => $value )
                                                            {
                                                                if ( ! $value )
                                                                    continue;
                                                                
                                                                echo '<li class="field">';
                                                                    echo '<label class="field-label">' . $key . ':</label>';
                                                                    echo '<p class="field-value">' . nl2br($value, true) . '</p>';
                                                                echo '</li>';
                                                            }
                                                        echo '</ul>';
                                                    }
                                                }
                                            }
                                            
                                        echo '</div>';
                                    echo '</li>';
                                }
                            }
                            else if ( $event['event_type'] == 'form' )
                            {
	                            //die(print_r($event));
                                $submission = $event['activities'][0];
                                $form_fields = isset($submission['form_fields']) ? json_decode($submission['form_fields']) : '';
                                $num_form_fieds = count($form_fields);
                                if(isset($tag->tag_slug)){
                                	$tag_text = '<a class="contact-list" href="' . wp_nonce_url(admin_url('admin.php?page=inboundrocket_contacts&contact_type=' . $tag->tag_slug)) . '">' . $tag->tag_text . '</a>';
                                } else {
	                                $tag_text = '';
                                }

                                echo '<li class="event form-submission">';
                                    echo '<div class="event-time">' . date('g:ia', strtotime($submission['event_date'])) . '</div>';
                                    echo '<div class="event-content">';
                                        echo '<p class="event-title">';
                                            echo '' . __( 'Filled out Form', 'inboundrocket' ) .' (' . $event['form_name'] . ') ' . __( 'on page', 'inboundrocket' ) .' <a href="' . $submission['form_page_url'] . '">' . $submission['form_page_title']  . '</a>';
                                            if ( isset($event['form_tags'][0]['tag_slug']) )
                                            {
                                                echo ' ' . __( 'and tagged as', 'inboundrocket' ) .' ';
                                                for ( $i = 0; $i < count($event['form_tags']); $i++ )
                                                    echo '<a href="' . wp_nonce_url(admin_url('admin.php?page=inboundrocket_contacts&contact_type=' . $event['form_tags'][$i]['tag_slug'])) . '">' . $event['form_tags'][$i]['tag_text'] . '</a> ';
                                            }
                                        echo '</p>';
                                        echo '<ul class="event-detail fields">';
                                        if ( isset($form_fields) && is_array($form_fields) )
                                        {
                                            foreach ( $form_fields as $num => $field )
                                            {
                                                echo '<li class="field">';
                                                    echo '<label class="field-label">' . esc_html($field->label) . ':</label>';
                                                    echo '<p class="field-value">' . esc_html($field->value) . '</p>';
                                                echo '</li>';
                                            }
                                        }
                                        echo '</ul>';
                                    echo '</div>';
                                echo '</li>';
                            }
                            else if ($event['event_type'] == 'text-share')
                            {
	                            $share = $event['activities'][0];
	                            
	                            $title = get_the_title($share['post_id']);
	                            $url = get_permalink($share['post_id']);
	                            
	                            if ($share['share_type']=='ss-twitter-text') { $type = __( 'Twitter (using Selection Sharer power-up)', 'inboundrocket' ); }
	                            elseif ($share['share_type']=='ss-facebook-text') { $type = __( 'Facebook (using Selection Sharer power-up)', 'inboundrocket' ); }
	                            elseif ($share['share_type']=='ss-linkedin-text') { $type = __( 'LinkedIn (using Selection Sharer power-up)', 'inboundrocket' ); }
	                            elseif ($share['share_type']=='ss-email-text') { $type = __( 'Email (using Selection Sharer power-up)', 'inboundrocket' ); }
	                            elseif ($share['share_type']=='click-to-tweet') { $type = __( 'Twitter (using Click-To-Tweet power-up)', 'inboundrocket' ); }
	                            
	                           	echo '<li class="event text-share">';
									echo '<div class="event-time">'.date('g:ia', strtotime($share['event_date'])).'</div>';
									echo '<div class="event-content">';
									echo	'<p class="event-title">' . __( 'Shared a text snippet from', 'inboundrocket' ) .' <a href="'.$url.'">'.$title.'</a></p>';
										echo '<ul class="event-detail fields">';
											echo '<li class="field">';
												echo '<label class="field-label">' . __( 'Text shared', 'inboundrocket' ) .':</label>';
												echo '<p class="field-value">'.$share['share'].'</p>';
											echo '</li>';
											echo '<li class="field">';
												echo '<label class="field-label">' . __( 'Shared to', 'inboundrocket' ) .':</label>';
												echo '<p class="field-value">'.$type.'</p>';
											echo '</li>';
										echo '</ul>';
									echo '</div>';
								echo '</li>';
                            }
                            else if ($event['event_type'] == 'image-share')
                            {
	                           	$share = $event['activities'][0];
	                            
	                            $title = get_the_title($share['post_id']);
	                            $url = get_permalink($share['post_id']);
	                            
	                            if ($share['share_type']=='is-twitter-image') { $type = __( 'Twitter (using Image Sharer power-up)', 'inboundrocket' ); }
	                            elseif ($share['share_type']=='is-facebook-image') { $type = __( 'Facebook (using Image Sharer power-up)', 'inboundrocket' ); }
	                            elseif ($share['share_type']=='is-pinterest-image') { $type = __( 'Pinterest (using Image Sharer power-up)', 'inboundrocket' ); }
	                            
	                            echo '<li class="event image-share">';
									echo '<div class="event-time">'.date('g:ia', strtotime($share['share_date'])).'</div>';
									echo '<div class="event-content">';
										echo '<p class="event-title">' . __( 'Shared an image from', 'inboundrocket' ) .' <a href="'.$url.'">'.$title.'</a></p>';
										echo '<ul class="event-detail fields">';
											echo '<li class="field">';
												echo '<label class="field-label">' . __( 'Image shared', 'inboundrocket' ) .'</label>';
												echo '<p class="field-value">VISUAL</p>';
											echo '</li>';
											echo '<li class="field">';
												echo '<label class="field-label">' . __( 'Shared to', 'inboundrocket' ) .':</label>';
												echo '<p class="field-value">'.$type.'</p>';
											echo '</li>';
										echo '</ul>';
									echo '</div>';
								echo '</li>';
                            }

                        }
                        echo '</ul>';
                        echo '</li>';
                    }
                    echo '</ul>';
                echo '</div>';
            echo '</div>';		
		
			echo '<div id="col-left" class="metabox-holder">';
				echo '<div class="col-header">Lead Profile</div>';
					echo '<div class="inboundrocket-meta-section">';
						echo '<table class="inboundrocket-meta-table">';
							echo '<tbody>';
								echo '<tr>';
									echo '<td>'; 
										echo '<img class="contact-header-avatar inboundrocket-dynamic-avatar_'. esc_attr($lead_id) .'" src="http://www.gravatar.com/avatar/' . $gravatar_hash .'"/>';
									echo '</td>';
									echo '<td>';
										echo '<table>';
											echo '<tr>';
												echo '<td><strong>' . __( 'Name', 'inboundrocket' ) .':</strong></td>';
												echo '<td style="padding-left:10px;">';
												echo !empty($lead_full_name) ? $lead_full_name : !empty($lead_firstname) ? esc_html($lead_firstname) : !empty($lead_lastname) ? esc_html($lead_lastname) : __( 'No name provided', 'inboundrocket' );
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td><strong>' . __( 'Email', 'inboundrocket' ) .':</strong></td>';
												echo '<td style="padding-left:10px;"><a href="mailto:'. esc_html($lead_email) .'" target="_blank">'.esc_html($lead_email).'</a></td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td><strong>' . __( 'Original source', 'inboundrocket' ) .':</strong></td>';
												echo '<td style="padding-left:10px;">' . ( $ir_contact->history->lead->lead_source ? '<a href="' . esc_url($ir_contact->history->lead->lead_source) . '">' . esc_html($lead_source) . '</a>' : 'Direct' ) . '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td><strong>' . __( 'First visit', 'inboundrocket' ) .':</strong></td>';
												echo '<td style="padding-left:10px;">' . esc_html(self::date_format_contact_stat($ir_contact->history->lead->first_visit)) . '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td><strong>' . __( 'Pageviews', 'inboundrocket' ) .':</strong></td>';
												echo '<td style="padding-left:10px;">' . esc_html($ir_contact->history->lead->total_pageviews) . '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td><strong>' . __( 'Form submissions', 'inboundrocket' ) .':</strong></d>';
												echo '<td style="padding-left:10px;">' . esc_html($ir_contact->history->lead->total_submissions) . '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td><strong>' . __( 'Total shares', 'inboundrocket' ) .':</strong></td>';
												echo '<td style="padding-left:10px;">' . esc_html($ir_contact->history->lead->total_shares) . '</td>';
											echo '</tr>';
										echo '</table>';
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									// @TODO create execption for localhost and if no city provided etc.
									echo '<td><strong>' . __( 'Location', 'inboundrocket' ) .':</strong><br />' . esc_html($lead_city) . ', ' .esc_html($lead_state). ' ' . esc_html($lead_country) . '</td>';
									echo '<td style="text-align:right;"><a target="_blank" href="https://www.google.com/maps/place/'.esc_html($lead_city).',+'.esc_html($lead_state).'">' . __( 'View Larger Map', 'inboundrocket' ) .'</a></td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td colspan="2"><img class="contact-map" src="https://maps.googleapis.com/maps/api/staticmap?center=' . esc_attr($lead_loc) . '&zoom=13&size=660x175&maptype=roadmap
&markers=color:red%7C' . esc_attr($lead_loc) . '" /></td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
					echo '</div>';

				echo '<div class="inboundrocket-meta-section">';
					echo '<h4 class="inboundrocket-meta-header inboundrocket-premium-tag">' . __( 'Personal Info', 'inboundrocket' ) .'</h4>' . __( 'More background information about the contact, coming soon for premium users.', 'inboundrocket' );
					echo '<table class="inboundrocket-meta-table">';
						echo '<tbody>';
							echo '<tr></tr>';
						echo '</tbody>';
					echo '</table>';
				echo '</div>';
				
				echo '<div class="inboundrocket-meta-section">';
					echo '<h4 class="inboundrocket-meta-header inboundrocket-premium-tag">' . __( 'Company Info', 'inboundrocket' ) .'</h4>';
					// echo '<p><b>About '. esc_html($company_name) .'</b></p>'; 
					?>
					<table class="inboundrocket-meta-table">
						<tbody>
							<tr><?php _e('In our upcoming release for premium users we will display all relevant company info.', 'inboundrocket'); ?></tr>
						</tbody>
					</table>
					<!--
						<table class="inboundrocket-meta-table">
						<tbody>
							<tr>
								<th>Website</th>
								<td><a href="https://www.lipsum.com/" target="_blank">Lorem Ipsum inc.</a></td>
							</tr>
							<tr>
								<th>LinkedIn</th>
								<td><a href="https://www.linkedin.com/company/loremipsum" target="_blank">Lorem Ipsum</a></td>
							</tr>
							<tr>
								<th>Facebook</th>
								<td><a href="https://www.facebook.com/loremipsum" target="_blank">Lorem Ipsum</a></td>
							</tr>
							<tr>
								<th>Twitter</th>
								<td><a href="https://twitter.com/loremipsum" target="_blank">@LoremIpsum</a></td>
							</tr>
						</tbody>
					</table>
					-->
				</div>
				
				<div class="inboundrocket-meta-section">
					<h4 class="inboundrocket-meta-header inboundrocket-premium-tag"><?php _e('Notes', 'inboundrocket');?></h4>
					<table class="inboundrocket-meta-table">
						<tbody>
							<tr></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>        

<?php  
	}
	
	    /**
     * Creates list table for Contacts page
     *
     */
    function inboundrocket_render_list_page ()
    {
        global $wp_version;
        
        //Create an instance of our package class...
		$inboundrocketListTable = new IR_List_table();

        // Process any bulk actions before the contacts are grabbed from the database
        $inboundrocketListTable->process_bulk_action();
        
        //Fetch, prepare, sort, and filter our data...
        $inboundrocketListTable->data = $inboundrocketListTable->get_contacts();
        $inboundrocketListTable->prepare_items();
        
        ?>
        <div class="inboundrocket-contacts">

            <form id="inboundrocket-contacts-search" class="inboundrocket-contacts__search" method="GET">
                <span class="table_search">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
                    <input type="search" id="inboundrocket-contact-search-input" name="s" value="<?php echo esc_attr($inboundrocketListTable->print_submission_val('s')); ?>" />
                    <input type="submit" name="" id="inboundrocket-search-submit" class="button" value="<?php _e('Search all contacts', 'inboundrocket');?>">
                </span>
            </form>
        
        <?php
        $this->inboundrocket_header('Inbound Rocket Contacts', 'inboundrocket-contacts__header');
    	?>
    
        	<div class="inboundrocket-contacts__nav">
				<?php $inboundrocketListTable->views(); ?>
    		</div>
    		
    		<div class="inboundrocket-contacts__content">

                <div class="inboundrocket-contacts__filter">
                    <?php $inboundrocketListTable->filters(); ?>
                </div>

                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                <form id="inboundrocket-contacts" method="GET">
                    
                    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />

                    <div class="inboundrocket-contacts__table">
                        <!-- Now we can render the completed list table -->
                        <?php $inboundrocketListTable->display() ?>
                    </div>

                    <input type="hidden" name="contact_type" value="<?php echo ( isset($_GET['contact_type']) ? esc_attr($_GET['contact_type']) : '' ); ?>"/>
                   
                    <?php if ( isset($_GET['filter_content']) ) : ?>
                        <input type="hidden" name="filter_content" value="<?php echo ( isset($_GET['filter_content']) ? esc_attr(stripslashes($_GET['filter_content'])) : '' ); ?>"/>
                    <?php endif; ?>

                    <?php if ( isset($_GET['filter_action']) ) : ?>
                        <input type="hidden" name="filter_action" value="<?php echo ( isset($_GET['filter_action']) ? esc_attr($_GET['filter_action']) : '' ); ?>"/>
                    <?php endif; ?>

                </form>
                
            </div>
            
            <?php add_thickbox(); ?>
            <div id="bulk-edit-lists" style="display:none;">
                <h2><?php _e('Select a lead list to add to', 'inboundrocket');?> <span class="selected-contacts-count"></span> <?php echo strtolower($inboundrocketListTable->view_label); ?></h2>
                <form id="bulk-edit-lists-form" action="" method="POST">
                    <?php
                    if ( isset($inboundrocketListTable->tags) ) 
                    {
                        echo '<select name="bulk_selected_tag">';
                            foreach( $inboundrocketListTable->tags as $tag )
                                echo '<option value="' . esc_attr($tag->tag_slug) . '">' . esc_html($tag->tag_text) . '</option>';
                        echo '</select>';
                    }
                    ?>

                    <input type="hidden" name="bulk_edit_lists" value="1" />
                    <input type="hidden" id="bulk-edit-tag-action" name="bulk_edit_list_action" value="" />
                    <input type="hidden" class="inboundrocket-selected-contacts"  name="inboundrocket_selected_contacts" value="" />

                    <p class="submit">
                        <input id="bulk-edit-button" type="submit" name="submit" id="submit" class="button button-primary" value="?php _e('Add To Lead List', 'inboundrocket');?>" />
                    </p>
                </form>
            </div>
            
            <?php
                $export_button_labels = $inboundrocketListTable->view_label;

                if ( isset($_GET['filter_action']) || isset($_GET['filter_content']) )
                    $export_button_labels = __( 'Filtered Contacts', 'inboundrocket' );
            ?>

            <form id="export-form" class="inboundrocket-contacts__export-form" name="export-form" method="POST">
                <input type="submit" value="<?php esc_attr_e(__('Export All ', 'inboundrocket') . $export_button_labels ); ?>" name="export-all" id="inboundrocket-export-leads" class="button" <?php echo ( ! count($inboundrocketListTable->data) ? 'disabled' : '' ); ?> />
                <input type="submit" value="<?php esc_attr_e(__('Export Selected ', 'inboundrocket') . $export_button_labels ); ?>" name="export-selected" id="inboundrocket-export-selected-leads" class="button" disabled />
                <input type="hidden" class="inboundrocket-selected-contacts"  name="inboundrocket_selected_contacts" value="" />
            </form>

        </div>
        <div style="clear:both"></div>
    <?php
    }
    
    /**
     * Deletes all rows from ir_leads, ir_pageviews and ir_submissions for a given lead
     *
     * @param   int
     * @return  bool
     */
    private function delete_lead ( $lead_id )
    {
        global $wpdb;

		$q = $wpdb->prepare("SELECT hashkey FROM {$wpdb->ir_leads} WHERE lead_id = %d", $lead_id);
        $lead_hash = $wpdb->get_var($q);

        $q = $wpdb->prepare("UPDATE {$wpdb->ir_pageviews} SET pageview_deleted = 1 WHERE lead_hashkey = %s AND pageview_deleted = 0", $lead_hash);
        $delete_pageviews = $wpdb->query($q);

        $q = $wpdb->prepare("UPDATE {$wpdb->ir_submissions} SET form_deleted = 1  WHERE lead_hashkey = %s AND form_deleted = 0", $lead_hash);
        $delete_submissions = $wpdb->query($q);

        $q = $wpdb->prepare("UPDATE {$wpdb->ir_leads} SET lead_deleted = 1 WHERE lead_id = %d AND lead_deleted = 0", $lead_id);
        $delete_lead = $wpdb->query($q);

        return $delete_lead;
	}
	
	//=============================================
    // Admin Styles & Scripts
    //=============================================

    /**
     * Adds admin javascript
     */
    function add_inboundrocket_admin_scripts ()
    {
        global $pagenow;

        if ( ($pagenow == 'admin.php' && isset($_GET['page']) && strstr($_GET['page'], 'inboundrocket')) ) 
        {
           if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_register_script('inboundrocket-admin-js', INBOUNDROCKET_PATH . '/assets/js/inboundrocket-admin.js', array ( 'jquery' ), FALSE, TRUE);}
           else { wp_register_script('inboundrocket-admin-js', INBOUNDROCKET_PATH . '/assets/js/inboundrocket-admin.min.js', array ( 'jquery' ), FALSE, TRUE);}
           wp_enqueue_script('inboundrocket-admin-js');
           wp_localize_script('inboundrocket-admin-js', 'ir_admin_ajax', array('ajax_url' => admin_url('/admin-ajax.php'),'ir_nonce'=>wp_create_nonce('ir-nonce-verify')));
        }
    }

    /**
     * Formats any timestamp to format like Feb 4 8:43pm
     *
     * @param   string
     * @return  string
     */
    function date_format_contact_stat ( $timestamp )
    {
        return date('M j, Y g:ia', strtotime($timestamp));
    }
}

/** Export functionality for the contacts list */
if ( isset($_POST['export-all']) || isset($_POST['export-selected']) )
{
    global $wpdb;
    inboundrocket_set_wpdb_tables();
    inboundrocket_set_mysql_timezone_offset();

    $sitename = sanitize_key(get_bloginfo('name'));

    if ( ! empty($sitename) )
        $sitename .= '.';

    $filename = $sitename . '.contacts.' . date('Y-m-d-H-i-s') . '.csv';

    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Type: text/csv; charset=' . get_option('blog_charset'), TRUE);

    $column_headers = array(
        'Email', 'First Name', 'Last Name', 'Original source', 'Visits', 'Page views', 'Forms',  'Shares', 'Last visit', 'Created on'
    );

    $fields = array(
        'lead_email', 'lead_first_name', 'lead_last_name', 'lead_source', 'visits', 'lead_pageviews', 'lead_form_submissions', 'lead_shares', 'last_visit', 'lead_date'
    );

    $headers = array();
    foreach ( $column_headers as $key => $field )
    {
            $headers[] = '"' . $field . '"';
    }
    echo implode(',', $headers) . "\n";

    $mysql_search_filter        = '';
    $mysql_contact_type_filter  = '';
    $mysql_action_filter        = '';
    $filter_action_set          = FALSE;

    // search filter
    if ( isset($_GET['s']) )
    {
        $search_query = $_GET['s'];
        $mysql_search_filter = $wpdb->prepare(" AND ( l.lead_email LIKE '%%%s%%' OR l.lead_source LIKE '%%%s%%' ) ", $wpdb->esc_like($search_query), $wpdb->esc_like($search_query));
    }

    // @TODO - need to modify the filters to pull down the form ID types
    
    $filtered_contacts = array();

    if ( isset($_GET['filter_action']) && $_GET['filter_action'] == 'visited' )
    {
        if ( isset($_GET['filter_content']) && $_GET['filter_content'] != 'any page' )
        {
            $q = $wpdb->prepare("SELECT lead_hashkey FROM $wpdb->ir_pageviews WHERE pageview_title LIKE '%%%s%%' GROUP BY lead_hashkey",  htmlspecialchars(urldecode($_GET['filter_content'])));
            $filtered_contacts = inboundrocket_merge_filtered_contacts($wpdb->get_results($q, 'ARRAY_A'), $filtered_contacts);
            $filter_action_set = TRUE;
        }
    }
    
    // filter for a form submitted on a specific page
    if ( isset($_GET['filter_action']) && $_GET['filter_action'] == 'submitted' )
    {
        $filter_form = '';
        if ( isset($_GET['filter_form']) && $_GET['filter_form'] && $_GET['filter_form'] != 'any form' )
        {
            $filter_form = str_replace(array('#', '.'), '', htmlspecialchars(urldecode($_GET['filter_form'])));
            $filter_form_query = $wpdb->prepare(" AND ( form_selector_id LIKE '%%%s%%' OR form_selector_classes LIKE '%%%s%%' )", $filter_form, $filter_form);
        }

        $q = $wpdb->prepare("SELECT lead_hashkey FROM $wpdb->ir_submissions WHERE form_page_title LIKE '%%%s%%' ", ( $_GET['filter_content'] != 'any page' ? htmlspecialchars(urldecode($_GET['filter_content'])): '' ));
        $q .= ( $filter_form_query ? $filter_form_query : '' );
        $q .= " GROUP BY lead_hashkey";
        $filtered_contacts = inboundrocket_merge_filtered_contacts($wpdb->get_results($q, 'ARRAY_A'), $filtered_contacts);
        $filter_action_set = TRUE;
    }        

    $filtered_hashkeys = inboundrocket_explode_filtered_contacts($filtered_contacts);

    $mysql_action_filter = '';
    if ( $filter_action_set ) // If a filter action is set and there are no contacts, do a blank
        $mysql_action_filter = " AND l.hashkey IN ( " . ( $filtered_hashkeys ? $filtered_hashkeys : "''" ) . " ) ";
    else
        $mysql_action_filter = ( $filtered_hashkeys ? " AND l.hashkey IN ( " . $filtered_hashkeys . " ) " : '' ); // If a filter action isn't set, use the filtered hashkeys if they exist, else, don't include the statement

    // There's a filter and leads are in it
    if ( ( isset($_GET['contact_type']) && $num_contacts ) || ! isset($_GET['contact_type']) )
    {
	    $q =  $wpdb->prepare("
            SELECT 
                l.lead_id AS lead_id, 
                LOWER(DATE_SUB(l.lead_date, INTERVAL %d HOUR)) AS lead_date, l.lead_ip, l.lead_source, l.lead_email, l.hashkey, l.lead_first_name, l.lead_last_name,
                COUNT(DISTINCT s.form_id) AS lead_form_submissions,
                COUNT(DISTINCT sh.share_id) AS lead_shares,
                COUNT(DISTINCT p.pageview_id) AS lead_pageviews,
                LOWER(DATE_SUB(MAX(p.pageview_date), INTERVAL %d HOUR)) AS last_visit,
                ( SELECT COUNT(DISTINCT pageview_id) FROM $wpdb->ir_pageviews WHERE lead_hashkey = l.hashkey AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS visits,
                ( SELECT MIN(pageview_source) AS pageview_source FROM $wpdb->ir_pageviews WHERE lead_hashkey = l.hashkey AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS pageview_source 
            FROM 
                $wpdb->ir_leads l
            LEFT JOIN $wpdb->ir_submissions s ON l.hashkey = s.lead_hashkey
            LEFT JOIN $wpdb->ir_pageviews p ON l.hashkey = p.lead_hashkey
            LEFT JOIN $wpdb->ir_shares sh ON l.hashkey = p.lead_hashkey
            WHERE l.lead_email != '' AND l.lead_deleted = 0 AND l.hashkey != '' " .
            ( isset ($_POST['export-selected']) ? " AND l.lead_id IN ( " . str_replace(" ",',',$_POST['inboundrocket_selected_contacts']) . " ) " : "" ), $wpdb->db_hour_offset, $wpdb->db_hour_offset);
            
        $q .= $mysql_contact_type_filter;
        $q .= ( $mysql_search_filter ? $mysql_search_filter : "" );
        $q .= ( $mysql_action_filter ? $mysql_action_filter : "" );
        $q .=  " GROUP BY l.hashkey";
        $leads = $wpdb->get_results($q);
    }
    else
    {
        $leads = array();
    }

    foreach ( $leads as $contacts )
    {
        $data = array();
        foreach ( $fields as $field )
        {
            $value = ( isset($contacts->{$field}) ? $contacts->{$field} : '' );
            $value = ( is_array($value) ? serialize($value) : $value );
            $data[] = '"' . str_replace('"', '""', $value) . '"';
        }
        echo implode(',', $data) . "\n";
    }

    exit;
}

?>