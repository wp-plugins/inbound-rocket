<?php
if ( !defined('ABSPATH') ) exit;
if ( !defined('INBOUNDROCKET_PLUGIN_DIR') ) die('Security');

//=============================================
// Include Needed Files
//=============================================
include_once(INBOUNDROCKET_PLUGIN_DIR . '/admin/inc/Snowplow/RefererParser/Parser.php');
include_once(INBOUNDROCKET_PLUGIN_DIR . '/admin/inc/Snowplow/RefererParser/Referer.php');
include_once(INBOUNDROCKET_PLUGIN_DIR . '/admin/inc/Snowplow/RefererParser/Medium.php');

//=============================================
// WPStatsDashboard Class
//=============================================
class IR_StatsDashboard {

    /**
     * Variables
     */
    var $total_visits = array();
    var $total_visits_last_30_days = 0;
    var $avg_visits_last_30_days = 0;
    var $total_contacts     = 0;
    var $total_new_contacts     = 0;
    var $best_day_ever      = 0;
    var $avg_contacts_last_90_days  = 0;
    var $total_contacts_last_30_days = 0;
    var $total_contacts_last_90_days = 0;
    var $total_returning_contacts = 0;
    var $max_source = 0;
    
    /**
     * Arrays
     */
    var $returning_contacts;
    var $new_contacts;
    var $most_popular_pages;
    var $new_shares;
    var $most_popular_referrer;
    
    /**
     * Sources counts
     */
	var $organic_count 	= 0;
	var $referral_count = 0;
	var $social_count 	= 0;
	var $email_count 	= 0;
	var $paid_count 	= 0;
	var $direct_count 	= 0;

	var $x_axis_labels 	= '';
	var $column_colors 	= '';
	var $column_data 	= '';
	var $average_data 	= '';
	var $visits_data	= '';
	var $weekend_column_data = '';

	var $parser;

	function __construct ()
	{
		
		$this->parser = new Parser();
		
		$this->most_popular_referrer = $this->get_popular_referrer();
		
		$this->new_shares = $this->get_shares();
		$this->most_popular_pages = $this->get_popular_pages();
		
		$this->returning_contacts = $this->get_returning_contacts();
		$this->total_returning_contacts = count($this->returning_contacts);

		$this->new_contacts = $this->get_new_contacts();
		$this->total_new_contacts = count($this->new_contacts);
		
		$this->get_data_last_30_days_graph();
		$this->get_sources();

	}
	
	function get_data_last_30_days_graph ()
	{
		global $wpdb;
				
		$q = "SELECT 
                COUNT(DISTINCT lead_hashkey) AS total_visits
            FROM 
                {$wpdb->ir_pageviews}
            WHERE
                pageview_deleted = 0 AND lead_hashkey != '';";
		
		$this->total_visits = $wpdb->get_var($q);
		
		$q = "SELECT 
                COUNT(DISTINCT hashkey) AS total_contacts
            FROM 
                {$wpdb->ir_leads}
            WHERE
                lead_email != '' AND lead_deleted = 0 AND hashkey != '';";

        $this->total_contacts = $wpdb->get_var($q);

        $q = "SELECT DATE(lead_date) as lead_date, COUNT(DISTINCT hashkey) contacts FROM {$wpdb->ir_leads} WHERE lead_email != '' AND hashkey != '' AND lead_deleted = 0 GROUP BY DATE(lead_date)";
		$contacts = $wpdb->get_results($q);
		
		$q = "SELECT DATE(pageview_date) as pageview_date, COUNT(DISTINCT lead_hashkey) pageviews FROM {$wpdb->ir_pageviews} WHERE lead_hashkey != '' AND pageview_deleted = 0 GROUP BY DATE(pageview_date)";
		$visits = $wpdb->get_results($q);

		for ( $i = count($contacts)-1; $i >= 0; $i-- )
		{
			$this->best_day_ever = ( $contacts[$i]->contacts && $contacts[$i]->contacts > $this->best_day_ever ? $contacts[$i]->contacts : $this->best_day_ever);
		}

        for ( $i = 30; $i >= 0; $i-- )
        {
            $array_key = inboundrocket_search_object_by_value($contacts, date('Y-m-d', strtotime('-'. $i .' days')), 'lead_date');
            $this->total_contacts_last_30_days += ( $array_key ? $contacts[$array_key]->contacts : 0);
            
            $array_key = inboundrocket_search_object_by_value($visits, date('Y-m-d', strtotime('-'. $i .' days')), 'pageview_date');
            $this->total_visits_last_30_days += ( $array_key ? $visits[$array_key]->pageviews : 0);
        }
        
        for ( $i = 90; $i >= 0; $i-- )
        {
            $array_key = inboundrocket_search_object_by_value($contacts, date('Y-m-d', strtotime('-'. $i .' days')), 'lead_date');
            $this->total_contacts_last_90_days += ( $array_key ? $contacts[$array_key]->contacts : 0);
        }
		
		$this->avg_contacts_last_90_days = intval(floor($this->total_contacts_last_90_days/90));
        $this->avg_contacts_last_30_days = intval(floor($this->total_contacts_last_30_days/30));
        $this->avg_visits_last_30_days = intval(floor($this->total_visits_last_30_days/30));
        
		for ( $i = 31; $i >= 0; $i-- )
		{
			// x axis labels
			$this->x_axis_labels .= "'" . strtoupper(date('M j', strtotime('-'. $i .' days'))) . "'". ( $i != 0 ? "," : "" );

			// colors for chart columns
			$array_key = inboundrocket_search_object_by_value($contacts, date('Y-m-d', strtotime('-'. $i .' days')), 'lead_date');
			
			if($array_key && $contacts[$array_key]->contacts > $this->avg_contacts_last_30_days){
				$this->column_data .= "{ name: '" . strtoupper(date('M j', strtotime('-'. $i .' days'))) . "', color: '#00CAF0', y: " . ( $array_key ? $contacts[$array_key]->contacts : '0' ) . " }" . ( $i != 0 ? ", " : "" );
			} else {
				$this->column_data .= "{ name: '" . strtoupper(date('M j', strtotime('-'. $i .' days'))) . "', color: '#00CAF0', y: " . ( $array_key ? $contacts[$array_key]->contacts : '0' ) . " }" . ( $i != 0 ? ", " : "" );
			}
			

            // weekend background column points
			if ( inboundrocket_is_weekend(date('M j', strtotime('-'. $i .' days'))) )
				$this->weekend_column_data .= "{ name: '" . strtoupper(date('M j', strtotime('-'. $i .' days'))) . "', color: 'rgba(0,0,0,.05)', y: " . ( $array_key ? $contacts[$array_key]->contacts : '0' ) . " }" . ( $i != 0 ? ", " : "" );
			else
				$this->weekend_column_data .= "{ name: '" . strtoupper(date('M j', strtotime('-'. $i .' days'))) . "', color: 'rgba(0,0,0,0)', y: " . ( $array_key ? $contacts[$array_key]->contacts : '0' ) . " }" . ( $i != 0 ? ", " : "" );		

			$array_key = inboundrocket_search_object_by_value($visits, date('Y-m-d', strtotime('-'. $i .' days')), 'pageview_date');
			$this->visits_data .= "{ name: '" . strtoupper(date('M j', strtotime('-'. $i .' days'))) . "', color: '#CCF3FC', y: ".$visits[$array_key]->pageviews." }" . ( $i != 0 ? "," : "");
			
            // average line
            if ( $this->avg_contacts_last_30_days )
            {
                $this->average_data .= $this->avg_contacts_last_30_days . ( $i != 0 ? "," : "");
            }

		}
	}

	function get_sources ()
	{
		global $wpdb;

		$q = "SELECT hashkey lh,
			( SELECT MIN(pageview_source) AS pageview_source FROM {$wpdb->ir_pageviews} WHERE lead_hashkey = lh AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS lead_source,
			( SELECT MIN(pageview_url) AS pageview_url FROM {$wpdb->ir_pageviews} WHERE lead_hashkey = lh AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS lead_origin_url 
		 FROM 
		 	{$wpdb->ir_leads}
		 WHERE 
		 	lead_date BETWEEN CURDATE() - INTERVAL 30 DAY AND NOW() AND lead_email != ''";
	
		$contacts = $wpdb->get_results($q);

		foreach ( $contacts as $contact ) 
		{
			$source = $this->check_lead_source($contact->lead_source, $contact->lead_origin_url);

			switch ( $source )
		    {
		    	case 'search' :
		    		$this->organic_count++;
		    	break;

		    	case 'social' :
		    		$this->social_count++;
		    	break;
		    
		    	case 'email' :
		    		$this->email_count++;
		    	break;

		    	case 'referral' :
		    		$this->referral_count++;
		    	break;

		    	case 'paid' :
		    		$this->paid_count++;
		    	break;

		    	case 'direct' :
		    		$this->direct_count++;
		    	break;
		    }
		}

		$this->max_source = max(array($this->organic_count, $this->referral_count, $this->social_count, $this->email_count, $this->paid_count, $this->direct_count));
	}
	
	function get_shares()
	{
		global $wpdb;
		
		$shares = array();
		
		$q = "SELECT a.share_id,a.share_type,a.share,a.share_date,COUNT(a.share) as total FROM {$wpdb->ir_shares} a WHERE a.share_date BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() GROUP BY a.share HAVING COUNT(a.share) >= 1 ORDER BY a.share_id DESC;";
		
		$ss = $wpdb->get_results($q);
		
		if($ss):
		foreach($ss as $s){
			$arr = array(
				'shared_count' => $s->total,
				'shared_to' => $s->share_type,
				'shared_content' => $s->share,
				'id' => $s->share_id
			);
			$shares[] = $arr;
		}
		endif;
		
		return $shares;
	}
	
	function get_popular_pages()
	{
		global $wpdb;
		
		$popular_pages = array();
		
		$q = "SELECT pageview_date,pageview_title,pageview_url,COUNT(pageview_title) as cnt FROM {$wpdb->ir_pageviews} WHERE pageview_date >= '".date('Y-m-d')." 00:00:00' GROUP BY pageview_title HAVING COUNT(pageview_title) >= 2 ORDER BY cnt DESC;";
	
		$pp = $wpdb->get_results($q);
		
		$max_total = count($pp);
		
		if($pp):
		foreach($pp as $p){
			$arr = array(
				'page_title' => str_replace(site_url(),'',$p->pageview_url),
				'page_name' => $p->pageview_title,
				'total' => $p->cnt,
				'max_total' => $max_total	
			);
			$popular_pages[] = $arr;
		}
		endif;
	
		return $popular_pages;

	}	
	
	function get_popular_referrer()
	{
		global $wpdb;

		$popular_referrer = array();

		$q = "
			SELECT DISTINCT lead_hashkey,
				pageview_source, 
				count(*) as cnt 
			FROM 
				{$wpdb->ir_pageviews}
			WHERE 
				pageview_source != '' AND
				pageview_deleted <= 0
			GROUP BY 
				pageview_source
			ORDER BY 
				cnt DESC;";
		
		$pr = $wpdb->get_results($q);
		
		$count = 1;
		$total = 0;
		
		if($pr):
		foreach($pr as $r){
			$cnt = $r->cnt;
			$total += $cnt;
		}
		
		foreach($pr as $r){
			$cnt = $r->cnt;
			if($cnt > 1 && $count <= 5):
				$arr = array(
					'referral_source' => str_replace(site_url(),'',$r->pageview_source),
					'referral_count' => $cnt,
					'referral_total' => $total
				);
				$popular_referrer[] = $arr;
			endif;
			$count++;
		}
		endif;
		
		return $popular_referrer;
	}	
		
	function get_new_contacts ()
	{
		global $wpdb;

		$q = "
			SELECT DISTINCT lead_hashkey lh,
				lead_id, 
				lead_email, 
				( SELECT COUNT(*) FROM $wpdb->ir_pageviews WHERE lead_hashkey = lh ) as pageviews, 
				( SELECT MIN(pageview_source) AS pageview_source FROM $wpdb->ir_pageviews WHERE lead_hashkey = lh AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS lead_source,
				( SELECT MIN(pageview_url) AS pageview_url FROM $wpdb->ir_pageviews WHERE lead_hashkey = lh AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS lead_origin_url 
			FROM 
				$wpdb->ir_leads ll, $wpdb->ir_pageviews lpv
			WHERE 
				lead_date >= CURRENT_DATE() AND 
				ll.hashkey = lpv.lead_hashkey AND 
				pageview_deleted = 0 AND lead_email != '' AND lead_deleted = 0;";

		return $wpdb->get_results($q);	
	}

	function get_returning_contacts ()
	{
		global $wpdb;

		$q = "
			SELECT 
				DISTINCT lead_hashkey lh,
				lead_id, 
				lead_email, 
				( SELECT COUNT(*) FROM $wpdb->ir_pageviews WHERE lead_hashkey = lh ) as pageviews,
				( SELECT MIN(pageview_source) AS pageview_source FROM $wpdb->ir_pageviews WHERE lead_hashkey = lh AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS lead_source,
				( SELECT MIN(pageview_url) AS pageview_url FROM $wpdb->ir_pageviews WHERE lead_hashkey = lh AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS lead_origin_url 
			FROM 
				$wpdb->ir_leads ll, $wpdb->ir_pageviews lpv
			WHERE 
				ll.lead_date < CURRENT_DATE() AND 
				pageview_date >= CURRENT_DATE() AND 
				ll.hashkey = lpv.lead_hashkey AND 
				pageview_deleted = 0 AND lead_email != '' AND lead_deleted = 0 ";

		return $wpdb->get_results($q);
	}	

	function check_lead_source ( $source, $origin_url = '' )
	{
		if ( $source )
		{
			$decoded_source = urldecode($source);

			if ( stristr(strtolower($decoded_source), 'utm_medium=cpc') || stristr(strtolower($decoded_source), 'utm_medium=ppc') || stristr(strtolower($decoded_source), 'aclk') || stristr(strtolower($decoded_source), 'gclid') )
				return 'paid';

			if ( stristr($source, 'utm_') )
			{
				$url = $source;
				$url_parts = parse_url($url);
				parse_str($url_parts['query'], $path_parts);

				if ( isset($path_parts['adurl']) )
					return 'paid';

				if ( isset($path_parts['utm_medium']) )
				{
					if ( strtolower($path_parts['utm_medium']) == 'cpc' || strtolower($path_parts['utm_medium']) == 'ppc' || strtolower($path_parts['utm_medium']) == 'ysm' || strtolower($path_parts['utm_medium']) == 'msn-ppc' || strtolower($path_parts['utm_medium']) == 'yahoo-ppc' || strtolower($path_parts['utm_medium']) == 'bing-ppc' )
						return 'paid';

					if ( strtolower($path_parts['utm_medium']) == 'social' || strtolower($path_parts['utm_medium']) == 'facebook' || strtolower($path_parts['utm_medium']) == 'twitter' || strtolower($path_parts['utm_medium']) == 'linkedin' )
						return 'social';

					if ( strtolower($path_parts['utm_medium']) == 'email' || strtolower($path_parts['utm_medium']) == 'e-mail' || strtolower($path_parts['utm_medium']) == 'newsletter' )
						return 'email';
				}

				if ( isset($path_parts['utm_source']) )
				{
					if ( stristr(strtolower($path_parts['utm_source']), 'email') || stristr(strtolower($path_parts['utm_source']), 'e-mail') || stristr(strtolower($path_parts['utm_source']), 'mailchimp') || stristr(strtolower($path_parts['utm_source']), 'constantcontact') || stristr(strtolower($path_parts['utm_source']), 'aweber') || stristr(strtolower($path_parts['utm_source']), 'icontact') || stristr(strtolower($path_parts['utm_source']), 'verticalresponse') || stristr(strtolower($path_parts['utm_source']), 'getresponse') ) 
						return 'email';
				}
			}

			$referer = $this->parser->parse(
			     $source
			);

			if ( $referer->isKnown() )
				return $referer->getMedium();
			else
			    return 'referral';
		}
		else
		{
			$decoded_origin_url = urldecode($origin_url);

			if ( stristr(strtolower($decoded_origin_url), 'utm_medium=cpc') || stristr(strtolower($decoded_origin_url), 'utm_medium=ppc') || stristr(strtolower($decoded_origin_url), 'aclk') || stristr(strtolower($decoded_origin_url), 'gclid') )
				return 'paid';

			if ( stristr($decoded_origin_url, 'utm_') )
			{
				$url = $decoded_origin_url;
				$url_parts = parse_url($url);
				parse_str($url_parts['query'], $path_parts);

				if ( isset($path_parts['adurl']) )
					return 'paid';

				if ( isset($path_parts['utm_medium']) )
				{
					if ( strtolower($path_parts['utm_medium']) == 'cpc' || strtolower($path_parts['utm_medium']) == 'ppc' || strtolower($path_parts['utm_medium']) == 'ysm' || strtolower($path_parts['utm_medium']) == 'msn-ppc' || strtolower($path_parts['utm_medium']) == 'yahoo-ppc' || strtolower($path_parts['utm_medium']) == 'bing-ppc' )
						return 'paid';

					if ( strtolower($path_parts['utm_medium']) == 'social' || strtolower($path_parts['utm_medium']) == 'facebook' || strtolower($path_parts['utm_medium']) == 'twitter' || strtolower($path_parts['utm_medium']) == 'linkedin' )
						return 'social';

					if ( strtolower($path_parts['utm_medium']) == 'email' || strtolower($path_parts['utm_medium']) == 'e-mail' || strtolower($path_parts['utm_medium']) == 'newsletter' )
						return 'email';
				}

				if ( isset($path_parts['utm_source']) )
				{
					if ( stristr(strtolower($path_parts['utm_source']), 'email')  || stristr(strtolower($path_parts['utm_source']), 'e-mail') || stristr(strtolower($path_parts['utm_source']), 'mailchimp') || stristr(strtolower($path_parts['utm_source']), 'constantcontact') || stristr(strtolower($path_parts['utm_source']), 'aweber') || stristr(strtolower($path_parts['utm_source']), 'icontact') || stristr(strtolower($path_parts['utm_source']), 'verticalresponse') || stristr(strtolower($path_parts['utm_source']), 'getresponse') ) 
						return 'email';
				}
			}

			return 'direct';
		}
	}

	function print_readable_source ( $source )
	{
		switch ( $source )
	    {
	    	case 'search' :
	    		return 'Organic Search';
	    	break;

	    	case 'social' :
	    		return 'Social Media';
	    	break;
	    
	    	case 'email' :
	    		return 'Email Marketing';
	    	break;

	    	case 'referral' :
	    		return 'Referral';
	    	break;

	    	case 'paid' :
	    		return 'Paid';
	    	break;

	    	case 'direct' :
	    		return 'Direct';
	    	break;
	    }
	}
	

}

?>