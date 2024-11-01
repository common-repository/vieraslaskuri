<?php 
/*
Plugin Name: Vieraslaskuri
Description: Lisää sivuillesi vieraslaskuri 
Version: 1.0
Author: Henrik Rasi
Author URI: https://profiles.wordpress.org/razzie83
tdcense: GPLv2
*/

function vieraslaskuri_frontend_scripts_and_styles() {
 wp_enqueue_style( 'vieraslaskuri_frontend_css', plugins_url( 'vieraslaskuri.css', __FILE__ ) );
 wp_enqueue_style( 'vieraslaskuri_fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css' );

}
add_action( 'wp_enqueue_scripts', 'vieraslaskuri_frontend_scripts_and_styles' );

class VieraslaskuriPlugin_Widget extends WP_Widget {
     
    function __construct() {
        parent::__construct(
         
            // base ID of the widget
            'vieraslaskuri_widget',
             
            // name of the widget
            __('Vieraslaskuri lisäosa', 'VieraslaskuriPlugin' ),
             
            // widget options
            array (
                'description' => __( 'Lisää sivuillesi vieraslaskuri joka näyttää kävijäsi päivittäin, viikottain ja kuukausittain.', 'Vieraslaskuri' )
            )
             
        );
    }
     
      
     
    function widget( $args, $instance ) {
        extract($instance);
        ?>

        <aside class="widget" id="vieraslaskuri">
        <h2 class="vieraslaskuri-heading">Vieraslaskuri</h2>
        <div class="vieraslaskuri-content">
        <table class="vieraslaskuri-table">
        <tr class="vieraslaskuri-row">
            <td class="td-title"><i class="fa fa-calendar"></i> Tänään: </td><td class="td-content"><?php echo vcp_get_visit_count('D') ?></td>
        </tr>
        <tr class="vieraslaskuri-row">
            <td class="td-title"><i class="fa fa-calendar"></i> Tällä viikolla: </td><td class="td-content"><?php echo vcp_get_visit_count('W') ?></td>
        </tr>
        <tr class="vieraslaskuri-row">
            <td class="td-title"><i class="fa fa-calendar"></i> Tässä kuussa: </td><td class="td-content"><?php echo vcp_get_visit_count('M') ?></td>
        </tr>
        <tr class="vieraslaskuri-row">
            <td class="td-title vieraslaskuri-total"><i class="fa fa-calculator"></i> Yhteensä: </td><td class="td-content"><?php echo vcp_get_visit_count('T') ?></td>
        </tr>
        <tr class="vieraslaskuri-row">
            <td class="td-title vieraslaskuri-online"><i class="fa fa-wifi"></i> Nyt online: </td><td class="td-content"><?php echo vcp_get_visit_count('C') ?></td>
        </tr>
        </table>
        </div>
        </aside>
        
        <?php
    }
     
}

function vieraslaskuri_plugin_widget() {
 
    register_widget( 'VieraslaskuriPlugin_Widget' );
 
}

add_action( 'widgets_init', 'vieraslaskuri_plugin_widget' );



function vieraslaskuri_plugin_widget_shortcode($atts) {
    
    global $wp_widget_factory;
    
    // extract(shortcode_atts(array(
    //     'widget_name' => FALSE
    // ), $atts));
    
    $widget_name = 'VieraslaskuriPlugin_Widget';
    // $widget_name = wp_specialchars($widget_name);
    if (!is_a($wp_widget_factory->widgets[$widget_name], 'WP_Widget')):
        $wp_class = 'WP_Widget_'.ucwords(strtolower($class));
        
        if (!is_a($wp_widget_factory->widgets[$wp_class], 'WP_Widget')):
            return '<p>'.sprintf(__("%s: Widget class not found. Make sure this widget exists and the class name is correct"),'<strong>'.$class.'</strong>').'</p>';
        else:
            $class = $wp_class;
        endif;
    endif;
    
    ob_start();
    the_widget($widget_name, array(), array('widget_id'=>'arbitrary-instance-Vieraslaskuriplugin_widget',
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '',
        'after_title' => ''
    ));
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
    
}
add_shortcode('vieraslaskuri','vieraslaskuri_plugin_widget_shortcode'); 

//Log user
add_action( 'init', 'vcp_log_user' );

function vcp_log_user() {
     
    if(!vcp_check_ip_exist($_SERVER['REMOTE_ADDR'])){

        global $wpdb;

        $table_name = $wpdb->prefix . 'vcp_log';

        $sqlQuery = "INSERT INTO $table_name VALUES (NULL,'".$_SERVER['REMOTE_ADDR']."',NULL)";
        $sqlQueryResult = $wpdb -> get_results($sqlQuery);
    }
}


function vcp_get_visit_count($interval='D')
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'vcp_log';
    
    if($interval == 'C')
    $condition = "`Time` > DATE_SUB(NOW(), INTERVAL 5 HOUR)";
    else if($interval == 'T')
    $condition = "1";
    elseif($interval == 'D')
    $condition = "DATE(`Time`)=DATE(NOW())";
    else if($interval == 'W')
    $condition = "WEEKOFYEAR(`Time`)=WEEKOFYEAR(NOW())";
    else if($interval == 'M')
    $condition = "MONTH(`Time`)=MONTH(NOW())";
    else if($interval == 'Y')
    $condition = "DATE(`Time`)=DATE(NOW() - INTERVAL 1 DAY)";
   

    $sql = "SELECT COUNT(*) FROM $table_name WHERE ".$condition;

    $count = $wpdb -> get_var($sql);
   
    return $count;
}

function vcp_check_ip_exist($ip)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'vcp_log';

    $sql = "SELECT COUNT(*) FROM $table_name WHERE IP='".$ip."' AND DATE(Time)='".date('Y-m-d')."'";

    $count = $wpdb -> get_var($sql);
   
    return $count;
}

global $vcp_db_version;
$vcp_db_version = ‘1’;

function vcp_install() {
    global $wpdb;
    global $vcp_db_version;

    $vcp_log_table = $wpdb->prefix . 'vcp_log';

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $sql = "
    CREATE TABLE IF NOT EXISTS $vcp_log_table 
    (
        `LogID` int(11) NOT NULL AUTO_INCREMENT,
        `IP` varchar(20) NOT NULL,
        `Time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
         PRIMARY KEY (`LogID`)
    );";

    dbDelta( $sql );

    add_option( 'vcp_db_version', $vcp_db_version );
}

function vcp_uninstall(){

    global $wpdb;
    $vcp_log_table = $wpdb->prefix."vcp_log";
    //Delete any options that's stored also?
    delete_option('vcp_db_version');
    $wpdb->query("DROP TABLE IF EXISTS $vcp_log_table");
}

register_activation_hook( __FILE__, 'vcp_install' );
register_deactivation_hook( __FILE__, 'vcp_uninstall' );
?>