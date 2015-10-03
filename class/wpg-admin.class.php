<?php
/**
 * WP-Glossary Admin
 */
class WPG_Admin{
    static $base;
    static $base_url;

    public function __construct( $plugin_base ) {
        self::$base     = $plugin_base . '/class';
        self::$base_url = plugins_url( '', dirname(__FILE__) );


        add_action( 'admin_menu',                 array(&$this, 'options_submenu') );

        add_action( 'wp_ajax_wpg_update_options', array(&$this, 'update_options') );

        add_action( 'admin_head',                 array(&$this, 'add_tinymce_dropdown_hooks') );

        add_action( 'admin_init',                 array(&$this, 'setup_localixed_dropdown_values') );

    }

    static function base() {
        return self::$base;
    }
    static function base_url() {
        return self::$base_url;
    }

    public function add_tinymce_dropdown_hooks() {
        add_filter( 'mce_external_plugins', array(&$this, 'tinymce_add_dropdown_plugin') );
        add_filter( 'mce_buttons',          array(&$this, 'tinymce_add_dropdown_button') );
    }
    public function tinymce_add_dropdown_plugin( $plugin_array ){
        $plugin_array['wpglossary'] = $this->base_url() . '/js/tinymce-wpglossary-dropdown.js';
        return $plugin_array;
    }
    public function tinymce_add_dropdown_button( $buttons ){
        array_push( $buttons, 'wpglossary' );
        return $buttons;
    }

    public function setup_localixed_dropdown_values(){
        $args = array(
            'post_type'   => 'glossary',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby'     => 'title',
            'order'       => 'ASC',
        );
        $glossaryposts = get_posts( $args );
        $glossaryterms = array();
        foreach( $glossaryposts as $glossary ):
        $glossaryterms[$glossary->post_title] = "[glossary id='{$glossary->ID}' slug='{$glossary->post_name}' /]";
        endforeach;

        wp_localize_script( 'jquery', 'WPG', array(
            'tinymce_dropdown' => $glossaryterms,
        ) );
    }


    public function options_submenu(){
        $slug             = 'glossary';
        // Add menu page (capture page for adding admin style and javascript
        $glossary_options = add_submenu_page( 
            "edit.php?post_type=$slug", 
            __( 'Glossary Options', 'wp-glossary' ), 
            __( 'Glossary Options', 'wp-glossary' ), 
            'manage_options', 
            'glossary-options', 
            array($this, 'options')
        );
    }

    public function options(){
        $ajax         = admin_url( 'admin-ajax.php' );
        $options      = get_option( 'wp_glossary', array() );
        $tooltips     = isset( $options['tooltips'] )     ? $options['tooltips']     : 'excerpt';
        $alphaarchive = isset( $options['alphaarchive'] ) ? $options['alphaarchive'] : 'standard';
        $termtype     = isset( $options['termtype'] )     ? $options['termtype']     : 'glossary';
        $qtipstyle    = isset( $options['qtipstyle'] )    ? $options['qtipstyle']    : 'cream';
        $termlinkopt  = isset( $options['termlinkopt'] )  ? $options['termlinkopt']  : 'standard';
        $termusage    = isset( $options['termusage'] )    ? $options['termusage']    : 'on';
        $qtiptrigger  = isset( $options['qtiptrigger'] )  ? $options['qtiptrigger']  : 'hover';

        // Tooptip DD
        $ttddoptions = array(
            'full' => array(
                'title' => __('Full', 'wp-glossary'),
                'attrs' => array('title'=>__('Display full post content', 'wp-glossary'))
            ),
            'excerpt' => array(
                'title' => __('Excerpt', 'wp-glossary'),
                'attrs' => array('title'=>__('Display shorter excerpt content', 'wp-glossary'))
            ), 
            'off' => array(
                'title' => __('Off', 'wp-glossary'),
                'attrs' => array('title'=>__('Do not display tooltip at all', 'wp-glossary'))
            ),
        );
        $tooltipdropdown = tcb_wpg_build_dropdown( 'tooltips', array(
            'selected' => $tooltips,
            'options'  => $ttddoptions,
        ) );

        // Alpha Arrhive DD
        $aaddoptions = array(
            'alphabet' => array('title'=>__('Alphabetical', 'wp-glossary'), 'attrs'=>array('title'=>__('Display glossary archive alphabetically', 'wp-glossary'))),
            'standard' => array('title'=>__('Standard', 'wp-glossary'),     'attrs'=>array('title'=>__('No filtering, display as standard archive', 'wp-glossary'))),
        );
        $archivedropdown = tcb_wpg_build_dropdown( 'alphaarchive', array(
            'selected' => $alphaarchive,
            'options'  => $aaddoptions,
        ) );

        // qTipd syle options
        $qtipdropdown = tcb_wpg_build_dropdown( 'qtipstyle', array(
            'selected' => $qtipstyle,
            'options'  => array(
                'cream'     => __('Cream',      'wp-glossary'), 
                'dark'      => __('Dark',       'wp-glossary'), 
                'green'     => __('Green',      'wp-glossary'), 
                'light'     => __('Light',      'wp-glossary'), 
                'red'       => __('Red',        'wp-glossary'), 
                'blue'      => __('Blue',       'wp-glossary'),
                'plain'     => __('Plain',      'wp-glossary'),
                'bootstrap' => __('Bootstrap',  'wp-glossary'),
                'youtube'   => __('YouTube',    'wp-glossary'),
                'tipsy'     => __('Tipsy',      'wp-glossary'),
            ),
        ));

        $qtiptriggerdropdown = tcb_wpg_build_dropdown( 'qtiptrigger', array(
            'selected' => $qtiptrigger,
            'options'  => array(
                'hover' => array('title'=>__('Hover', 'wp-glossary'), 'attrs'=>array('title'=>__('On mouseover (hover)', 'wp-glossary'))),
                'click' => array('title'=>__('Click', 'wp-glossary'), 'attrs'=>array('title'=>__('On click',             'wp-glossary'))),
                'responsive' => array('title'=>__('Responsive', 'wp-glossary'), 'attrs'=>array('title'=>__('Hover (on computer) and click (touch devices)',             'wp-glossary'))),
            ),
        ));

        // Term Link HREF target
        $termlinkoptdropdown = tcb_wpg_build_dropdown( 'termlinkopt', array(
            'selected' => $termlinkopt,
            'options'  => array(
                'standard' => array('title'=>__('Normal',  'wp-glossary'), 'attrs'=>array('title'=>__('Normal link with no modifications', 'wp-glossary'))),
                'none'     => array('title'=>__('No link', 'wp-glossary'), 'attrs'=>array('title'=>__("Don't link to term",                'wp-glossary'))),
                'blank'    => array('title'=>__('New tab', 'wp-glossary'), 'attrs'=>array('title'=>__("Always open in a new tab",          'wp-glossary'))),
            ),
        ));

        // Term usage
        $termusagedd = tcb_wpg_build_dropdown( 'termusage', array(
            'selected' => $termusage,
            'options'  => array(
                'on'  => __('On',  'wp-glossary'),
                'off' => __('Off', 'wp-glossary'),
            ),
        ) );

?>
<div class="wrap">
    <div id="wp-glossary-options" class="meta-box meta-box-50" style="width: 50%;">
        <div class="meta-box-inside admin-help">
            <div class="icon32" id="icon-options-general">
                <br>
            </div>
            <h2><?php _e('WP Glossary Options', 'wp-glossary'); ?></h2>
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div class="postbox-container" style="width:98%">
                        <div id="normal-sortables" class="meta-box-sortables ui-sortable">

                            <form action="<?php echo $ajax; ?>" method="post" class="simpleajaxform" data-target="update-response">

                                <div id="wpglossary_options_1" class="postbox">
                                    <h3 class="handle"><span>Term Options</span></h3>
                                    <div class="inside">
                                        <p><?php _e('Archive:', 'wp-glossary'); echo "&nbsp;"; echo "{$archivedropdown}" ?></p>
                                        <p><?php _e('Term link:', 'wp-glossary'); echo "&nbsp;"; echo "{$termlinkoptdropdown}" ?></p>
                                        <p><?php _e('Glossary URL:', 'wp-glossary'); echo "&nbsp;"; ?><input type="text" value="<?php echo $termtype; ?>" name="termtype"/></p>
                                    </div>
                                </div>

                                <div id="wpglossary_options_2" class="postbox">
                                    <h3 class="handle"><span>qTip2 Tooltip Options</span></h3>
                                    <div class="inside">
                                        <p>WP Glossary uses the jQuery based <a href="http://qtip2.com/">qTip2</a> library for tooltips</p>
                                        <p><?php _e('Tooltip Content:', 'wp-glossary'); echo "{$tooltipdropdown}" ?></p>
                                        <p><?php _e('Tooltip Style (qTip):', 'wp-glossary');  echo "{$qtipdropdown}" ?></p>
                                        <p><?php _e('Tooltip activation:', 'wp-glossary');  echo "{$qtiptriggerdropdown}" ?></p>
                                    </div>
                                </div>


                                <div id="wpglossary_options_3" class="postbox">
                                    <h3 class="handle"><span>Experimental Options</span></h3>
                                    <div class="inside">
                                        <p>Do not rely on these at all, I am experimenting with them</p>
                                        <p><?php _e('Term usage:', 'wp-glossary');  echo "{$termusagedd}" ?></p>
                                    </div>
                                </div>
                                <p>
                                    <input type="hidden" name="action" value="wpg_update_options"/>
                                    <input type="submit" name="submit" class="alignleft button-primary" value="<?php _e('Update Glossary Options', 'wp-glossary'); ?>"/>
                                </p>

                            </form>
                            <div id="update-response" class="clear confweb-update"></div>
                        </div>
                    </div>
                </div>
                <?php
    }

    public function update_options(){
        $defaults = array(
            'tooltips'     => 'excerpt',
            'alphaarchive' => 'standard',
            'termtype'     => 'glossary',
            'qtipstyle'    => 'cream',
            'termlinkopt'  => 'standard',
            'termusage'    => 'on',
            'qtiptrigger'  => 'hover',
        );

        $glossary_options = get_option( 'wp_glossary', $defaults );

        $glossary_options_old = $glossary_options;
        foreach( $defaults as $key => $default ){
            $value                  = $_POST[$key] ? $_POST[$key] : $default;
            $glossary_options[$key] = $value;
        }

        $outtxt = "";


        if($glossary_options_old["termtype"] != $glossary_options["termtype"]){
            $glossary_options["needflush"] = true;
            flush_rewrite_rules(false);
            $outtxt .= "<p>" . __( 'Rewrite rule flushed', 'wp-glossary' ) . "</p>";
        }
        update_option( 'wp_glossary', $glossary_options );

        $outtxt .='<p>' . __('Glossary options updated', 'wp-glossary') . '</p>' ;
        die( $outtxt );
    }
}
