<?php
/**
 * VilloCity Module Add-on: Events - Festival & Itinerary Features
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. LARGE EVENT / FESTIVAL ADD-ON (ADMIN)
// ==========================================

// Enqueue WordPress Media Uploader Scripts on the Event Edit Screen
if ( ! function_exists( 'voc_events_festival_admin_scripts' ) ) {
    add_action( 'admin_enqueue_scripts', 'voc_events_festival_admin_scripts' );
    function voc_events_festival_admin_scripts( $hook ) {
        global $typenow;
        if ( $typenow === 'bcg_event' ) {
            wp_enqueue_media();
        }
    }
}

if ( ! function_exists( 'voc_events_add_festival_meta_box' ) ) {
    add_action( 'add_meta_boxes', 'voc_events_add_festival_meta_box' );
    function voc_events_add_festival_meta_box() {
        add_meta_box(
            'voc_event_festival_data',
            '🎪 Festival & Large Event Features (Optional)',
            'voc_events_render_festival_meta_box',
            'bcg_event',
            'normal',
            'default'
        );
    }
}

if ( ! function_exists( 'voc_events_render_festival_meta_box' ) ) {
    function voc_events_render_festival_meta_box( $post ) {
        wp_nonce_field( 'voc_festival_save_meta', 'voc_festival_meta_nonce' );

        $schedule = get_post_meta( $post->ID, '_event_schedule', true );
        if ( ! is_array( $schedule ) ) $schedule = array();

        $sponsors = get_post_meta( $post->ID, '_event_sponsors', true );
        if ( ! is_array( $sponsors ) ) $sponsors = array();

        ?>
        <style>
            .voc-fest-section { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
            .voc-fest-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-start; background: #fff; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; }
            .voc-fest-input { width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px; }
            .voc-fest-remove { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 4px; padding: 6px 10px; cursor: pointer; font-weight: bold; }
            .voc-fest-add { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; }
            .voc-fest-upload-btn { background: #e2e8f0; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0 12px; cursor: pointer; font-size: 12px; font-weight: 600; white-space: nowrap; color: #334155; }
            .voc-fest-upload-btn:hover { background: #cbd5e1; }
        </style>

        <div class="voc-fest-section">
            <h4 style="margin-top:0; font-size:16px;">⏱️ Schedule of Events / Itinerary</h4>
            <p style="font-size:13px; color:#64748b; margin-top:0;">Add individual times and sub-events (e.g. 12:00 PM - Live Music).</p>
            
            <div id="voc-schedule-wrapper">
                <?php foreach ( $schedule as $item ) : ?>
                    <div class="voc-fest-row">
                        <div style="flex: 1;"><input type="text" name="voc_sch_time[]" value="<?php echo esc_attr($item['time']); ?>" class="voc-fest-input" placeholder="Time (e.g. 10:00 AM)"></div>
                        <div style="flex: 2;"><input type="text" name="voc_sch_title[]" value="<?php echo esc_attr($item['title']); ?>" class="voc-fest-input" placeholder="Sub-Event Title"></div>
                        <div style="flex: 2;"><input type="text" name="voc_sch_desc[]" value="<?php echo esc_attr($item['desc']); ?>" class="voc-fest-input" placeholder="Brief Description"></div>
                        <div style="flex: 1.5;"><input type="text" name="voc_sch_sponsor[]" value="<?php echo esc_attr($item['sponsor']); ?>" class="voc-fest-input" placeholder="Sponsored By (Opt)"></div>
                        <button type="button" class="voc-fest-remove" onclick="this.parentElement.remove();">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="voc-fest-add" onclick="vocAddScheduleRow()">+ Add Schedule Item</button>
        </div>

        <div class="voc-fest-section">
            <h4 style="margin-top:0; font-size:16px;">🤝 Event Sponsors</h4>
            <p style="font-size:13px; color:#64748b; margin-top:0;">Add sponsors to display at the bottom of the event page. You can upload or select an image directly from your Media Library.</p>
            
            <div id="voc-sponsor-wrapper">
                <?php foreach ( $sponsors as $sponsor ) : ?>
                    <div class="voc-fest-row">
                        <div style="flex: 1;"><input type="text" name="voc_sp_tier[]" value="<?php echo esc_attr($sponsor['tier']); ?>" class="voc-fest-input" placeholder="Tier (e.g. Gold, Silver)"></div>
                        <div style="flex: 2;"><input type="text" name="voc_sp_name[]" value="<?php echo esc_attr($sponsor['name']); ?>" class="voc-fest-input" placeholder="Sponsor Name"></div>
                        <div style="flex: 2; display: flex; gap: 4px;">
                            <input type="url" name="voc_sp_logo[]" value="<?php echo esc_attr($sponsor['logo']); ?>" class="voc-fest-input" placeholder="Logo Image URL">
                            <button type="button" class="voc-fest-upload-btn">Media</button>
                        </div>
                        <div style="flex: 2;"><input type="url" name="voc_sp_link[]" value="<?php echo esc_attr($sponsor['link']); ?>" class="voc-fest-input" placeholder="Sponsor Website Link"></div>
                        <button type="button" class="voc-fest-remove" onclick="this.parentElement.remove();">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="voc-fest-add" onclick="vocAddSponsorRow()">+ Add Sponsor</button>
        </div>

        <script>
            function vocAddScheduleRow() {
                const wrapper = document.getElementById('voc-schedule-wrapper');
                const row = document.createElement('div');
                row.className = 'voc-fest-row';
                row.innerHTML = `
                    <div style="flex: 1;"><input type="text" name="voc_sch_time[]" class="voc-fest-input" placeholder="Time (e.g. 10:00 AM)"></div>
                    <div style="flex: 2;"><input type="text" name="voc_sch_title[]" class="voc-fest-input" placeholder="Sub-Event Title"></div>
                    <div style="flex: 2;"><input type="text" name="voc_sch_desc[]" class="voc-fest-input" placeholder="Brief Description"></div>
                    <div style="flex: 1.5;"><input type="text" name="voc_sch_sponsor[]" class="voc-fest-input" placeholder="Sponsored By (Opt)"></div>
                    <button type="button" class="voc-fest-remove" onclick="this.parentElement.remove();">&times;</button>
                `;
                wrapper.appendChild(row);
            }

            function vocAddSponsorRow() {
                const wrapper = document.getElementById('voc-sponsor-wrapper');
                const row = document.createElement('div');
                row.className = 'voc-fest-row';
                row.innerHTML = `
                    <div style="flex: 1;"><input type="text" name="voc_sp_tier[]" class="voc-fest-input" placeholder="Tier (e.g. Gold, Silver)"></div>
                    <div style="flex: 2;"><input type="text" name="voc_sp_name[]" class="voc-fest-input" placeholder="Sponsor Name"></div>
                    <div style="flex: 2; display: flex; gap: 4px;">
                        <input type="url" name="voc_sp_logo[]" class="voc-fest-input" placeholder="Logo Image URL">
                        <button type="button" class="voc-fest-upload-btn">Media</button>
                    </div>
                    <div style="flex: 2;"><input type="url" name="voc_sp_link[]" class="voc-fest-input" placeholder="Sponsor Website Link"></div>
                    <button type="button" class="voc-fest-remove" onclick="this.parentElement.remove();">&times;</button>
                `;
                wrapper.appendChild(row);
            }

            // WP Media Uploader Integration
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('voc-fest-upload-btn')) {
                    e.preventDefault();
                    var button = e.target;
                    var inputField = button.previousElementSibling;
                    
                    var mediaUploader = wp.media({
                        title: 'Select or Upload Sponsor Logo',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        inputField.value = attachment.url;
                    });
                    
                    mediaUploader.open();
                }
            });
        </script>
        <?php
    }
}

if ( ! function_exists( 'voc_events_save_festival_meta' ) ) {
    add_action( 'save_post_bcg_event', 'voc_events_save_festival_meta' );
    function voc_events_save_festival_meta( $post_id ) {
        if ( ! isset( $_POST['voc_festival_meta_nonce'] ) || ! wp_verify_nonce( $_POST['voc_festival_meta_nonce'], 'voc_festival_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // Save Schedule
        $schedule = array();
        if ( isset($_POST['voc_sch_time']) && is_array($_POST['voc_sch_time']) ) {
            for ( $i = 0; $i < count($_POST['voc_sch_time']); $i++ ) {
                if ( ! empty($_POST['voc_sch_title'][$i]) ) {
                    $schedule[] = array(
                        'time'    => sanitize_text_field( $_POST['voc_sch_time'][$i] ),
                        'title'   => sanitize_text_field( $_POST['voc_sch_title'][$i] ),
                        'desc'    => sanitize_text_field( $_POST['voc_sch_desc'][$i] ),
                        'sponsor' => sanitize_text_field( $_POST['voc_sch_sponsor'][$i] ),
                    );
                }
            }
        }
        update_post_meta( $post_id, '_event_schedule', $schedule );

        // Save Sponsors
        $sponsors = array();
        if ( isset($_POST['voc_sp_name']) && is_array($_POST['voc_sp_name']) ) {
            for ( $i = 0; $i < count($_POST['voc_sp_name']); $i++ ) {
                if ( ! empty($_POST['voc_sp_name'][$i]) ) {
                    $sponsors[] = array(
                        'tier' => sanitize_text_field( $_POST['voc_sp_tier'][$i] ),
                        'name' => sanitize_text_field( $_POST['voc_sp_name'][$i] ),
                        'logo' => esc_url_raw( $_POST['voc_sp_logo'][$i] ),
                        'link' => esc_url_raw( $_POST['voc_sp_link'][$i] ),
                    );
                }
            }
        }
        update_post_meta( $post_id, '_event_sponsors', $sponsors );
    }
}

// ==========================================
// 2. LARGE EVENT / FESTIVAL ADD-ON (FRONTEND)
// ==========================================
if ( ! function_exists( 'voc_events_append_festival_frontend' ) ) {
    add_filter( 'the_content', 'voc_events_append_festival_frontend', 20 ); // Fires after main event template
    function voc_events_append_festival_frontend( $content ) {
        // Only run on the single event page
        if ( is_singular( 'bcg_event' ) && in_the_loop() && is_main_query() ) {
            
            $id = get_the_ID();
            $schedule = get_post_meta( $id, '_event_schedule', true );
            $sponsors = get_post_meta( $id, '_event_sponsors', true );

            // If neither exists, just return the standard content
            if ( empty( $schedule ) && empty( $sponsors ) ) {
                return $content;
            }

            // Inherit Global Brand Colors
            $primary_col = get_option('villocity_global_primary', '#860038');
            $accent_col  = get_option('villocity_global_accent', '#fadceb');
            
            $addon_html = '<style>
                .voc-festival-container { font-family: "Montserrat", sans-serif; margin-top: 40px; padding-top: 30px; border-top: 2px dashed #e7e5e4; }
                
                /* Schedule Timeline Styles */
                .voc-schedule-wrap { margin-bottom: 40px; }
                .voc-sch-title { font-size: 24px; font-weight: 800; color: ' . esc_attr($primary_col) . '; margin-bottom: 20px; display:flex; align-items:center; gap:8px; }
                .voc-sch-item { display: flex; gap: 20px; margin-bottom: 16px; padding: 16px; background: #fafaf9; border-radius: 12px; border: 1px solid #e7e5e4; transition: 0.2s; }
                .voc-sch-item:hover { border-color: ' . esc_attr($primary_col) . '; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
                .voc-sch-time { min-width: 100px; font-weight: 700; color: ' . esc_attr($primary_col) . '; flex-shrink: 0; }
                .voc-sch-details h5 { margin: 0 0 4px 0; font-size: 18px; font-weight: 700; color: #1c1917; }
                .voc-sch-details p { margin: 0; font-size: 14px; color: #475569; line-height: 1.5; }
                .voc-sch-sponsor { display: inline-block; margin-top: 8px; font-size: 12px; font-weight: 600; background: ' . esc_attr($accent_col) . '; color: ' . esc_attr($primary_col) . '; padding: 2px 10px; border-radius: 12px; }

                /* Sponsor Grid Styles */
                .voc-sponsors-wrap { background: #fff; border: 1px solid #e7e5e4; border-radius: 16px; padding: 32px; text-align: center; }
                .voc-sponsors-title { font-size: 24px; font-weight: 800; color: ' . esc_attr($primary_col) . '; margin-bottom: 24px; }
                .voc-sponsor-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 24px; }
                .voc-sponsor-card { display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: inherit; width: 150px; transition: transform 0.2s; }
                .voc-sponsor-card:hover { transform: translateY(-3px); }
                .voc-sponsor-logo { width: 120px; height: 120px; object-fit: contain; margin-bottom: 12px; }
                .voc-sponsor-name { font-size: 14px; font-weight: 600; color: #1c1917; }
                .voc-sponsor-tier { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
            </style>';

            $addon_html .= '<div class="voc-festival-container">';

            // 1. Render Schedule
            if ( ! empty( $schedule ) ) {
                $addon_html .= '<div class="voc-schedule-wrap">';
                $addon_html .= '<h3 class="voc-sch-title">📅 Schedule of Events</h3>';
                foreach ( $schedule as $item ) {
                    $addon_html .= '<div class="voc-sch-item">';
                    $addon_html .= '<div class="voc-sch-time">' . esc_html( $item['time'] ) . '</div>';
                    $addon_html .= '<div class="voc-sch-details">';
                    $addon_html .= '<h5>' . esc_html( $item['title'] ) . '</h5>';
                    if ( ! empty( $item['desc'] ) ) {
                        $addon_html .= '<p>' . esc_html( $item['desc'] ) . '</p>';
                    }
                    if ( ! empty( $item['sponsor'] ) ) {
                        $addon_html .= '<span class="voc-sch-sponsor">Presented by ' . esc_html( $item['sponsor'] ) . '</span>';
                    }
                    $addon_html .= '</div></div>';
                }
                $addon_html .= '</div>';
            }

            // 2. Render Sponsors
            if ( ! empty( $sponsors ) ) {
                $addon_html .= '<div class="voc-sponsors-wrap">';
                $addon_html .= '<h3 class="voc-sponsors-title">🤝 A Special Thanks to Our Sponsors</h3>';
                $addon_html .= '<div class="voc-sponsor-grid">';
                foreach ( $sponsors as $sponsor ) {
                    $tag = ! empty( $sponsor['link'] ) ? 'a' : 'div';
                    $href = ! empty( $sponsor['link'] ) ? 'href="' . esc_url( $sponsor['link'] ) . '" target="_blank" rel="noopener noreferrer"' : '';
                    
                    $addon_html .= '<' . $tag . ' ' . $href . ' class="voc-sponsor-card">';
                    
                    if ( ! empty( $sponsor['tier'] ) ) {
                        $addon_html .= '<span class="voc-sponsor-tier">' . esc_html( $sponsor['tier'] ) . '</span>';
                    }
                    
                    if ( ! empty( $sponsor['logo'] ) ) {
                        $addon_html .= '<img src="' . esc_url( $sponsor['logo'] ) . '" alt="' . esc_attr( $sponsor['name'] ) . ' Logo" class="voc-sponsor-logo">';
                    } else {
                        // Placeholder if no logo is provided
                        $addon_html .= '<div style="width:100px; height:100px; background:#f1f5f9; border-radius:50%; margin-bottom:12px; display:flex; align-items:center; justify-content:center; color:#94a3b8;"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div>';
                    }
                    
                    $addon_html .= '<span class="voc-sponsor-name">' . esc_html( $sponsor['name'] ) . '</span>';
                    $addon_html .= '</' . $tag . '>';
                }
                $addon_html .= '</div></div>';
            }

            $addon_html .= '</div>';

            // Append the new block to the end of the existing content
            return $content . $addon_html;
        }
        
        return $content;
    }
}

// ==========================================
// 3. CLONE EVENT UTILITY
// ==========================================
if ( ! function_exists( 'voc_events_add_clone_link' ) ) {
    // Add "Clone Event" link to the native WordPress post row actions
    add_filter( 'post_row_actions', 'voc_events_add_clone_link', 10, 2 );
    function voc_events_add_clone_link( $actions, $post ) {
        if ( current_user_can( 'edit_posts' ) && $post->post_type === 'bcg_event' ) {
            $nonce_url = wp_nonce_url( admin_url( 'admin.php?action=voc_clone_event&post=' . $post->ID ), 'voc_clone_event_nonce', 'clone_nonce' );
            $actions['clone'] = '<a href="' . esc_url( $nonce_url ) . '" title="Duplicate this event for next year" style="color: #16a34a; font-weight: 600;">Clone Event</a>';
        }
        return $actions;
    }
}

if ( ! function_exists( 'voc_events_process_clone' ) ) {
    // Process the clone request
    add_action( 'admin_action_voc_clone_event', 'voc_events_process_clone' );
    function voc_events_process_clone() {
        if ( ! isset( $_GET['post'] ) || ! isset( $_GET['clone_nonce'] ) ) {
            wp_die( 'Missing parameters.' );
        }
        if ( ! wp_verify_nonce( $_GET['clone_nonce'], 'voc_clone_event_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Permission denied.' );
        }

        $post_id = absint( $_GET['post'] );
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'bcg_event' ) {
            wp_die( 'Event not found.' );
        }

        // 1. Create the new cloned post as a Draft
        $new_post_args = array(
            'post_title'   => $post->post_title . ' (Copy)',
            'post_content' => $post->post_content,
            'post_status'  => 'draft',
            'post_type'    => $post->post_type,
            'post_author'  => get_current_user_id(),
        );

        $new_post_id = wp_insert_post( $new_post_args );

        if ( $new_post_id && ! is_wp_error( $new_post_id ) ) {
            // 2. Duplicate all custom fields (Schedule, Sponsors, Dates, etc.)
            $post_meta_infos = get_post_meta( $post_id );
            if ( count( $post_meta_infos ) != 0 ) {
                foreach ( $post_meta_infos as $meta_key => $meta_values ) {
                    // Skip internal WordPress lock logs
                    if ( $meta_key === '_edit_lock' || $meta_key === '_edit_last' ) continue;
                    
                    foreach ( $meta_values as $meta_value ) {
                        add_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value ) );
                    }
                }
            }
            
            // 3. Duplicate taxonomy terms (Event Categories)
            $taxonomies = get_object_taxonomies( $post->post_type );
            foreach ( $taxonomies as $taxonomy ) {
                $post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
                wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
            }

            // 4. Redirect the user straight into the new Draft to start editing
            wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
            exit;
        } else {
            wp_die( 'Event creation failed.' );
        }
    }
}