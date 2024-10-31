<?php
/**
 * @package Optingun
 * @version 1.0
 */
/*
Plugin Name: Optingun
Plugin URI: https://optingun.com/
Description: This Plugin helps integrating Optingun forms into your website.
Author: San
Version: 1.0
Author URI: https://grainpot.com/
License: GPLv2 or later
*/
/*
Optingun is SaaS lead capture tool, 
helps you create amazing lead capture forms 
and this plugin will help in integrating the forms to your wordpress site.
Copyright 2016-2018 Grainpot Global.
*/

//unique identifier
//gp_optingun => Grainpot Optingun

//store optingun domain for API operations
define('GP_OPTINGUN_DOMAIN','https://optingun.com/');

//adding quick manage link to plugin list
function gp_optingun_settings_link( $links ) {	
    $settings_link = '<a href="'.admin_url( 'admin.php?page=optingun').'">' . __( 'Manage Forms' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}

//hook for the above
add_filter( "plugin_action_links_".plugin_basename(__FILE__), 'gp_optingun_settings_link' );



//adding menu item to left menu
function gp_optingun_dash_page(){
	
    add_menu_page( 
        __( 'Optingun Control Panel', 'textdomain' ),
        'Optingun',
        'manage_options',
        'optingun',
        'gp_optingun_dash',
        plugins_url( 'optingun/optingun_logo.png' ),
        6
    ); 
}
//menu hook
add_action( 'admin_menu', 'gp_optingun_dash_page' );

//including styles for dashboard design 
function gp_optingun_dash_styles() {
    wp_enqueue_style('gp-optingun-looks', plugins_url('style.css', __FILE__));
}
//hook for attaching the styles
add_action('admin_enqueue_scripts', 'gp_optingun_dash_styles');


	
	


//dashboard page for the plugin page
function gp_optingun_dash(){
	

	
	//action of saving API key
if(isset($_POST['gp_optingun_api_key_save']))
	{
		//validate data, key is going to be alphanumberic so just check if its not empty/null
		if( isset($_POST['gp_optingun_api_key']) && trim($_POST['gp_optingun_api_key']) != '' )
		{
			
			update_option('gp_optingun_apikey',trim(sanitize_text_field($_POST['gp_optingun_api_key'])));		
			?>
			<div class="updated notice"><p>Your Optingun API Key saved</p></div>
			<?php
		}else{
			?>
			<div class="notice-error notice"><p>Invalid API Key</p></div>
			<?php
		}
		
	}
	
	
	//get the forms from wpoptions
	$gp_optingun_active_form_str = (get_option('gp_optingun_activeforms')!=''?get_option('gp_optingun_activeforms'):'[]');
	$gp_optingun_active_forms = array_values((array)json_decode($gp_optingun_active_form_str));
	

	
	//adding a form to site
	if( isset($_POST['gp_optingun_add_form_id']))
	{
		//sanitize the data
		$gpog_form_id = absint($_POST['gp_optingun_form_id']);
		//validate if form id is all good
		if(isset($gpog_form_id) && is_int($gpog_form_id) && $gpog_form_id > 0)
		{
		$gp_optingun_active_forms[] = $gpog_form_id;
		update_option('gp_optingun_activeforms',json_encode(array_unique(array_filter($gp_optingun_active_forms))));
		?>
		<div class="updated notice"><p>Optingun Form(ID#<?php echo esc_html($gpog_form_id); ?>) added to your Site</p></div>
		<?php }else{ ?>
		<div class="notice-error notice"><p>Invalid Form ID, please try again!</p></div>
		<?php }
	}
	
	//remove form from site
	if( isset($_POST['gp_optingun_remove_form_id']) )
	{
		//sanitize the data
		$gpog_form_id = absint($_POST['gp_optingun_form_id']);
		
		if(isset($gpog_form_id) && is_int($gpog_form_id) && $gpog_form_id > 0)
		{
		if (($key = array_search($gpog_form_id, $gp_optingun_active_forms)) !== false) {
			unset($gp_optingun_active_forms[$key]);
		}
		update_option('gp_optingun_activeforms',json_encode(array_unique(array_filter($gp_optingun_active_forms))));
		?>
		<div class="updated notice"><p>Optingun Form(ID#<?php echo esc_html($gpog_form_id); ?>) removed from your Site</p></div>
		<?php }else{ ?>
		<div class="notice-error notice"><p>Invalid Form ID, please try again!</p></div>
		<?php }
	}
	
	
    $return = '<div class="gp_optingun_wraper wrap">';  
    $return.= '<h1 class="wp-heading-inline">Optingun Control Panel</h1>';  
    $return.= '<div class="og_api_wraper">';  
    $return.= '<span class="ogheadings ">Enter Your Optingun API Key</span>';  
    $return.= '<form method="post"><input name="gp_optingun_api_key" value="'.esc_html(get_option('gp_optingun_apikey')).'" class="gp_optingun_api_key regular-text" type="text" placeholder="API Key"><input name="gp_optingun_api_key_save" class="gp_optingun_api_key_save  " type="submit" value="Save API Key"><a href="'.esc_url(GP_OPTINGUN_DOMAIN).'dashboard/api.php" target="_blank" class="gp_optingun_api_key_get">Get your API Key</a></form>';  
    $return.= '</div>';  
	
	//display forms only when api key is available
	if(get_option('gp_optingun_apikey') !="")
	{
	//get the forms by making call to Optingun API
	$gp_optingun_api_data = json_decode(wp_remote_retrieve_body( wp_remote_get(GP_OPTINGUN_DOMAIN."gun/v3/?action=form_list&apikey=".get_option('gp_optingun_apikey'))));

			
		$return.= '<div class="og_forms_wraper">';  
		$return.= '<span class="ogheadings ">All Forms</span>';  
		
		//check the status API response
		if($gp_optingun_api_data->status == '1')
		{


			//save used hits on this account
			//if its a valid number
				if(isset($gp_optingun_api_data->hits) && is_int($gp_optingun_api_data->hits) && $gp_optingun_api_data->hits > 0)
				{
					update_option('gp_optingun_used_hits',$gp_optingun_api_data->hits);
				}else{
					//if not a valid numbr then zero
					update_option('gp_optingun_used_hits',0);
				}
			//save plan hits on this account
			//if its a valid number
				if(isset($gp_optingun_api_data->planhits) && is_int($gp_optingun_api_data->planhits) && $gp_optingun_api_data->planhits > 0)
				{
					update_option('gp_optingun_plan_hits',$gp_optingun_api_data->planhits);
				}else{
					//if not a valid numbr then zero
					update_option('gp_optingun_plan_hits',0);
				}
				
				
		
			//when the data contains active forms
			if(is_array($gp_optingun_api_data->data) && count($gp_optingun_api_data->data) > 0 )
			{
				//prepare html for every form
				foreach($gp_optingun_api_data->data as $formrec)
				{
					$return.= '<div class="form_row"><span class="left">'.esc_html(($formrec->name!=''?$formrec->name:'Un-named Form')).'</span><span class="right"><form method="post"><input name="gp_optingun_form_id" type="hidden" value="'.esc_html($formrec->id).'" />'.(!in_array($formrec->id,$gp_optingun_active_forms)?'<input type="submit" name="gp_optingun_add_form_id" class="gp_optingun_add_form_id" value="Add this Form" />':'<input type="submit" name="gp_optingun_remove_form_id" class="gp_optingun_remove_form_id" value="Remove this Form" />').'</form></span></div>';
				}
			}
		}else
		{
			//display error
			$return.= '<span class="ogheadings " style="color:red;">Response from API: '.esc_html($gp_optingun_api_data->message).'</span>'; 
		}
		
		$return.= '</div>';
	}
	
    $return.= '</div>';  
	
	echo $return;
}


//hook for adding optingun javascript code to footer of site
add_action('wp_footer', 'gp_optingun_add_embeding_scripts');

//footer hook implementation
function gp_optingun_add_embeding_scripts() {
	//get all forms
	$gp_optingun_active_form_str = (get_option('gp_optingun_activeforms')!=''?get_option('gp_optingun_activeforms'):'[]');
	$gp_optingun_active_forms = array_values((array)json_decode($gp_optingun_active_form_str));
	//check if there are any active forms
	if(count($gp_optingun_active_forms) > 0 )
	{
	foreach($gp_optingun_active_forms as $form)
		{
			if($form != '')
			{
				//add embed elements for every form
				echo '<div  optin_id="'.esc_html($form).'" og-api-key="'.esc_html(get_option('gp_optingun_apikey')).'" ></div>';
			}
		}
	

	//add Optingun Javascript SDK when there are forms
    echo '<script>
		function optingun_load(d, s, id,source) { var js, father = d.getElementsByTagName(s)[0];  
		if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = source;  
		father .parentNode.insertBefore(js, father );}
		optingun_load(document, \'script\', \'jquery-lib\',\''.esc_url(GP_OPTINGUN_DOMAIN).'sdkv3.js\');
		</script>';
	}
}



//usage notification with admin notification hook
function gp_optingun_warn_user_for_usage() {
	if( get_option('gp_optingun_used_hits') > get_option('gp_optingun_plan_hits') )
	{
	?>
		<div class="error notice">
			<p><?php echo _e( 'YOU ARE LOOSING LEADS!</br>Your Optingun Account exceeded monthly usage limit, upgrade your plan to not miss any leads.', 'optingun_textdomain' ); ?></p>
		</div>
	<?php
	}
}
//admin notification hook for major data related statuses
add_action( 'admin_notices', 'gp_optingun_warn_user_for_usage' );

