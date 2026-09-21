<?php
/**
 * Module Name: VilloCity Documentation Hub (Admin)
 * Module URI:  https://villocity.org
 * Description: Tabbed documentation interface attached directly to the VilloCity Hub dashboard.
 * Version:     2.1.0
 * Last Updated: September 5, 2026
 * Author:      Brockett Creative Group, Inc.
 * Author URI:  https://brockettcreative.com
 * Copyright:   © 2026 Brockett Creative Group, Inc. All Rights Reserved.
 * Text Domain: villocity-suite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 1. ADMIN MENU ROUTING
// ==========================================
if ( ! function_exists( 'voc_suite_add_docs_menu' ) ) {
    add_action( 'admin_menu', 'voc_suite_add_docs_menu', 99 );
    function voc_suite_add_docs_menu() {
        if ( menu_page_url( 'villocity-hub', false ) ) {
            add_submenu_page(
                'villocity-hub', 
                'Suite Documentation',          
                '📖 Help & Docs',               
                'manage_options',                   
                'voc-suite-docs',              
                'voc_suite_render_docs_page'   
            );
        }
    }
}

// ==========================================
// 2. RENDER DOCUMENTATION DASHBOARD
// ==========================================
if ( ! function_exists( 'voc_suite_render_docs_page' ) ) {
    function voc_suite_render_docs_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $primary_col = get_option('villocity_global_primary', '#860038');
        ?>
        <div class="voc-gemini-dashboard">
            <div class="voc-gemini-header">
                <div>
                    <h1 style="color: <?php echo esc_attr($primary_col); ?>;">
                        📖 VilloCity Suite Documentation
                    </h1>
                    <p>Master reference guide for managing your community hub modules.</p>
                </div>
            </div>

            <!-- Documentation Tabs -->
            <div class="voc-gemini-tabs">
                <button class="voc-gemini-tab active" onclick="voc_switch_doc_tab(event, 'voc-docs-events')"><?php echo villocity_get_icon('calendar'); ?> Events</button>
                <button class="voc-gemini-tab" onclick="voc_switch_doc_tab(event, 'voc-docs-news')"><?php echo villocity_get_icon('news'); ?> Live News</button>
                <button class="voc-gemini-tab" onclick="voc_switch_doc_tab(event, 'voc-docs-marketplace')"><?php echo villocity_get_icon('bag'); ?> Marketplace</button>
                <button class="voc-gemini-tab" onclick="voc_switch_doc_tab(event, 'voc-docs-directory')"><?php echo villocity_get_icon('store'); ?> Directory</button>
                <button class="voc-gemini-tab" onclick="voc_switch_doc_tab(event, 'voc-docs-staff')"><?php echo villocity_get_icon('users'); ?> Board & Staff</button>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: EVENTS DOCS                         -->
            <!-- ========================================== -->
            <div id="voc-docs-events" class="voc-tab-content" style="display: block;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
                    
                    <!-- STANDARD EVENTS -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('edit'); ?> 1. Creating a Standard Event</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            To add a new event, click <strong>Add New</strong> under the Events menu.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li><strong>Title & Description:</strong> Enter the name of the event and all relevant details in the main text editor.</li>
                            <li><strong>Featured Image:</strong> Upload an image in the right sidebar. This will be used as the event's main thumbnail on the public calendar.</li>
                            <li><strong>Event Data:</strong> Scroll down to fill out the Event Date, Time, Location, and optional Ticket/RSVP links.</li>
                        </ul>
                    </div>

                    <!-- FESTIVALS & SCHEDULES -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('clock'); ?> 2. Festivals & Itineraries</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            For large events that have multiple activities throughout the day (like a Summer Festival), you can build a dynamic timeline.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>Scroll down to the <strong>Festival & Large Event Features</strong> box.</li>
                            <li>Click <strong>+ Add Schedule Item</strong>.</li>
                            <li>Enter the time (e.g., "12:00 PM"), the activity title (e.g., "Live Music"), and an optional description or individual sponsor.</li>
                            <li>The system will automatically format this into a clean timeline on the public event page.</li>
                        </ul>
                    </div>

                    <!-- SPONSORS -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('star'); ?> 3. Adding Sponsors</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            You can recognize local businesses who sponsor your events by building a sponsor grid.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>In the Festival Features box, click <strong>+ Add Sponsor</strong>.</li>
                            <li>Type their Name and optionally assign a Tier (e.g., "Gold Sponsor").</li>
                            <li>Click the <strong>Media</strong> button to upload or select their logo from your WordPress library.</li>
                            <li>Add their website URL so visitors can click their logo to visit their site.</li>
                        </ul>
                    </div>

                    <!-- CLONING ANNUAL EVENTS -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('refresh'); ?> 4. Cloning Annual Events</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            Don't rebuild complex festivals from scratch every year! You can duplicate an existing event perfectly.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>Go to your main <strong>Events</strong> list in the dashboard.</li>
                            <li>Hover your mouse over the event you want to duplicate.</li>
                            <li>Click the green <strong>Clone Event</strong> link.</li>
                            <li>The system will create a Draft copy with the entire schedule, description, and sponsor grid intact. Simply update the date to the current year and click Publish!</li>
                        </ul>
                    </div>

                </div>
                
                <div class="voc-gemini-card" style="margin-top: 24px; text-align: center; background: #f8fafc;">
                    <p style="color: #64748b; font-size: 14px; margin: 0;">
                        <strong>Shortcode Reference:</strong> Use <code>[villocity_events]</code> to display the master calendar on any page.
                    </p>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: LIVE NEWS DOCS                      -->
            <!-- ========================================== -->
            <div id="voc-docs-news" class="voc-tab-content" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
                    
                    <!-- AUTOMATION & FEEDS -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('rss'); ?> 1. Automated Feeds & Rules</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            The Live News module automatically pulls headlines from external sources. You do not need to post articles manually.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li><strong>Manage Sources:</strong> Go to the <em>Live News Settings &rarr; RSS Sources</em> tab to add local news outlet RSS URLs.</li>
                            <li><strong>Keyword Targeting:</strong> In the <em>UI Settings</em> tab, you must add "Included Keywords" (like your town name). An article is only saved if it mentions one of these words.</li>
                            <li><strong>Blocked Keywords:</strong> Add words like "obituary" or "classifieds" to block unwanted content from reaching the feed.</li>
                        </ul>
                    </div>

                    <!-- CATEGORIES & TABS -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('tag'); ?> 2. Setting Up Category Tabs</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            You can create clickable filter buttons at the top of the news feed by grouping keywords.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>In the <em>UI Settings</em> tab, find the <strong>Category Filter Tabs</strong> text box.</li>
                            <li>Enter one category per line using this exact format: <code>Tab Name | keyword1, keyword2</code></li>
                            <li>Example: <code>High School Sports | athletics, warriors, section 3</code>. When a user clicks "High School Sports", it will only show articles containing those specific keywords.</li>
                        </ul>
                    </div>

                    <!-- MANAGING ARTICLES -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('globe'); ?> 3. Managing & Pinning Content</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            While the system is automated, you retain full control over what is displayed to the public.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li><strong>Custom Announcements:</strong> Use the <em>Feed Content</em> tab to write and publish manual alerts (like road closures) that sit alongside news articles.</li>
                            <li><strong>Pin to Top:</strong> Click "Pin" on any article to lock it to the top of the feed and give it a highlighted background.</li>
                            <li><strong>Trash Bin:</strong> Click "Trash" on any article to immediately hide it from the public. You can permanently delete or restore it from the <em>Trash Bin</em> tab.</li>
                        </ul>
                    </div>

                    <!-- EMAIL DIGESTS -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('mail'); ?> 4. Weekly Email Digests</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            The system can automatically email a styled newsletter containing the week's top local headlines.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>Go to the <em>Automation & Digest</em> tab.</li>
                            <li>Check the "Enable Weekly Email Digest" box and enter a recipient email address.</li>
                            <li>You can hit <strong>Send Test Email Digest Now</strong> to instantly preview what the HTML newsletter looks like in your inbox.</li>
                        </ul>
                    </div>

                </div>

                <div class="voc-gemini-card" style="margin-top: 24px; background: #f8fafc; display: flex; flex-wrap: wrap; gap: 24px;">
                    <div style="flex: 1; min-width: 250px;">
                        <p style="color: #64748b; font-size: 14px; margin: 0 0 8px 0;"><strong>Main Feed Shortcode:</strong></p>
                        <code style="display:inline-block; padding:8px 12px; background:#fff; border-radius:6px; border: 1px solid #e2e8f0; width: 100%;">[villocity_local_news]</code>
                        <p style="color: #475569; font-size: 13px; margin: 8px 0 0 0;">Outputs the full feed with the search bar, category tabs, and pagination.</p>
                    </div>
                    <div style="flex: 1; min-width: 250px;">
                        <p style="color: #64748b; font-size: 14px; margin: 0 0 8px 0;"><strong>Homepage Mini-Feed Shortcode:</strong></p>
                        <code style="display:inline-block; padding:8px 12px; background:#fff; border-radius:6px; border: 1px solid #e2e8f0; width: 100%;">[villocity_latest_news count="3"]</code>
                        <p style="color: #475569; font-size: 13px; margin: 8px 0 0 0;">Outputs a clean, compact grid of the most recent headlines without the sidebar.</p>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: MARKETPLACE DOCS                    -->
            <!-- ========================================== -->
            <div id="voc-docs-marketplace" class="voc-tab-content" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
                    
                    <!-- APPROVING LISTINGS -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('check'); ?> 1. Approving Submissions</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            If you allow public submissions, community posts (like Yard Sales) will appear as <strong>Pending</strong>.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>Navigate to the <strong>Marketplace</strong> menu in your dashboard.</li>
                            <li>Click on any listing marked as "Pending" to review its content, dates, and location.</li>
                            <li>If it meets community guidelines, simply click <strong>Publish</strong> to push it live to the public board.</li>
                        </ul>
                    </div>

                    <!-- MANUAL LISTINGS -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('edit'); ?> 2. Adding Listings Manually</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            You can easily add municipal announcements or sponsored listings manually.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>Click <strong>Add New</strong> under the Marketplace menu.</li>
                            <li>Add the title, description, and upload a featured image.</li>
                            <li>Set the start and end dates (crucial for time-sensitive listings like weekend farm stands).</li>
                        </ul>
                    </div>

                    <!-- GOOGLE MAPS -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('map-pin'); ?> 3. Location & Google Maps</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            The Marketplace module features an integrated map to help citizens find sales and stands.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>In the listing editor, look for the Location/Address fields.</li>
                            <li>Enter a valid street address. The system uses this to drop a pin on the main marketplace map automatically.</li>
                        </ul>
                    </div>

                    <!-- CATEGORIES -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('tag'); ?> 4. Listing Categories</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            Organize the board so users can filter by what they are looking for.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>Go to <strong>Marketplace &rarr; Categories</strong> to manage types (e.g., Yard Sale, Farm Stand, Craft Fair, Fundraiser).</li>
                            <li>Assign categories to listings in the right sidebar before publishing.</li>
                        </ul>
                    </div>

                </div>
                
                <div class="voc-gemini-card" style="margin-top: 24px; text-align: center; background: #f8fafc;">
                    <p style="color: #64748b; font-size: 14px; margin: 0;">
                        <strong>Shortcode Reference:</strong> Use <code>[villocity_local_listings]</code> to display the main interactive marketplace board.
                    </p>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 4: DIRECTORY DOCS                      -->
            <!-- ========================================== -->
            <div id="voc-docs-directory" class="voc-tab-content" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
                    
                    <!-- ADDING BUSINESSES -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('store'); ?> 1. Adding a Business</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            Build out your local commercial and services database to support local economic development.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>Navigate to <strong>Business Directory &rarr; Add New</strong>.</li>
                            <li>Enter the business name as the title and provide a description in the main editor.</li>
                            <li><strong>Logo:</strong> Upload the business logo via the "Featured Image" box in the right sidebar. This is displayed prominently on the search cards.</li>
                        </ul>
                    </div>

                    <!-- DIRECTORY CATEGORIES -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('tag'); ?> 2. Managing Categories</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            Categories are the backbone of the Directory's Super Search. Users rely on them to filter local options.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>Go to <strong>Business Directory &rarr; Categories</strong> to establish your standard groups (e.g., "Food & Dining", "Retail", "Home Services").</li>
                            <li>Be sure to check the appropriate category box on the right side of the screen when creating a new business listing.</li>
                            <li>You can assign a business to multiple categories if it applies.</li>
                        </ul>
                    </div>

                    <!-- CONTACT & SOCIAL INFO -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('phone'); ?> 3. Contact & Social Info</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            Help citizens connect directly with businesses by providing comprehensive contact data.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li>Scroll down to the <strong>Directory Listing Details</strong> box on the edit screen.</li>
                            <li>Enter the physical address, phone number, and public email.</li>
                            <li>Add the primary website link and any relevant social media profile URLs. The frontend will automatically generate clickable icons for these.</li>
                        </ul>
                    </div>

                    <!-- SUPER SEARCH -->
                    <div class="voc-gemini-card" style="margin-bottom: 0;">
                        <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('search'); ?> 4. The Super Search UI</h3>
                        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                            The directory frontend features a highly optimized, instant-filtering interface.
                        </p>
                        <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                            <li><strong>Sticky Sidebar:</strong> The category list sticks to the side of the screen on desktop devices, allowing users to rapidly click through different business types without reloading the page.</li>
                            <li><strong>Instant Search:</strong> The search bar instantly filters businesses by name or description as the user types.</li>
                        </ul>
                    </div>

                </div>
                
                <div class="voc-gemini-card" style="margin-top: 24px; text-align: center; background: #f8fafc;">
                    <p style="color: #64748b; font-size: 14px; margin: 0;">
                        <strong>Shortcode Reference:</strong> Use <code>[villocity_business_directory]</code> to display the full, categorized Business Directory.
                    </p>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 5: STAFF DOCS                          -->
            <!-- ========================================== -->
            <div id="voc-docs-staff" class="voc-tab-content" style="display: none;">
                 <div class="voc-gemini-card">
                    <h3 style="color: <?php echo esc_attr($primary_col); ?>;"><?php echo villocity_get_icon('users'); ?> Board & Staff Guide</h3>
                    <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                        Manage your municipal leadership directory, department rosters, and official contact channels.
                    </p>
                    <ul style="color: #475569; font-size: 14px; line-height: 1.6; margin-left: 20px;">
                        <li>Use <code>[villocity_staff]</code> to display your complete municipal roster and board member grid.</li>
                    </ul>
                 </div>
            </div>

        </div>

        <script>
            function voc_switch_doc_tab(event, tabId) {
                if(event) event.preventDefault();
                var contents = document.getElementsByClassName("voc-tab-content");
                for (var i = 0; i < contents.length; i++) {
                    contents[i].style.display = "none";
                }
                var tabs = document.getElementsByClassName("voc-gemini-tab");
                for (var i = 0; i < tabs.length; i++) {
                    tabs[i].classList.remove("active");
                }
                document.getElementById(tabId).style.display = "block";
                if(event) {
                    event.currentTarget.classList.add("active");
                }
            }
        </script>
        <?php
    }
}