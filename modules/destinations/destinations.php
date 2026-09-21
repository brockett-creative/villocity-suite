<?php
/**
 * Module Name: VilloCity Destinations & Tourism
 * Module URI:  https://villocity.org
 * Description: Regional tourism, historical sites, and attractions directory with interactive Leaflet.js maps, county filters, and cross-promotional "Nearby Sites" engines.
 * Version:     1.3.1
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'VOC_DESTINATIONS_DIR' ) ) {
    define( 'VOC_DESTINATIONS_DIR', plugin_dir_path( __FILE__ ) );
}

// ==========================================
// 1. REGISTER CPT & TAXONOMIES
// ==========================================
if ( ! function_exists( 'voc_register_destinations_cpt' ) ) {
    add_action( 'init', 'voc_register_destinations_cpt', 5 );
    function voc_register_destinations_cpt() {
        register_post_type( 'voc_destination', array(
            'labels' => array(
                'name'               => 'Destinations',
                'singular_name'      => 'Destination',
                'add_new'            => 'Add New Destination',
                'add_new_item'       => 'Add New Destination',
                'edit_item'          => 'Edit Destination',
                'all_items'          => 'All Destinations',
                'menu_name'          => 'Destinations'
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'has_archive'        => false,
            'rewrite'            => array( 'slug' => 'destination', 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        ) );

        // County Taxonomy
        register_taxonomy( 'voc_destination_county', array( 'voc_destination' ), array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'          => 'Counties',
                'singular_name' => 'County',
                'menu_name'      => 'Counties',
            ),
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'county' ),
        ));

        // Theme / Historical Era Taxonomy
        register_taxonomy( 'voc_destination_theme', array( 'voc_destination' ), array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'          => 'Themes & Eras',
                'singular_name' => 'Theme / Era',
                'menu_name'      => 'Themes & Eras',
            ),
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'destination-theme' ),
        ));
    }
}

// Flush Rewrite Rules Transients Engine
if ( ! function_exists( 'villocity_destinations_flush_rewrite' ) ) {
    add_action('admin_init', 'villocity_destinations_flush_rewrite');
    function villocity_destinations_flush_rewrite() {
        if (!get_transient('villocity_destinations_flush')) {
            flush_rewrite_rules();
            set_transient('villocity_destinations_flush', true, 86400);
        }
    }
}

// ==========================================
// 2. SAFE DUMMY DATA GENERATOR
// ==========================================
if ( ! function_exists( 'voc_generate_destination_dummy_data' ) ) {
    function voc_generate_destination_dummy_data() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $counties = array( 'Herkimer County', 'Oneida County', 'Montgomery County' );
        foreach ( $counties as $county ) {
            if ( ! term_exists( $county, 'voc_destination_county' ) ) {
                wp_insert_term( $county, 'voc_destination_county' );
            }
        }

        $themes = array( 'Revolutionary War', 'Erie Canal', 'Colonial History', 'Industrial Heritage' );
        foreach ( $themes as $theme ) {
            if ( ! term_exists( $theme, 'voc_destination_theme' ) ) {
                wp_insert_term( $theme, 'voc_destination_theme' );
            }
        }

        $destinations = array(
            array(
                'title'     => 'Fort Stanwix National Monument',
                'excerpt'   => 'A reconstructed 18th-century fort that played a pivotal role during the American Revolutionary War and Siege of 1777.',
                'content'   => 'Fort Stanwix guarded a vital portage location known as the Oneida Carry during the French and Indian War and American Revolution. Visitors can explore living history exhibits, museum collections, and guided park ranger tours.',
                'county'    => 'Oneida County',
                'theme'     => 'Revolutionary War',
                'address'   => '112 E Park St, Rome, NY 13440',
                'lat'       => '43.2104',
                'lng'       => '-75.4557',
                'phone'     => '(315) 338-7730',
                'website'   => 'https://www.nps.gov/fost/index.htm',
                'hours'     => 'Open Daily: 9:00 AM - 5:00 PM',
                'admission' => 'Free Admission',
                'featured'  => 'yes',
            ),
            array(
                'title'     => 'Herkimer Home State Historic Site',
                'excerpt'   => 'The historic estate and homestead of General Nicholas Herkimer, hero of the Battle of Oriskany.',
                'content'   => 'Located along the Mohawk River, Herkimer Home preserves the 1764 Georgian-style mansion of General Nicholas Herkimer. The site features beautiful grounds, a historic cemetery, and seasonal living history demonstrations.',
                'county'    => 'Herkimer County',
                'theme'     => 'Colonial History',
                'address'   => '200 State Route 169, Little Falls, NY 13365',
                'lat'       => '43.0232',
                'lng'       => '-74.8210',
                'phone'     => '(315) 823-3192',
                'website'   => 'https://parks.ny.gov/historic-sites/herkimerhome/',
                'hours'     => 'May - Oct: Wed-Sun 10:00 AM - 4:00 PM',
                'admission' => '$4 Adults, Kids 12 & Under Free',
                'featured'  => 'yes',
            ),
            array(
                'title'     => 'Oriskany Battlefield State Historic Site',
                'excerpt'   => 'Site of one of the bloodiest encounters of the American Revolutionary War in August 1777.',
                'content'   => 'On August 6, 1777, American Patriots and their Oneida allies fought Loyalist forces and Native allies in a fierce battle. Today, visitors can walk the battlefield trails and view the historic battle monument.',
                'county'    => 'Oneida County',
                'theme'     => 'Revolutionary War',
                'address'   => '7801 NY-69, Oriskany, NY 13424',
                'lat'       => '43.1818',
                'lng'       => '-75.3619',
                'phone'     => '(315) 768-6441',
                'website'   => 'https://parks.ny.gov/historic-sites/oriskanybattlefield/',
                'hours'     => 'Daily: Sunrise to Sunset',
                'admission' => 'Free Entry',
                'featured'  => 'no',
            ),
            array(
                'title'     => 'Erie Canal Lock 17 & Moss Island',
                'excerpt'   => 'Home to the highest single-lift lock on the Erie Canal and striking glacial potholes.',
                'content'   => 'Lock 17 in Little Falls lifts boats over 40 feet in a single chamber. Adjacent Moss Island features incredible geological rock formations, rock climbing areas, and scenic river trails.',
                'county'    => 'Herkimer County',
                'theme'     => 'Erie Canal',
                'address'   => 'Lock Street, Little Falls, NY 13365',
                'lat'       => '43.0401',
                'lng'       => '-74.8521',
                'phone'     => '(315) 823-2400',
                'website'   => 'https://www.canals.ny.gov',
                'hours'     => 'May - Nov: 8:00 AM - 6:00 PM',
                'admission' => 'Free Entry',
                'featured'  => 'no',
            ),
            array(
                'title'     => 'Old Stone Fort Museum',
                'excerpt'   => 'A 1772 stone church that was fortified and attacked during a British raid in 1780.',
                'content'   => 'Originally constructed as a Reformed Dutch Church, the building was fortified during the Revolution. The complex includes seven historic buildings spanning three centuries of rural New York history.',
                'county'    => 'Montgomery County',
                'theme'     => 'Revolutionary War',
                'address'   => '229 Fort Road, Schoharie, NY 12157',
                'lat'       => '42.6802',
                'lng'       => '-74.3120',
                'phone'     => '(518) 295-7192',
                'website'   => 'https://theoldstonefort.org',
                'hours'     => 'May - Oct: Tue-Sun 10:00 AM - 5:00 PM',
                'admission' => '$9 Adults, $8 Seniors',
                'featured'  => 'no',
            ),
        );

        foreach ( $destinations as $dest ) {
            $check_query = new WP_Query( array(
                'post_type'      => 'voc_destination',
                'title'          => $dest['title'],
                'post_status'    => 'all',
                'posts_per_page' => 1
            ) );

            if ( $check_query->have_posts() ) {
                wp_reset_postdata();
                continue;
            }
            wp_reset_postdata();

            $post_id = wp_insert_post( array(
                'post_title'   => $dest['title'],
                'post_excerpt' => $dest['excerpt'],
                'post_content' => $dest['content'],
                'post_status'  => 'publish',
                'post_type'    => 'voc_destination',
            ) );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                wp_set_object_terms( $post_id, $dest['county'], 'voc_destination_county' );
                wp_set_object_terms( $post_id, $dest['theme'], 'voc_destination_theme' );

                update_post_meta( $post_id, '_dest_address', $dest['address'] );
                update_post_meta( $post_id, '_dest_lat', $dest['lat'] );
                update_post_meta( $post_id, '_dest_lng', $dest['lng'] );
                update_post_meta( $post_id, '_dest_phone', $dest['phone'] );
                update_post_meta( $post_id, '_dest_website', $dest['website'] );
                update_post_meta( $post_id, '_dest_hours', $dest['hours'] );
                update_post_meta( $post_id, '_dest_admission', $dest['admission'] );
                update_post_meta( $post_id, '_dest_featured', $dest['featured'] );
            }
        }
    }
}

// Trigger importer safely from VilloCity Hub
add_action( 'admin_init', function() {
    if ( isset( $_GET['generate_voc_destinations_dummy'] ) && current_user_can( 'manage_options' ) ) {
        voc_generate_destination_dummy_data();
        wp_safe_redirect( admin_url('admin.php?page=voc-destinations-hub&imported=1') );
        exit;
    }
} );

// ==========================================
// 3. INCLUDE SUB-MODULE COMPONENTS
// ==========================================
if ( is_admin() && file_exists( VOC_DESTINATIONS_DIR . 'admin.php' ) ) {
    require_once VOC_DESTINATIONS_DIR . 'admin.php';
}

if ( file_exists( VOC_DESTINATIONS_DIR . 'frontend.php' ) ) {
    require_once VOC_DESTINATIONS_DIR . 'frontend.php';
}