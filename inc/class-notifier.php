<?php
if ( !defined('ABSPATH') ) exit;

//=============================================
// IR_Notifier Class
//=============================================
if(!class_exists('IR_Notifier')):
class IR_Notifier {
    
    /**
     * Class constructor
     */
    function __construct(){}

    /**
     * Sends the leads history email
     *
     * @param   string
     * @return  bool $email_sent    Whether the email contents were sent successfully. A true return value does not automatically mean that the user received the email successfully. It just only means that the method used was able to process the request without any errors.
     */
    function send_new_lead_email ( $hashkey ) 
    {
        $ir_contact = new IR_Contact();
        $ir_contact->hashkey = $hashkey;
        $ir_contact->get_contact_history();
        $history = $ir_contact->history;
        
        $lead_email = isset($history->lead->lead_email) ? $history->lead->lead_email : '';

        $body = null;
        
        $body = $this->build_body($history);

        // Each line in an email can only be 998 characters long, so lines need to be broken with a wordwrap
        $body = wordwrap($body, 900, "\r\n"); 

		$options = get_option('inboundrocket_options');
        $to = ( $options['ir_email'] ? $options['ir_email'] : get_bloginfo('admin_email') ); // Get email from plugin settings, if none set, use admin email

        $tag_status = '';
        if ( count($history->lead->last_submission['form_tags']) )
            $tag_status = __('labeled as','inboundrocket').' "' . $history->lead->last_submission['form_tags'][0]['tag_text'] . '" ';

        $return_status = ( $tag_status ? '' : ' ' );
        if ( $history->lead->total_visits > 1 )
            $return_status = __('by a returning visitor','inboundrocket').' ';

        if ( $history->lead->total_submissions > 1 )
            $return_status = __('by a returning contact','inboundrocket').' ';

        $subject = __('Form submission','inboundrocket') ." " . $tag_status . $return_status . "on " . get_bloginfo('name') . " - " . $lead_email;

        $headers = "From: Inbound Rocket <notifications@inboundrocket.co>\r\n";
        $headers .= "Reply-To: Inbound Rocket <notifications@inboundrocket.co>\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";

        $email_sent = wp_mail($to, $subject, $body, $headers);
        inboundrocket_track_plugin_activity('Contact Notification Sent', array('service' => 'php_mail', 'to_email' => $to));

        return $email_sent;
    }

    /**
     * Creates the contact identity section of the contact notification email
     *
     * @param   stdClass    IR_Contact
     * @return  string      concatenated string with HTML body
     */
    function build_body ( $history ) 
    {
	    if(!isset($history)) die('Missing Identity');
	    
	    $email = isset($history->lead->lead_email) ? $history->lead->lead_email : '';
	    
        $format = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml"> <head> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <style type="text/css">body,#bodyTable,#bodyCell{height:100%% !important;margin:0;padding:0;width:100%% !important}img,a img{border:0;outline:none;text-decoration:none}h1,h2,h3,h4,h5,h6{margin:0;padding:0}p{margin:1em 0;padding:0}a{word-wrap:break-word}.ReadMsgBody{width:100%%}.ExternalClass{width:100%%}.ExternalClass,.ExternalClass p,.ExternalClass span,.ExternalClass font,.ExternalClass td,.ExternalClass div{line-height:100%%}table,td{mso-table-lspace:0pt;mso-table-rspace:0pt}#outlook a{padding:0}img{-ms-interpolation-mode:bicubic}body,table,td,p,a,li,blockquote{-ms-text-size-adjust:100%%;-webkit-text-size-adjust:100%%}#bodyCell{padding:20px}.mcnImage{vertical-align:bottom}.mcnTextContent img{height:auto !important}body,#bodyTable{background-color:#f2f2f2}#bodyCell{border-top:0}#templateContainer{border:0}h1{color:#606060 !important;display:block;font-family:Helvetica;font-size:40px;font-style:normal;font-weight:bold;line-height:125%%;letter-spacing:-1px;margin:0;text-align:left}h2{color:#404040 !important;display:block;font-family:Helvetica;font-size:26px;font-style:normal;font-weight:bold;line-height:125%%;letter-spacing:-.75px;margin:0;text-align:left}h3{color:#606060 !important;display:block;font-family:Helvetica;font-size:18px;font-style:normal;font-weight:bold;line-height:125%%;letter-spacing:-.5px;margin:0;text-align:left}h4{color:#808080 !important;display:block;font-family:Helvetica;font-size:16px;font-style:normal;font-weight:bold;line-height:125%%;letter-spacing:normal;margin:0;text-align:left}#templatePreheader{background-color:#FFFFFF;border-top:0;border-bottom:0}.preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{color:#606060;font-family:Helvetica;font-size:11px;line-height:125%%;text-align:left}.preheaderContainer .mcnTextContent a{color:#606060;font-weight:normal;text-decoration:underline}#templateHeader{background-color:#FFFFFF;border-top:0;border-bottom:0}.headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{color:#606060;font-family:Helvetica;font-size:15px;line-height:150%%;text-align:left}.headerContainer .mcnTextContent a{color:#6DC6DD;font-weight:normal;text-decoration:underline}#templateBody{background-color:#FFFFFF;border-top:0;border-bottom:0}.bodyContainer .mcnTextContent,.bodyContainer .mcnTextContent p{color:#606060;font-family:Helvetica;font-size:15px;line-height:150%%;text-align:left}.bodyContainer .mcnTextContent a{color:#6DC6DD;font-weight:normal;text-decoration:underline}#templateFooter{background-color:#FFFFFF;border-top:0;border-bottom:0}.footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{color:#606060;font-family:Helvetica;font-size:11px;line-height:125%%;text-align:left}.footerContainer .mcnTextContent a{color:#606060;font-weight:normal;text-decoration:underline}.button:hover{color:white !important;font-family:Helvetica, Arial, sans-serif;text-decoration:none}.button:active{color:white !important;font-family:Helvetica, Arial, sans-serif;text-decoration:none}.button:visited{color:white !important;font-family:Helvetica, Arial, sans-serif;text-decoration:none}.medium-button:hover table td{background:#2795b6 !important}.medium-button:hover{color:white !important;font-family:Helvetica, Arial, sans-serif;text-decoration:none}.medium-button:active{color:white !important;font-family:Helvetica, Arial, sans-serif;text-decoration:none}.medium-button:visited{color:white !important;font-family:Helvetica, Arial, sans-serif;text-decoration:none}@media only screen and (max-width: 480px){body,table,td,p,a,li,blockquote{-webkit-text-size-adjust:none !important}}@media only screen and (max-width: 480px){body{width:100%% !important;min-width:100%% !important}}@media only screen and (max-width: 480px){td[id=bodyCell]{padding:10px !important}}@media only screen and (max-width: 480px){table[class=mcnTextContentContainer]{width:100%% !important}}@media only screen and (max-width: 480px){table[class=mcnBoxedTextContentContainer]{width:100%% !important}}@media only screen and (max-width: 480px){table[class=mcpreview-image-uploader]{width:100%% !important;display:none !important}}@media only screen and (max-width: 480px){img[class=mcnImage]{width:100%% !important}}@media only screen and (max-width: 480px){table[class=mcnImageGroupContentContainer]{width:100%% !important}}@media only screen and (max-width: 480px){td[class=mcnImageGroupContent]{padding:9px !important}}@media only screen and (max-width: 480px){td[class=mcnImageGroupBlockInner]{padding-bottom:0 !important;padding-top:0 !important}}@media only screen and (max-width: 480px){tbody[class=mcnImageGroupBlockOuter]{padding-bottom:9px !important;padding-top:9px !important}}@media only screen and (max-width: 480px){table[class=mcnCaptionTopContent],table[class=mcnCaptionBottomContent]{width:100%% !important}}@media only screen and (max-width: 480px){table[class=mcnCaptionLeftTextContentContainer],table[class=mcnCaptionRightTextContentContainer],table[class=mcnCaptionLeftImageContentContainer],table[class=mcnCaptionRightImageContentContainer],table[class=mcnImageCardLeftTextContentContainer],table[class=mcnImageCardRightTextContentContainer]{width:100%% !important}}@media only screen and (max-width: 480px){td[class=mcnImageCardLeftImageContent],td[class=mcnImageCardRightImageContent]{padding-right:18px !important;padding-left:18px !important;padding-bottom:0 !important}}@media only screen and (max-width: 480px){td[class=mcnImageCardBottomImageContent]{padding-bottom:9px !important}}@media only screen and (max-width: 480px){td[class=mcnImageCardTopImageContent]{padding-top:18px !important}}@media only screen and (max-width: 480px){td[class=mcnImageCardLeftImageContent],td[class=mcnImageCardRightImageContent]{padding-right:18px !important;padding-left:18px !important;padding-bottom:0 !important}}@media only screen and (max-width: 480px){td[class=mcnImageCardBottomImageContent]{padding-bottom:9px !important}}@media only screen and (max-width: 480px){td[class=mcnImageCardTopImageContent]{padding-top:18px !important}}@media only screen and (max-width: 480px){table[class=mcnCaptionLeftContentOuter] td[class=mcnTextContent],table[class=mcnCaptionRightContentOuter] td[class=mcnTextContent]{padding-top:9px !important}}@media only screen and (max-width: 480px){td[class=mcnCaptionBlockInner] table[class=mcnCaptionTopContent]:last-child td[class=mcnTextContent]{padding-top:18px !important}}@media only screen and (max-width: 480px){td[class=mcnBoxedTextContentColumn]{padding-left:18px !important;padding-right:18px !important}}@media only screen and (max-width: 480px){td[class=mcnTextContent]{padding-right:18px !important;padding-left:18px !important}}@media only screen and (max-width: 480px){table[id=templateContainer],table[id=templatePreheader],table[id=templateHeader],table[id=templateBody],table[id=templateFooter]{max-width:600px !important;width:100%% !important}}@media only screen and (max-width: 480px){h1{font-size:24px !important;line-height:125%% !important}}@media only screen and (max-width: 480px){h2{font-size:20px !important;line-height:125%% !important}}@media only screen and (max-width: 480px){h3{font-size:18px !important;line-height:125%% !important}}@media only screen and (max-width: 480px){h4{font-size:16px !important;line-height:125%% !important}}@media only screen and (max-width: 480px){table[class=mcnBoxedTextContentContainer] td[class=mcnTextContent],td[class=mcnBoxedTextContentContainer] td[class=mcnTextContent] p{font-size:18px !important;line-height:125%% !important}}@media only screen and (max-width: 480px){table[id=templatePreheader]{display:block !important}}@media only screen and (max-width: 480px){td[class=preheaderContainer] td[class=mcnTextContent],td[class=preheaderContainer] td[class=mcnTextContent] p{font-size:14px !important;line-height:115%% !important}}@media only screen and (max-width: 480px){td[class=headerContainer] td[class=mcnTextContent],td[class=headerContainer] td[class=mcnTextContent] p{font-size:18px !important;line-height:125%% !important}}@media only screen and (max-width: 480px){td[class=bodyContainer] td[class=mcnTextContent],td[class=bodyContainer] td[class=mcnTextContent] p{font-size:18px !important;line-height:125%% !important}}@media only screen and (max-width: 480px){td[class=footerContainer] td[class=mcnTextContent],td[class=footerContainer] td[class=mcnTextContent] p{font-size:14px !important;line-height:115%% !important}}@media only screen and (max-width: 480px){td[class=footerContainer] a[class=utilityLink]{display:block !important}}</style> </head> <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin: 0;padding: 0;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;background-color: #f2f2f2;height: 100%% !important;width: 100%% !important;"> <center> <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%%" width="100%%" id="bodyTable" style="border-collapse: collapse; mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;margin: 0;padding: 0;background-color: #f2f2f2;height: 100%% !important;width: 100%% !important;"> <tr> <td align="center" valign="top" id="bodyCell" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;margin: 0;padding: 20px;border-top: 0;height: 100%% !important;width: 100%% !important;"> <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;border: 0; background-color: #FFFFFF;"> <tr> <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <table border="0" cellpadding="0" cellspacing="0" width="600" id="templatePreheader" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;background-color: #FFFFFF;border-top: 0;border-bottom: 0;"> <tr> <td valign="top" class="preheaderContainer" style="padding-top: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"><table border="0" cellpadding="0" cellspacing="0" width="100%%" class="mcnTextBlock" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <tbody class="mcnTextBlockOuter"> <tr> <td valign="top" class="mcnTextBlockInner" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <table align="left" border="0" cellpadding="0" cellspacing="0" width="366" class="mcnTextContentContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <tbody><tr> <td valign="top" class="mcnTextContent" style="padding-top: 9px;padding-left: 18px;padding-bottom: 9px;padding-right: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;color: #606060;font-family: Helvetica;font-size: 11px;line-height: 125%%;text-align: left;">to infinity and beyond! </td></tr></tbody></table> <table align="right" border="0" cellpadding="0" cellspacing="0" width="197" class="mcnTextContentContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <tbody><tr> <td valign="top" class="mcnTextContent" style="padding-top: 9px;padding-right: 18px;padding-bottom: 9px;padding-left: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;color: #606060;font-family: Helvetica;font-size: 11px;line-height: 125%%;text-align: left;"> </td></tr></tbody></table> </td></tr></tbody></table></td></tr></table> </td></tr><tr> <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateHeader" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;background-color: #FFFFFF;border-top: 0;border-bottom: 0;"> <tr> <td valign="top" class="headerContainer" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"><table border="0" cellpadding="0" cellspacing="0" width="100%%" class="mcnImageBlock" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <tbody class="mcnImageBlockOuter"> <tr> <td valign="top" style="padding: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;" class="mcnImageBlockInner"> <table align="left" width="100%%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <tbody><tr> <td class="mcnImageContent" valign="top" style="padding-right: 9px;padding-left: 9px;padding-top: 0;padding-bottom: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <img align="left" alt="" src="https://gallery.mailchimp.com/d5d71070c1ddad7fd4fcd2845/images/311c5ffe-c610-4ee2-88cc-b789e94f1622.png" width="564" style="max-width: 851px;padding-bottom: 0;display: inline !important;vertical-align: bottom;border: 0;outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;" class="mcnImage"> </td></tr></tbody></table> </td></tr></tbody></table></td></tr></table> </td></tr><tr> <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateBody" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;background-color: #FFFFFF;border-top: 0;border-bottom: 0;"> <tr> <td valign="top" class="bodyContainer" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"><table border="0" cellpadding="0" cellspacing="0" width="100%%" class="mcnTextBlock" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <tbody class="mcnTextBlockOuter"> <tr> <td valign="top" class="mcnTextBlockInner" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <table align="left" border="0" cellpadding="0" cellspacing="0" width="600" class="mcnTextContentContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <tbody><tr> <td valign="top" class="mcnTextContent" style="padding: 9px 10px;color: #606060;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;font-family: Helvetica;font-size: 15px;line-height: 150%%;text-align: left;">%s%s%s%s</td></tr></tbody></table> </td></tr></tbody></table></td></tr></table> </td></tr><tr> <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateFooter" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;background-color: #FFFFFF;border-top: 0;border-bottom: 0;"> <tr> <td valign="top" class="footerContainer" style="padding-bottom: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"><table border="0" cellpadding="0" cellspacing="0" width="100%%" class="mcnTextBlock" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <tbody class="mcnTextBlockOuter"> <tr> <td valign="top" class="mcnTextBlockInner" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <table align="left" border="0" cellpadding="0" cellspacing="0" width="600" class="mcnTextContentContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;"> <tbody><tr> <td valign="top" class="mcnTextContent" style="padding-top: 9px;padding-right: 18px;padding-bottom: 9px;padding-left: 18px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%%;-webkit-text-size-adjust: 100%%;color: #606060;font-family: Helvetica;font-size: 11px;line-height: 125%%;text-align: left;"> You are receiving this email because you subscribed to be informed as soon as a new lead leaves his or her details on your website.<br/> <em style="line-height:20.7999992370605px">Copyright © 2015 Inbound Rocket, All rights reserved.</em> <br></td></tr></tbody></table> </td></tr></tbody></table></td></tr></table> </td></tr></table> </td></tr></table> </center> </body></html>';

        $built_body = sprintf(
        	$format, 
        	$this->build_submission_details(get_bloginfo('url')), 
        	$this->build_contact_identity($email), 
        	$this->build_sessions($history), 
        	$this->build_footer($history)
        );

        return $built_body;
    }

    /**
     * Creates the contact identity section of the contact notification email
     *
     * @param   string    site URL
     * @return  string    concatenated string - You have a new lead on [Site Name](linked to site URL)
     */
    function build_submission_details ( $url ) 
    {
        $format = '<span>Hello superstar! </span><br><br>You have a new lead on <a href="%s" style="color: #2ba6cb;text-decoration: none;">%s</a><br><br>' . "\r\n";
        $built_submission_details = sprintf($format, $url, get_bloginfo('name'));
        $built_submission_details .= '<img src="' . $this->create_tracking_pixel() . '"/>';

        return $built_submission_details;
    }

    /**
     * Creates the contact identity section of the contact notification email
     *
     * @param   string    email address from IR_Contact
     * @return  string    concatenated string with avatar + linked email address
     */
    function build_contact_identity ( $email ) 
    { 
	    if(empty($email)) return null;
	    
        $avatar_img = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) );
        
        $format = '<table class="row lead-identity" style="border-spacing: 0; border-collapse: collapse; padding: 0px; vertical-align: top; text-align: left; width: 100%%; position: relative; display: block;"> <tr style="padding: 0; vertical-align: top; text-align: left;"> <td class="wrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 10px 0px 0px 0px; vertical-align: top; text-align: left; position: relative; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <table class="two columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 100%%; position: relative; display: block;"> <tr style="padding: 0; vertical-align: top; text-align: left;"> <td class="text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0px 0px 10px; vertical-align: top; text-align: left; padding-left: 10px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <img height="60" width="60" src="%s" style="border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%%; float: left; clear: both; display: block;"/> </td><td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; visibility: hidden; width: 0px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"></td></tr></table></td></tr><tr><td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 10px 0px 0px 0px; vertical-align: top; text-align: left; position: relative; padding-right: 0px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <table class="ten columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 480px;"> <tr style="padding: 0; vertical-align: top; text-align: left;"> <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0px 0px 10px; vertical-align: top; text-align: left; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <h1 style="color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; line-height: 60px; word-break: normal; font-size: 26px;"><a href="mailto:%s" style="text-decoration: none; color: #6DC6DD;">%s</a></h1> </td><td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; visibility: hidden; width: 0px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"></td></tr></table>';
        $built_identity = sprintf($format, $avatar_img, $email, $email);

        return $built_identity;
    }

    /**
     * Creates each session section separated by a spacer
     *
     * @param   stdClass    IR_Contact
     * @return  string      concatenated string of sessions
     */
    function build_sessions ( $history ) 
    {
        $built_sessions = "";

        $sessions = $history->sessions;

        foreach ( $sessions as &$session ) 
        {
            $first_event = end($session['events']);
            $first_event_date = $first_event['activities'][0]['event_date'];
            $session_date = date('F j, Y, g:i a', strtotime($first_event_date)); 

            $last_event = array_values($session['events']);
            $last_event = $last_event[0];
            $last_activity = end($last_event['activities']);
            $session_end_time = date('g:i a', strtotime($last_activity['event_date']));

            $format = '<table class="row lead-timeline__date" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="twelve columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 580px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><h4 style="color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: bold;padding: 0;margin: 0;text-align: left;line-height: 1.3;word-break: normal;font-size: 14px;">%s - %s</h4></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
            $built_sessions .= sprintf($format, $session_date, $session_end_time);

            $events = $session['events'];

            foreach ( $events as &$event ) 
            {
                if ( $event['event_type'] == 'pageview' ) 
                {
                    $pageview = $event['activities'][0];
                    $pageview_time = date('g:ia', strtotime($pageview['event_date']));
                    $pageview_url = $pageview['pageview_url'];
                    $pageview_title = $pageview['pageview_title'];
                    $pageview_source = $pageview['pageview_source'];

                    $format = '<table class="row lead-timeline__event pageview" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;background-color: #fff;border-top: 1px solid #dedede;border-right: 1px solid #dedede;border-left: 4px solid #28c;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="two columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 80px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-time" style="margin: 0;color: #1f6696;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="ten columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 480px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-title" style="margin: 0;color: #1f6696;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p><p class="lead-timeline__pageview-url" style="margin: 0;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;"><a href="%s" style="color: #999;text-decoration: none;">%s</a></p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
                    $built_sessions .= sprintf($format, $pageview_time, $pageview_title, $pageview_url, inboundrocket_strip_params_from_url($pageview_url));

                    if ( $pageview['event_date'] == $first_event_date ) 
                    {
                        $format = '<table class="row lead-timeline__event traffic-source" style="margin-bottom: 20px;border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;background-color: #fff;border-top: 1px solid #dedede;border-right: 1px solid #dedede;border-left: 4px solid #99aa1f;border-bottom: 1px solid #dedede;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="two columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 80px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-time" style="margin: 0;color: #727e14;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="ten columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 480px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-title" style="margin: 0;color: #727e14;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">Traffic Source: %s</p> %s </td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
                        $built_sessions .= sprintf($format, $pageview_time, ( $pageview_source ? '<a href="' . $pageview_source . '">' . inboundrocket_strip_params_from_url($pageview_source) . '</a>' : 'Direct' ), $this->build_source_url_params($pageview_source));
                    }
                }
                else if ( $event['event_type'] == 'form' ) 
                {
                    $submission = $event['activities'][0];
                    $submission_Time = date('g:ia', strtotime($submission['event_date']));
                    $submission_url = $submission['form_page_url'];
                    $submission_page_title = $submission['form_page_title'];
                    $submission_form_fields = json_decode($submission['form_fields']);

                    $submission_tags = '';
                    if ( count($event['form_tags']) )
                    {
                        $submission_tags = ' and tagged as ';
                        for ( $i = 0; $i < count($event['form_tags']); $i++ )
                            $submission_tags .=  '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=inboundrocket_contacts&contact_type=' . $event['form_tags'][$i]['tag_slug'] . '">' . $event['form_tags'][$i]['tag_text'] . '</a> ';
                    }
                    
                    $format = '<table class="row lead-timeline__event submission" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;background-color: #fff;border-top: 1px solid #dedede;border-right: 1px solid #dedede;border-left: 4px solid #f6601d;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="two columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 80px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-time" style="margin: 0;color: #b34a12;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="ten columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 480px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-title" style="margin: 0;color: #b34a12;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">Filled out ' . $event['form_name'] . ' on page <a href="%s" style="color: #2ba6cb;text-decoration: none;">%s</a>%s</p> %s </td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
                    $built_sessions .= sprintf($format, $submission_Time, $submission_url, $submission_page_title, $submission_tags, $this->build_form_fields($submission_form_fields));
                }
                else if ($event['event_type'] == 'text-share')
                {
	                $textshare = $event['activities'][0];
	                $textshare_Time = date('g:ia', strtotime($textshare['event_date']));
                    $textshare_url = get_permalink($textshare['post_id']);
                    $textshare_page_title = get_the_title($textshare['post_id']);
                    $textshare_text_shared = $textshare['share'];
                    $textshare_shared_to = $textshare['share_type'];
                    
	            	$format = '<table class="row lead-timeline__event text-share" style="border-spacing: 0; border-collapse: collapse; padding: 0px; vertical-align: top; text-align: left; width: 100%%; position: relative; display: block; background-color: #fff; border-top: 1px solid #dedede; border-right: 1px solid #dedede; border-left: 4px solid #00caf0; border-bottom: 1px solid #dedede;"> <tr style="padding: 0; vertical-align: top; text-align: left;"> <td class="wrapper" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 10px 0px 0px 0px; vertical-align: top; text-align: left; position: relative; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <table class="two columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 80px;"> <tr style="padding: 0; vertical-align: top; text-align: left;"> <td class="text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0px 0px 10px; vertical-align: top; text-align: left; padding-left: 10px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <p class="lead-timeline__event-time" style="margin: 0; color: #00caf0; font-family: Helvetica, Arial, sans-serif; font-weight: normal; padding: 0; text-align: left; line-height: 19px; font-size: 14px; margin-bottom: 10px;">%s</p></td><td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; visibility: hidden; width: 0px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"></td></tr></table> </td><td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 10px 0px 0px 0px; vertical-align: top; text-align: left; position: relative; padding-right: 0px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <table class="ten columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 480px;"> <tr style="padding: 0; vertical-align: top; text-align: left;"> <td class="text-pad" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0px 0px 10px; vertical-align: top; text-align: left; padding-left: 10px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <p class="lead-timeline__event-title" style="margin: 0; color: #00caf0; font-family: Helvetica, Arial, sans-serif; font-weight: normal; padding: 0; text-align: left; line-height: 19px; font-size: 14px; margin-bottom: 10px;">Shared a text snippet from <a href="%s" style="text-decoration: none; color: #6DC6DD;">%s</a></p><p style="text-transform: uppercase; letter-spacing: 0.05em; color: #999; margin-bottom: 6px; font-size: 0.9em;">text shared:</p><p style="font-size: 13px; line-height: 1.5;">%s</p><p style="text-transform: uppercase; letter-spacing: 0.05em; color: #999; margin-bottom: 6px; font-size: 0.9em;">shared to:</p><p style="font-size: 13px; line-height: 1.5;">%s</p></td><td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; visibility: hidden; width: 0px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"></td></tr></table> </td></tr></table>';
	            	$built_sessions .= sprintf($format, $textshare_Time, $textshare_url, $textshare_page_title, $textshare_text_shared, $textshare_shared_to);
	            	    
	            }
            }
        }

        return $built_sessions;
    }

    /**
     * Creates the form fields event for contact notification email
     *
     * @param   object      json decoded set of form fields
     * @return  string      concatenated string of form fields
     */
    function build_form_fields ( $form_fields ) 
    {
        $built_form_fields = "";

        if ( count($form_fields) )
        {
            foreach ( $form_fields as $field )
            {
                $label = isset($field->label) ? esc_attr($field->label) : '';
                $value = isset($field->value) ? str_replace("\n", "\\n", str_replace(array("\r\n"), "\n", $field->value)) : '';
                
                $format = '<p class="lead-timeline__submission-field" style="margin: 0;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;"><label class="lead-timeline__submission-label" style="text-transform: uppercase;font-size: 12px;color: #999;letter-spacing: 0.05em;">%s</label><br/>%s </p>';
                $built_form_fields .= sprintf($format, $label, inboundrocket_html_line_breaks($value));
            }
        }
        
        return $built_form_fields;
    }

    /**
     * Creates the traffic source url params display for the contact notification email
     *
     * @param   object      string
     * @return  string      concatenated string of key value pairs for url params
     */
    function build_source_url_params ( $source_url ) 
    {
        $built_source_url_params = "";
        $url_parts = parse_url($source_url);

        if ( isset($url_parts['query']) )
        {
            parse_str($url_parts['query'], $url_vars);
            if ( count($url_vars) )
            {
                foreach ( $url_vars as $key => $value )
                {
                    $value =  str_replace("\n", "\\n", str_replace(array("\r\n"), "\n", $value));

                    if ( ! $value )
                        continue;

                    $format = '<p class="lead-timeline__submission-field" style="margin: 0;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;"><label class="lead-timeline__submission-label" style="text-transform: uppercase;font-size: 12px;color: #999;letter-spacing: 0.05em;">%s</label><br/>%s </p>';
                    $built_source_url_params .= sprintf($format, $key, inboundrocket_html_line_breaks($value));
                }
            }
        }
        
        return $built_source_url_params;
    }

    /**
     * Creates the footer content for the contact notificaiton email
     *
     * @param   stdClass      history from IR_Contact
     * @return  string        footer content
     */
    function build_footer ( $history ) 
    {
	    
        $built_footer = "";
        $button_text = "View Contact Record";
        $lead_id = isset($history->lead->lead_id) ? $history->lead->lead_id : '';
        $contactViewUrl = get_bloginfo('wpurl') . "/wp-admin/admin.php?page=inboundrocket_contacts&action=view&lead=" . $lead_id;
        
        $format = ' <table class="row footer" style="border-spacing: 0; border-collapse: collapse; padding: 0px; vertical-align: top; text-align: left; width: 100%%; position: relative; display: block; margin-bottom: 20px;"> <tr style="padding: 0; vertical-align: top; text-align: left;"> <td class="wrapper last" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 10px 0px 0px 0px; vertical-align: top; text-align: left; position: relative; padding-right: 0px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <table class="eight columns" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; margin: 0 auto; width: 380px;"> <tr style="padding: 0; vertical-align: top; text-align: left;"> <td align="center" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0px 0px 10px; vertical-align: top; text-align: left; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"> <center style="width: 100%%; min-width: 380px;"> <table class="button medium-button radius" style="border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%%; overflow: hidden; margin-left:100px;"> <tr style="padding: 0; vertical-align: top; text-align: left;"> <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 12px 0 10px; vertical-align: top; text-align: center; color: #ffffff; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px; display: block; width: auto; background: #2ba6cb; border: 1px solid #2284a1; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;"> <a href="%s" style="color: #ffffff; text-decoration: none; font-weight: bold; font-family: Helvetica, Arial, sans-serif; font-size: 20px; display: block; height: 100%%; width: 100%%;">%s</a> </td></tr></table> </center> </td><td class="expander" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; visibility: hidden; width: 0px; color: #222222; font-family: Helvetica, Arial, sans-serif; font-weight: normal; margin: 0; line-height: 19px; font-size: 14px;"></td></tr></table> </td></tr></table>';
        $built_footer .= sprintf($format, $contactViewUrl, $button_text);

        return $built_footer;
    }

    /**
     * Sends the subscription confirmation email
     *
     * @param   none
     * @return  bool $email_sent    Whether the email contents were sent successfully. A true return value does not automatically mean that the user received the email successfully. It just only means that the method used was able to process the request without any errors.
     * @TODO needs to reflect the bakend settings with the welcome email instead of the below body content and subject, if NO premium, powered by link should stay in the footer
     */
    function send_subscriber_confirmation_email ( ) 
    {
	    $ir_email = 'hello@inboundrocket.co';
        // Get email from plugin settings, if none set, use admin email
        $options = get_option('inboundrocket_options');
        $inboundrocket_email = ( $options['ir_email'] ? $options['ir_email'] : get_bloginfo('admin_email') ); // Get email from plugin settings, if none set, use admin email
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('wpurl');

        // Email Base open
        $body = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'><html xmlns='http://www.w3.org/1999/xhtml' xmlns='http://www.w3.org/1999/xhtml'><head><meta http-equiv='Content-Type' content='text/html;charset=utf-8'/><meta name='viewport' content='width=device-width'/></head><body style='width: 100% !important;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;color: #222222;display: block;font-family: Helvetica, Arial, sans-serif;font-weight: normal;text-align: left;line-height: 19px;font-size: 14px;margin: 0;padding: 0;'><style type='text/css'>a:hover{color: #2795b6 !important;}a:active{color: #2795b6 !important;}a:visited{color: #2ba6cb !important;}h1 a:active{color: #2ba6cb !important;}h2 a:active{color: #2ba6cb !important;}h3 a:active{color: #2ba6cb !important;}h4 a:active{color: #2ba6cb !important;}h5 a:active{color: #2ba6cb !important;}h6 a:active{color: #2ba6cb !important;}h1 a:visited{color: #2ba6cb !important;}h2 a:visited{color: #2ba6cb !important;}h3 a:visited{color: #2ba6cb !important;}h4 a:visited{color: #2ba6cb !important;}h5 a:visited{color: #2ba6cb !important;}h6 a:visited{color: #2ba6cb !important;}.button:hover table td{background: #2795b6 !important;}.tiny-button:hover table td{background: #2795b6 !important;}.small-button:hover table td{background: #2795b6 !important;}.medium-button:hover table td{background: #2795b6 !important;}.large-button:hover table td{background: #2795b6 !important;}.button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.tiny-button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.tiny-button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.tiny-button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.small-button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.small-button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.small-button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.medium-button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.medium-button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.medium-button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.large-button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.large-button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.large-button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.secondary:hover table td{background: #d0d0d0 !important;}.success:hover table td{background: #457a1a !important;}.alert:hover table td{background: #970b0e !important;}@media only screen and (max-width: 600px){table[class='body'] img{width: auto !important;height: auto !important;}table[class='body'] .container{width: 95% !important;}table[class='body'] .row{width: 100% !important;display: block !important;}table[class='body'] .wrapper{display: block !important;padding-right: 0 !important;}table[class='body'] .columns{table-layout: fixed !important;float: none !important;width: 100% !important;padding-right: 0px !important;padding-left: 0px !important;display: block !important;}table[class='body'] .column{table-layout: fixed !important;float: none !important;width: 100% !important;padding-right: 0px !important;padding-left: 0px !important;display: block !important;}table[class='body'] .wrapper.first .columns{display: table !important;}table[class='body'] .wrapper.first .column{display: table !important;}table[class='body'] table.columns td{width: 100%;}table[class='body'] table.column td{width: 100%;}table[class='body'] td.offset-by-one{padding-left: 0 !important;}table[class='body'] td.offset-by-two{padding-left: 0 !important;}table[class='body'] td.offset-by-three{padding-left: 0 !important;}table[class='body'] td.offset-by-four{padding-left: 0 !important;}table[class='body'] td.offset-by-five{padding-left: 0 !important;}table[class='body'] td.offset-by-six{padding-left: 0 !important;}table[class='body'] td.offset-by-seven{padding-left: 0 !important;}table[class='body'] td.offset-by-eight{padding-left: 0 !important;}table[class='body'] td.offset-by-nine{padding-left: 0 !important;}table[class='body'] td.offset-by-ten{padding-left: 0 !important;}table[class='body'] td.offset-by-eleven{padding-left: 0 !important;}table[class='body'] .expander{width: 9999px !important;}table[class='body'] .hide-for-small{display: none !important;}table[class='body'] .show-for-desktop{display: none !important;}table[class='body'] .show-for-small{display: inherit !important;}table[class='body'] .hide-for-desktop{display: inherit !important;}table[class='body'] .container.main{width: 100% !important;}}</style><table class='body' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;height: 100%;width: 100%;padding: 0;'><tr align='left' style='vertical-align: top; text-align: left; padding: 0;'><td class='center' align='center' valign='top' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: center;padding: 0 0 20px;'><center style='width: 100%;'>";
        
        // Email Header open
        $body .= "<table class='row header' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;position: relative;padding: 0px;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='center' align='center' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: center;padding: 0;' valign='top'><center style='width: 100%;'><table class='container' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: inherit;width: 580px;margin:0 auto 10px auto; padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='wrapper last' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;position: relative;padding: 10px 0px 0px;' align='left' valign='top'><table class='twelve columns' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 580px;margin: 0 auto;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='two sub-columns' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;width: 100% !important;padding: 0px 0px 10px 0px;' align='left' valign='top'>";

        $body .= "<h1 class='lead-name' style='color: #222222; display: block; font-family: Helvetica, Arial, sans-serif; font-weight: bold; text-align: left; line-height: 1.3; word-break: normal; font-size: 20px; margin: 0; padding: 0;' align='left'>" . $site_name . "</h1>";

        // Email Header close
        $body .= "</td><td class='expander' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;visibility: hidden;width: 0px;padding: 0;' align='left' valign='top'></td></tr></table></td></tr></table></center></td></tr></table>";

        $body .= "<table class='row header' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;position: relative;padding: 0px;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='center' align='center' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: center;padding: 0;' valign='top'><center style='width: 100%;'><table class='container' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: inherit;width: 580px;margin:0 auto 10px auto; padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='wrapper last' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;position: relative;padding: 10px 0px 0px;' align='left' valign='top'><table class='twelve columns' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 580px;margin: 0 auto;padding: 0;'>";

            $body .= "<tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td>";
                $body .= "<td style='padding: 0px 0px 10px 0px;'>Your subscription to <i><a href='" . $site_url . "'>" . $site_name . "</a></i> has been confirmed.</td>";
            $body .= "</tr>";

            $body .= "<tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td>";
                $body .= "<td style='padding: 10px 0px 20px 0px;'>Just so you have it, here is a copy of the information you submitted to us...</td>";
            $body .= "</tr>";

        $body .= "</table>";

        // Main container open
        $body .= "<table class='container main' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: inherit;width: 580px;margin: 0 auto;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;padding: 0;' align='left' valign='top'>";
        
        // Form Submission section open
        $body .= "<table class='row section form-submission' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;position: relative;display: block;background: #deedf8;padding: 0px;' bgcolor='#deedf8'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='wrapper last' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;position: relative;padding: 0 0px 0 0;' align='left' valign='top'>";
        
        $email = new stdClass;
        $email->label = "Email";
        $email->value = $inboundrocket_email;
        $submission_form_fields = array('email'=>$email);
        
        $submission_Time = date('g:ia', time());
        $submission_url = admin_url('admin.php?page=inboundrocket_settings');
        $submission_page_title = 'Inbound Rocket Plugin Setup';
        
        $format = '<table class="row lead-timeline__event submission" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;background-color: #fff;border-top: 1px solid #dedede;border-right: 1px solid #dedede;border-left: 4px solid #f6601d;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="two columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 80px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-time" style="margin: 0;color: #b34a12;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="ten columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 480px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-title" style="margin: 0;color: #b34a12;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">Filled out form on page <a href="%s" style="color: #2ba6cb;text-decoration: none;">%s</a></p> %s </td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
        $built_sessions = sprintf($format, $submission_Time, $submission_url, $submission_page_title, $this->build_form_fields($submission_form_fields));

        $body .= $built_sessions;

        // Form Submission Section Close
        $body .= "</td></tr></table>";

        // Build [you may contact us at:] row
        $body .= "<table class='row section' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;position: relative;display: block;margin-top: 20px;padding: 0px;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='wrapper last' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;position: relative;padding: 0 0px 0 0;' align='left' valign='top'><table class='twelve columns' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 580px;margin: 0 auto;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;padding: 0px;' align='left' valign='top'><table class='button round' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;overflow: hidden;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td>";
            $body .="You may also contact us at:<br/><a href='mailto:" . $ir_email . "'>" . $ir_email . "</a>";
        $body .= "</td></tr></table></td><td class='expander' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;visibility: hidden;width: 0px;padding: 0;border: 0;' align='left' valign='top'></td></tr></table></td></tr></table>";
        
        $body .= $this->build_powered_by_link();

        // @EMAIL - end form section

        // Email Base close
        $body .= '</center></td></tr></table></body></html>';

        // Each line in an email can only be 998 characters long, so lines need to be broken with a wordwrap
        $body = wordwrap($body, 900, "\r\n");

        $headers = "From: Inbound Rocket <" . $ir_email . ">\r\n";
        $headers.= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers.= "MIME-Version: 1.0\r\n";
        $headers.= "Content-type: text/html; charset=utf-8\r\n";

        $subject = $site_name . ': Subscription Confirmed';

        $email_sent = wp_mail($inboundrocket_email, $subject, $body, $headers);
        return $email_sent;
    }

    /**
     * Builds the Powered by Inbound Rocket links for the email footer
     *
     * @return  string
     */
    function build_powered_by_link ( )
    {
        $powered_by = '';
        
        $powered_by .= "<table class='row section' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;position: relative;display: block;margin-top: 20px;padding: 0px;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='wrapper last' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;position: relative;padding: 0 0px 0 0;' align='left' valign='top'><table class='twelve columns' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 580px;margin: 0 auto;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td style='padding: 10px 20px;' align='left' valign='top'><table style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;overflow: hidden;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: center;display: block;width: auto !important;font-size: 16px;padding: 10px 20px;' align='center' valign='top'>";
            $powered_by .="<div style='font-size: 11px; color: #888; padding: 0 0 5px 0;'>Powered by</div><a href='http://inboundrocket.co/wordpress-subscribe-widget?utm_source=virality&utm_medium=referral&utm_term=" . get_bloginfo('wpurl') . "&utm_content=e11&utm_campaign=subscribe%20confirmation%20email'><img alt='Inbound Rocket' height='23px' width='100px' src='http://inboundrocket.co/wp-content/themes/InboundRocket/img/confirmation-email-small.png' alt='inboundrocket.co'/></a>";
        $powered_by .= "</td></tr></table></td><td class='expander' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;visibility: hidden;width: 0px;padding: 0;border: 0;' align='left' valign='top'></td></tr></table></td></tr></table>";
    
        return $powered_by;
    }

    /**
     * Creates Mixpanel tracking email pixel
     *
     * @return  string      specs @ https://mixpanel.com/docs/api-documentation/pixel-based-event-tracking
     */
    function create_tracking_pixel ( )
    {
        $url_properties = array(
            'token' => INBOUNDROCKET_MIXPANEL_PROJECT_TOKEN
        );

        $inboundrocket_user = inboundrocket_get_current_user();
        $inboundrocket_user_properties = array(
            'distinct_id'   => $inboundrocket_user['user_id'],
            '$wp-url'       => $inboundrocket_user['wp_url'],
            '$wp-version'   => $inboundrocket_user['wp_version'],
            '$ir-version'   => $inboundrocket_user['ir_version'] 
        );

        $properties = array_merge($url_properties, $inboundrocket_user_properties);

        $params = array ( 'event' => 'Contact Notification Opened', 'properties' => $properties );

        return 'http://api.mixpanel.com/track/?data=' . base64_encode(json_encode($params)) . '&ip=1&img=1';
    }
    
}
endif;