<?php
/*
  Plugin Name:   Ratings FilmAffinity Widget
  Plugin URI:    http://giltesa.com
  Description:   <em>Ratings FilmAffinity Widget</em> shows the cover and information about the latest movies as voted by you on page FilmAffinity.
  Version:       0.45
  Date:          27/04/2013
  Author:        Alberto Gil Tesa
  Author URI:    http://giltesa.com/sobre-mi/
  License:       GPL2
  License URI:   http://www.gnu.org/licenses/gpl-2.0.html
  Text Domain:   RFW
*/

/*
  Copyright 2013 Alberto Gil Tesa (email : developer [at] giltesa [dot] com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
    // Path to the plugin directory:
    define( "PLUGIN_URL_PATH",   plugin_dir_url( __FILE__ ) );
    define( "PLUGIN_LOCAL_PATH", dirname(__FILE__) . '/' );

    // Short name of the plugin:
    define( "PLUGIN_NAME_SHORT", "RFW" );

    // Libraries.
    require_once("includes/lib/simple_html_dom.php");
    require_once("includes/Film.php");
    require_once("includes/Filmaffinity.php");



    /**
     * Load the language file with all the text strings.
     */
    load_plugin_textdomain( 'RFW', false, dirname(plugin_basename( __FILE__ )) . '/languages/' );



    /**
     * Added the two style sheets to WP plugin, show each style sheet as appropriate.
     */
    function adminStyle()
    {
        wp_register_style( 'rfw-style', PLUGIN_URL_PATH."css/admin-styles.css", false, false, 'all' );
        wp_enqueue_style( 'rfw-style' );
    }
    function widgetStyle()
    {
        wp_register_style( 'rfw-style', PLUGIN_URL_PATH."css/widget-styles.css", false, false, 'all' );
        wp_enqueue_style( 'rfw-style' );
    }
    add_action( 'admin_enqueue_scripts', 'adminStyle' );
    add_action( 'wp_enqueue_scripts', 'widgetStyle' );



     // Add the widget to WP.
    add_action( 'widgets_init', create_function( '', 'register_widget( "RatingsFilmAffinityWidget" );' ) );

    // Indicates methods of activation, deactivation and uninstall the widget.
    register_activation_hook( __FILE__, array( 'RatingsFilmAffinityWidget', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'RatingsFilmAffinityWidget', 'deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'RatingsFilmAffinityWidget', 'uninstall' ) );



    /**
     * Class "RatingsFilmAffinityWidget" extends "WP_Widget" and contains methods for
     * initializing, updating data, display and control panel widget.
     */
    class RatingsFilmAffinityWidget extends WP_Widget
    {
        /**
         * widget actual processes
         */
        public function __construct()
        {
            parent::__construct(
                'RatingsFilmAffinityWidget',               // Base ID
                __('Ratings FilmAffinity Widget', 'RFW' ), // Name
                array(                                     // Args
                    'description' => __( 'Displays the latest films at FilmAffinity views.', 'RFW' )
                )
            );
        }



        /**
         * Processes widget options to be saved.
         *
         * @param array $new_instance Values just sent to be saved.
         * @param array $old_instance Previously saved values from database.
         * @return array Updated safe values to be saved.
         */
        public function update( $new_instance, $old_instance )
        {
            // If you have changed your important data, old data is deleted from the database (this will force the update):
            if(  strcmp($old_instance['id'], $new_instance['id']) || strcmp($old_instance['language'], $new_instance['language']) || strcmp($old_instance['viewing_mode'], $new_instance['viewing_mode']) || (strcmp($old_instance['show_more_info'], $new_instance['show_more_info']) && !strcmp($new_instance['show_more_info'], "on"))  )
            {
                global $wpdb;
                $wpdb->query( "DELETE FROM ".$wpdb->prefix . PLUGIN_NAME_SHORT." WHERE id_widget = ".$this->number );
            }

            $instance = $old_instance;
            $instance['title']                  = strip_tags( $new_instance['title'] );
            $instance['id']                     = strip_tags( $new_instance['id'] );
            $instance['language']               = strip_tags( $new_instance['language'] );
            $instance['viewing_mode']           = strip_tags( $new_instance['viewing_mode'] );
            $instance['update_time']            = strip_tags( $new_instance['update_time'] );
            $instance['show_user']              = strip_tags( $new_instance['show_user'] );
            $instance['show_avg_votes']         = strip_tags( $new_instance['show_avg_votes'] );
            $instance['movies_rated']           = strip_tags( $new_instance['movies_rated'] );
            $instance['forcing_big_thumbnails'] = strip_tags( $new_instance['forcing_big_thumbnails'] );
            $instance['show_punctuation']       = strip_tags( $new_instance['show_punctuation'] );
            $instance['show_more_info']         = strip_tags( $new_instance['show_more_info'] );
            return $instance;
        }



        /**
         * Outputs the content of the widget
         *
         * @see WP_Widget::widget()
         * @param array $args     Widget arguments.
         * @param array $instance Saved values from database.
         */
        public function widget( $args, $instance )
        {
            global $wpdb;
            extract( $args );

            $idWidget             = $this->number;
            $tableName            = $wpdb->prefix . PLUGIN_NAME_SHORT;
            $title                = apply_filters( 'widget_title', $instance['title'] );
            $id                   = $instance['id'];
            $language             = $instance['language'];
            $viewing_mode         = $instance['viewing_mode'];
            $numRowFilms          = substr($viewing_mode, 0, 1);
            $numColFilms          = substr($viewing_mode, 2, 1);
            $numFilms             = $numRowFilms * $numColFilms;
            $updateTime           = $instance['update_time'];
            $showUser             = ($instance['show_user'] == "on") ? true : false;
            $showAvgVotes         = ($instance['show_avg_votes'] == "on") ? true : false;
            $showMoviesRated      = ($instance['movies_rated'] == "on") ? true : false;
            $forcingBigThumbnails = false; //($instance['forcing_big_thumbnails'] == "on") ? true : false;
            $showPunctuation      = ($instance['show_punctuation'] == "on") ? true : false;
            $showMoreInfo         = ($instance['show_more_info'] == "on") ? true : false;


            /* *** OBTAINING THE DATA *** */


            // Try to get data from the widget of the database:
            $dataWidget = $wpdb->get_row("SELECT * FROM $tableName WHERE id_widget = $idWidget");

            // If no data is entered for the first time:
            if( $dataWidget == null )
            {
                $filmaffinity = new Filmaffinity($id, $language);
                $movies = $filmaffinity->getFilms($numFilms, $showMoreInfo, 'w'.$idWidget.'_' );

                $rows_affected = $wpdb->insert(
                    $tableName,
                    array(
                        'id_widget'    => $idWidget,
                        'last_updated' => date('Y-m-d G:i:s'),
                        'user'         => $filmaffinity->getUser(),
                        'avg_votes'    => $filmaffinity->getAvgVotes(),
                        'movies_rated' => $filmaffinity->getMoviesRated(),
                        'array_films'  => serialize($movies)
                    )
                );
                $dataWidget = $wpdb->get_row("SELECT * FROM $tableName WHERE id_widget = $idWidget");
            }
            else
            {
               // Otherwise, it is checked if it is necessary to update the data
                $elapsedTime = strtotime('now') - strtotime($dataWidget->last_updated);

                if( $elapsedTime >= $updateTime )
                {
                    $filmaffinity = new Filmaffinity($id, $language);
                    $movies = $filmaffinity->getFilms($numFilms, $showMoreInfo, 'w'.$idWidget.'_' );

                    $rows_affected = $wpdb->update(
                        $tableName,
                        array(
                            'last_updated' => date('Y-m-d G:i:s'),
                            'user'         => $filmaffinity->getUser(),
                            'avg_votes'    => $filmaffinity->getAvgVotes(),
                            'movies_rated' => $filmaffinity->getMoviesRated(),
                            'array_films'  => serialize($movies)
                        ),
                        array( 'id_widget' => $idWidget )
                    );
                    $dataWidget = $wpdb->get_row("SELECT * FROM $tableName WHERE id_widget = $idWidget");
                }
            }


            /* *** PRESENTATION OF DATA *** */


            echo $before_widget;

            if( !empty($title) )
                echo $before_title . $title . $after_title;

            $films = unserialize($dataWidget->array_films);

            ?>
            <div class='user-info'>
                <?php if($showUser){ echo "<span id='user' title='". __('Profile at FilmAffinity','RFW') ."' ><a href='http://www.filmaffinity.com/$language/userratings.php?user_id=$id' target='_blank'>$dataWidget->user</a></span>"; } ?>
                <?php if($showAvgVotes){ echo "<span id='avg' title='". __('His/her average rating','RFW') ."' >$dataWidget->avg_votes</span>"; } ?>
                <?php if($showMoviesRated){ echo "<span id='rated' title='". __('Total number of films voted','RFW') ."' >$dataWidget->movies_rated</span>"; } ?>
            </div>
            <?php

            echo "<div class='films'>";
            for( $i=0 ; $i< $numFilms && $i< sizeof($films) ; $i++ )
            {
            ?>
                <dl class='item <?php echo "t$viewing_mode" ?>' >
                    <dt class="popup" >
                        <a target='_blank' href='<?php echo $films[$i]->getLink() ?>'>
                            <img src='<?php echo (( $numColFilms < 3 || $forcingBigThumbnails) ? $films[$i]->getImageMedium() : $films[$i]->getImageSmall());?>' alt="<?php echo $films[$i]->getTitle() ?>" <?php if(!$showMoreInfo || !strcmp($films[$i]->getSynopsis(), "") ){ echo "title='". $films[$i]->getTitle() ."'"; } ?> />
                        </a>
                        <?php if($showMoreInfo && strcmp($films[$i]->getSynopsis(), "") ){ ?>
                        <div class="content">
                            <h1><?php echo $films[$i]->getTitle() ?></h1>
                            <img src='<?php echo $films[$i]->getImageMedium(); ?>' />
                            <p><?php echo $films[$i]->getSynopsis() ?></p>
                        </div>
                        <?php } ?>
                    </dt>
                    <?php if($showPunctuation) echo "<dd class='note'>".$films[$i]->getNote()."</dd>"; ?>
                </dl>
            <?php
            }
            echo "</div>";


            echo $after_widget;

        } // End widget method.



        /**
         * Outputs the options form on admin
         *
         * @see WP_Widget::form()
         * @param array $instance Previously saved values from database.
         */
        public function form( $instance )
        {
            $title                = isset($instance['title'])                  ? $instance['title']                  : '';
            $id                   = isset($instance['id'])                     ? $instance['id']                     : '';
            $language             = isset($instance['language'])               ? $instance['language']               : '';
            $viewing_mode         = isset($instance['viewing_mode'])           ? $instance['viewing_mode']           : '';
            $updateTime           = isset($instance['update_time'])            ? $instance['update_time']            : '';
            $showUser             = isset($instance['show_user'])              ? $instance['show_user']              : '';
            $showAvgVotes         = isset($instance['show_avg_votes'])         ? $instance['show_avg_votes']         : '';
            $showMoviesRated      = isset($instance['movies_rated'])           ? $instance['movies_rated']           : '';
            $forcingBigThumbnails = isset($instance['forcing_big_thumbnails']) ? $instance['forcing_big_thumbnails'] : '';
            $showPunctuation      = isset($instance['show_punctuation'])       ? $instance['show_punctuation']       : '';
            $showMoreInfo         = isset($instance['show_more_info'])         ? $instance['show_more_info']         : '';

            ?>
                <p>
                    <label for="<?php echo $this->get_field_id('title'); ?>" ><?php _e('Widget Title:','RFW') ?></label><br/>
                    <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" class="widefat" value="<?php echo esc_attr($title); ?>" />
                </p>
                <p>
                <label for="<?php echo $this->get_field_id('id'); ?>"><?php _e('FilmAffinity user ID:','RFW') ?></label><br/>
                    <input type="text" id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" class="widefat rfwid" value="<?php echo esc_attr($id); ?>" />

                    <img src="<?php echo PLUGIN_URL_PATH . "img/question.png"; ?>" />

                    <div class="popup">
                        <div class="content">
                            <p><?php _e('In this field you must enter the number or user ID of FilmAffinity.<br/>Este this hidden number, to locate it needs to go to the menu <em>My Ratings</em> then any of the rated movies must be pressed in <em>Share</em> and user number in the URL.','RFW') ?></p>
                            <a target="_blank" href="<?php echo PLUGIN_URL_PATH . "img/instructions_id.png"; ?>">
                                <img src="<?php echo PLUGIN_URL_PATH . "img/instructions_id_m.png"; ?>" />
                            </a>
                        </div>
                    </div>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id('language'); ?>"><?php _e('FilmAffinity language:','RFW') ?></label><br/>
                    <select id="<?php echo $this->get_field_id('language'); ?>" name="<?php echo $this->get_field_name('language'); ?>" class="rfwselect" >
                        <option value="es" <?php if(!strcmp($language,"es")) echo "selected='selected'" ?>><?php _e('Spanish','RFW') ?></option>
                        <option value="en" <?php if(!strcmp($language,"en")) echo "selected='selected'" ?>><?php _e('English','RFW') ?></option>
                    </select>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id('viewing_mode'); ?>"><?php _e('Display mode <em>(rows x columns)</em>:','RFW') ?></label><br/>
                    <select id="<?php echo $this->get_field_id('viewing_mode'); ?>" name="<?php echo $this->get_field_name('viewing_mode'); ?>" class="rfwselect" >
                        <option value="1x1" <?php if(!strcmp($viewing_mode,"1x1")) echo "selected='selected'" ?>>1x1</option>
                        <option value="1x2" <?php if(!strcmp($viewing_mode,"1x2")) echo "selected='selected'" ?>>1x2</option>
                        <option value="1x3" <?php if(!strcmp($viewing_mode,"1x3")) echo "selected='selected'" ?>>1x3</option>
                        <option value="2x1" <?php if(!strcmp($viewing_mode,"2x1")) echo "selected='selected'" ?>>2x1</option>
                        <option value="2x2" <?php if(!strcmp($viewing_mode,"2x2")) echo "selected='selected'" ?>>2x2</option>
                        <option value="2x3" <?php if(!strcmp($viewing_mode,"2x3")) echo "selected='selected'" ?>>2x3</option>
                    </select>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id('update_time'); ?>"><?php _e('Update information each:','RFW') ?></label><br/>
                    <select id="<?php echo $this->get_field_id('update_time'); ?>" name="<?php echo $this->get_field_name('update_time'); ?>" class="rfwselect" >
                        <option value="43200" <?php if($updateTime == 43200) echo "selected='selected'" ?>><?php _e('Every twelve hours','RFW') ?></option>
                        <option value="86400" <?php if($updateTime == 86400) echo "selected='selected'" ?>><?php _e('Every day','RFW') ?></option>
                        <option value="604800" <?php if($updateTime == 604800) echo "selected='selected'" ?>><?php _e('Every week','RFW') ?></option>
                    </select>
                </p>
                <p>
                    <input type="checkbox" id="<?php echo $this->get_field_id('show_user'); ?>" name="<?php echo $this->get_field_name('show_user'); ?>" class="checkbox" <?php if($showUser != "") echo "checked='checked'" ?> />
                    <label for="<?php echo $this->get_field_id('show_user'); ?>"><?php _e('Show user','RFW') ?></label>
                    <br>
                    <input type="checkbox" id="<?php echo $this->get_field_id('show_avg_votes'); ?>" name="<?php echo $this->get_field_name('show_avg_votes'); ?>" class="checkbox" <?php if($showAvgVotes != "") echo "checked='checked'" ?> />
                    <label for="<?php echo $this->get_field_id('show_avg_votes'); ?>"><?php _e('Show the average votes','RFW') ?></label>
                    <br>
                    <input type="checkbox" id="<?php echo $this->get_field_id('movies_rated'); ?>" name="<?php echo $this->get_field_name('movies_rated'); ?>" class="checkbox" <?php if($showMoviesRated != "") echo "checked='checked'" ?> />
                    <label for="<?php echo $this->get_field_id('movies_rated'); ?>"><?php _e('Show the number of films voted','RFW') ?></label>
                    <br>
                    <!--
                    <input type="checkbox" id="<?php echo $this->get_field_id('forcing_big_thumbnails'); ?>" name="<?php echo $this->get_field_name('forcing_big_thumbnails'); ?>" class="checkbox" <?php if($forcingBigThumbnails != "") echo "checked='checked'" ?> />
                    <label for="<?php echo $this->get_field_id('forcing_big_thumbnails'); ?>"><?php _e('Forcing big thumbnails','RFW') ?></label>
                    <br>
                    -->
                    <input type="checkbox" id="<?php echo $this->get_field_id('show_punctuation'); ?>" name="<?php echo $this->get_field_name('show_punctuation'); ?>" class="checkbox" <?php if($showPunctuation != "") echo "checked='checked'" ?> />
                    <label for="<?php echo $this->get_field_id('show_punctuation'); ?>"><?php _e('Show movies note','RFW') ?></label>
                    <br>
                    <input type="checkbox" id="<?php echo $this->get_field_id('show_more_info'); ?>" name="<?php echo $this->get_field_name('show_more_info'); ?>" class="checkbox" <?php if($showMoreInfo != "") echo "checked='checked'" ?> />
                    <label for="<?php echo $this->get_field_id('show_more_info'); ?>" title="<?php _e('It will show the title and description of films hovering above.','RFW') ?>"><?php _e('Show description of movies','RFW') ?></label>
                </p>
            <?php

        } // End form method.



        /**
         * When activated the plugin creates a table in the database that stores all information of the films.
         */
        public function activate()
        {
            global $wpdb;

            // The name of the table is composed by the WP prefix and the short name of the plugin.
            $tableName = $wpdb->prefix . PLUGIN_NAME_SHORT;
            
            // Removes the table:
            $wpdb->query( "DROP TABLE IF EXISTS $tableName;" );

            // Check if the table exists, and otherwise creates the table:
            if( $wpdb->get_var("SHOW TABLES LIKE '$tableName'") !== $tableName )
            {
               $sql = "CREATE TABLE $tableName (
                        id_widget     TINYINT UNSIGNED PRIMARY KEY,
                        last_updated  DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                        user          VARCHAR(20),
                        avg_votes     VARCHAR(4),
                        movies_rated  VARCHAR(6),
                        array_films   LONGTEXT NOT NULL );";

               require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
               dbDelta($sql);
            }
        }



        /**
         * To disable the plugin is deleted the database table.
         */
        public function deactivate()
        {
            global $wpdb;
            $tableName = $wpdb->prefix . PLUGIN_NAME_SHORT;

            if( $wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName )
            {
                $sql  = "DROP TABLE IF EXISTS $tableName;";
                $wpdb->query( $sql );
            }
        }



        /**
         * Uninstalling the plugin erases all your settings.
         */
        public function uninstall()
        {
            delete_option('widget_ratingsfilmaffinitywidget');
        }



    } // End RatingsFilmAffinityWidget class.



?>