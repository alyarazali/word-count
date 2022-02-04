<?php

/*
Plugin Name: Word Count Info
Description: Word Count Info you can add in Page and Post content.
Version: 1.1
Author: Alya Razali
Author URI: https://alyarazali.github.io/Portfolio/
Text Domain: wcipaldomain
Domain Path: /languages
*/

//Use class to use generic function names. No need for unique names.
class WordCountAndTimePlugin {
    //In PHP, any time you create a new instance from a class, php will automatically call the function inside the class that is named __construct.
    function __construct(){
        add_action('admin_menu', array($this, 'adminPage')); 
        add_action('admin_init', array($this, 'settings'));
        add_filter('the_content', array($this, 'ifWrap')); 
        
        add_action('init', array($this, 'languages'));
    }

    //For Translation with Loco Translate Plugin
    function languages(){
        load_plugin_textdomain('wcipaldomain', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    function ifWrap($content){
        if(is_main_query() AND (
            (is_page() AND get_option('wcp_locapage', '1')) OR 
            (is_single() AND get_option('wcp_locapost', '1'))
        ) AND
        (
            get_option('wcp_wordcount', '1') OR
            get_option('wcp_charcount', '1') OR
            get_option('wcp_readtime', '1')
        )){
            return $this -> createHTML($content); //Calling a method in our class 
        }

        return $content; //If not true just return default content.
    }

    /*-------------------------FRONTEND-----------------------*/
    function createHTML($content){
        $html = '<h3>' . esc_html(get_option('wcp_headline', 'Post Statistics (default title)')) . '</h3><p>';

            // Get word count once bacause both word count and read time will need it
            if(get_option('wcp_wordcount', '1') OR get_option('wcp_readtime', '1')){
                $wordCount = str_word_count(strip_tags($content)); //use strip tags so that we don't count individual HTML element
            }

            if(get_option('wcp_wordcount', '1')){
                $html .=  esc_html__('This post has' , 'wcipaldomain') . ' ' . $wordCount . ' ' . esc_html__('words' , 'wcipaldomain') . '.<br>';
            }
            if(get_option('wcp_charcount', '1')){
                $html .= 'This post has ' . strlen(strip_tags($content)) . ' characters.<br>';
            }
            if(get_option('wcp_readtime', '1')){
                //average adult reads, approximately two hundred and twenty five words per minute.
                $html .= 'This post will take about ' . round($wordCount/225) . ' minute(s) to read.<br>';
            }

        $html .= '</p>';

        //obviously this one equals zero but we want to make sure if it's coming from the DB
        //If = 0  add content to the beginning of the blog post content.
        if(get_option('wcp_location' , '0') == '0'){
            return $html . $content;
        } 

        //If not = 0 then at the end of blog post. no need else.
        return $content . $html;
    }

    /*-------------------------------SET PLUGIN SETTINGS--------------------------------*/
    function settings(){
        /*---------------------FIRST SECTION-------------------------*/
        add_settings_section(
            'wcp_first_section', //Group
            null, //Sub Title
            null, 
            'my-word-count-settings-page' //Page url
        );

        //Display Location
        add_settings_field('wcp_location', 'Display Location', array($this, 'locationHTML'), 'my-word-count-settings-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_location', array(
            'sanitize_callback' => array($this, 'mySanitizeLocation'), 
            'default' => '0'
        ));

        //Headline Text
        add_settings_field('wcp_headline', 'Headline Text', array($this, 'headlineHTML'), 'my-word-count-settings-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_headline', array(
            'sanitize_callback' => 'sanitize_text_field', 
            'default' => 'post Statistics'
        ));

        //-------Add 'array('theName' => 'wcp_wordcount')' at the end for multiple
        //Word count checkbox
        add_settings_field('wcp_wordcount', 'Word Count', array($this, 'checkboxHTML'), 'my-word-count-settings-page', 'wcp_first_section', array('theName' => 'wcp_wordcount'));
        register_setting('wordcountplugin', 'wcp_wordcount', array(
            'sanitize_callback' => 'sanitize_text_field', 
            'default' => '1'
        ));

        //Character count checkbox
        add_settings_field('wcp_charcount', 'Character Count', array($this, 'checkboxHTML'), 'my-word-count-settings-page', 'wcp_first_section', array('theName' => 'wcp_charcount'));
        register_setting('wordcountplugin', 'wcp_charcount', array(
            'sanitize_callback' => 'sanitize_text_field', 
            'default' => '1'
        ));

        //read time checkbox
        add_settings_field('wcp_readtime', 'Read Time', array($this, 'checkboxHTML'), 'my-word-count-settings-page', 'wcp_first_section', array('theName' => 'wcp_readtime'));
        register_setting('wordcountplugin', 'wcp_readtime', array(
            'sanitize_callback' => 'sanitize_text_field', 
            'default' => '1'
        ));


        /*---------------------SECOND SECTION-------------------------*/
        add_settings_section(
            'wcp_second_section', 
            '<br>Display on', 
            null, 
            'my-word-count-settings-page' 
        );

        //Display on Post option
        add_settings_field('wcp_locapost', 'Post', array($this, 'checkboxHTML'), 'my-word-count-settings-page', 'wcp_second_section', array('theName' => 'wcp_locapost'));
        //register_setting(name of group settings belong to, actual name for this setting, )
        register_setting('wordcountplugin', 'wcp_locapost', array(
            'sanitize_callback' => 'sanitize_text_field', //Standard WP function that santize user's input
            'default' => '1' //
        ));

        //Display on Page option
        add_settings_field('wcp_locapage', 'Page (Post Type)', array($this, 'checkboxHTML'), 'my-word-count-settings-page', 'wcp_second_section', array('theName' => 'wcp_locapage'));
        //register_setting(name of group settings belong to, actual name for this setting, )
        register_setting('wordcountplugin', 'wcp_locapage', array(
            'sanitize_callback' => 'sanitize_text_field', //Standard WP function that santize user's input
            'default' => '1' //
        ));
    }

    //To allow only 0 and 1 for location. so ppl cannot update DB by updating on Inspect Element with random numbers
    function mySanitizeLocation($input){
        //check to see if the value is not 0 and 1
        if($input != '0' AND $input != '1'){
            add_settings_error('wcp_location', 'wcp_location_error', 'Display location must be beginning or end');
            return get_option('wcp_location');
        }

        return $input;
    }

    /*---------------------------REUSABLE CHECKBOX FUNCTION-------------------------*/
    function checkboxHTML($args) { ?>
        <input type="checkbox" name="<?php echo $args['theName'] ?>" value="1" <?php checked(get_option($args['theName']), '1') ?>>
    <?php }


    function headlineHTML(){ ?>
        <input type="text" name="wcp_headline" value="<?php echo esc_attr(get_option('wcp_headline')); ?>">
    <?php }
    

    function locationHTML(){ ?>
        <select name="wcp_location">
            <!-- PHP code update the selected one -->
            <option value="0" <?php selected(get_option('wcp_location'), '0'); ?>>Beginning of content</option>
            <option value="1" <?php selected(get_option('wcp_location'), '1'); ?>>End of content</option>
        </select>
    <?php }

    /*---------------------------ADD PLUGIN SETTINGS TO ADMIN DASHBOARD-------------------------*/
    function adminPage(){
        //__(a , b) = (Original Title, Text Domain) | This of tran;sation purposes
        //add_options_page(Tab title, Title page in menu, type of permission: admin, page slug url - must be unique, output the html content)
        add_options_page('Word Count Settings', esc_html__('Word Count' , 'wcipaldomain'), 'manage_options', 'my-word-count-settings-page', array($this, 'ourHTML'));
    }
    
    /*-------------------------ADD PLUGIN SETTINGS TO PLUGIN PAGE DASHBOARD-----------------------*/
    function ourHTML(){ ?>
        <div class="wrap">
            <h1>Word Count Settings</h1>
            <form action="options.php" method="POST">
                <?php
                    settings_fields('wordcountplugin');
                    do_settings_sections('my-word-count-settings-page');
                    submit_button();
                ?>
            </form>
        </div>
    <?php }
}

//If any other plugin need to remove our action or filter, they can access to our function and methods that live inside our class.
//They can use this variable and look inside it.
$wordcountandtimeplugin = new WordCountAndTimePlugin(); 



