<?php
/**
* Admin Settings
*/
class XC_WOO_CLOUD_Settings
{
    /**    
    * Holds the values to be used in the fields callbacks    
    */
    private $options;
    private $option_page = 'xc_woo_cloud_settings';
    private $printers_found = 0;
    private $token;
    /**
    
    * Start up
    
    */
    public function __construct()
    {
        add_action('admin_menu', array(
            $this,
            'add_plugin_page'
        ));
        add_action('admin_init', array(
            $this,
            'page_init'
        ));
        add_filter('plugin_action_links_' . XC_WOO_CLOUD_BASE_NAME, array(
            $this,
            'add_action_links'
        ));
        add_filter('xc_woo_cloud_print_options', array(
            $this,
            'xc_woo_cloud_print_options'
        ));
        add_action('wp_ajax_xc_woo_refresh_printers', array(
            $this,
            'xc_woo_cloud_print_options_printer'
        ));
        $this->handle_url_actions();
    }
    /**
    
    * Add options page
    
    */
    public function add_plugin_page()
    {
        add_submenu_page('options-general.php', 'Google Cloud Print Setings', 'Google Cloud Print Settings', 'manage_options', 'xc_woo_cloud_settings', array(
            &$this,
            'create_admin_page'
        ));
    }
    /**
    
    * Options page callback
    
    */
    public function create_admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        } //!current_user_can('manage_options')
        // Set class property
        $this->options = get_option('xc_woo_cloud_options');
        wp_enqueue_script('jquery-ui-spinner', false, array(
            'jquery'
        ));
        $pver  = XC_WOO_CLOUD_VERSION;
        $title = htmlspecialchars("XC Woo Google Cloud Printer Options");
        echo '<div style="clear: left;width:950px; float: left; margin-right:20px;">';
        echo "<h1>$title (version $pver)</h1>";
        echo '<p>Authored by <a href="http://xperts.club"><strong>Xperts Club</strong></a></p>';
        echo "<div>\n";
        echo '<div style="margin:4px; padding:6px; border: 1px dotted;">';
        echo '<p><em><strong>' . __('Instructions', XC_WOO_CLOUD) . ':</strong></em></p> ';
?>
<a target="_blank" href="http://wp.xperts.club/googlecloudprint/configuring-google-cloud-print-api-access/"><strong>
<?php
        _e('For longer help, including screenshots, follow this link. The description below is sufficient for more expert users.', XC_WOO_CLOUD);
?>
</strong></a>
<?php
        $admin_page_url = admin_url('options-general.php');
        # This is advisory - so the fact it doesn't match IPv6 addresses isn't important
        if (preg_match('#^(https?://(\d+)\.(\d+)\.(\d+)\.(\d+))/#', $admin_page_url, $matches)) {
            echo '<p><strong>' . htmlspecialchars(sprintf(__("%s does not allow authorisation of sites hosted on direct IP addresses. You will need to change your site's address (%s) before you can use %s for storage.", XC_WOO_CLOUD), __('Google Cloud Print', XC_WOO_CLOUD), $matches[1], __('Google Cloud Print', XC_WOO_CLOUD))) . '</strong></em></p>';
        } //preg_match('#^(https?://(\d+)\.(\d+)\.(\d+)\.(\d+))/#', $admin_page_url, $matches)
        else {
?>
<p></p>
<p><em><a target="_blank" href="https://console.developers.google.com">
  <?php
            _e('Follow this link to your Google API Console, and there create a Client ID in the API Access section.', XC_WOO_CLOUD);
?>
  </a>
  <?php
            _e("Select 'Web Application' as the application type. Then enter the client ID and secret below and save your settings.", XC_WOO_CLOUD);
?>
</p>
<p>
  <?php
            echo htmlspecialchars(__('You must add the following as the authorised redirect URI (under "More Options") when asked', XC_WOO_CLOUD));
?>
  : <kbd>
  <?php
            echo $admin_page_url . '?action=xc-woo-google-cloud-print-auth';
?>
  </kbd>
  <?php
            _e('N.B. If you install this plugin on several WordPress sites, then you might have problems in re-using your project (depending on whether Google have fixed issues at their end yet); if so, then  create a new project from your Google API console for each site.', XC_WOO_CLOUD);
?>
  </em></p>
<p> <em>
  <?php
            echo __('After completing authentication, a list of printers will appear.', XC_WOO_CLOUD) . ' <strong>' . __('Choose one, and then save the settings for the second time.', XC_WOO_CLOUD) . '</strong></p>';
?>
  </em> </p>
<?php
        }
        echo '</div>';
?>
<div class="wrap">
  <form method="post" action="options.php">
    <?php
        // This prints out all hidden setting fields
        settings_fields('xc_woo_cloud_print_options');
        do_settings_sections('xc_woo_cloud_print');
        submit_button();
?>
  </form>
</div>
<?php
        $nonce      = wp_create_nonce("xc-woo-cloud-nonce");
        $refreshing = esc_js(__('refreshing...', XC_WOO_CLOUD));
        $refresh    = esc_js(__('refresh', XC_WOO_CLOUD));
?>
<script>



        var google_cloud_print_confirm_unload = null;



        window.onbeforeunload = function() { return google_cloud_print_confirm_unload; }



        jQuery(document).ready(function($) {



            $('#xc_woo_refreshprinters').click(function(e) {

                e.preventDefault();

                $('.xc_woo_cloud_print_options_printer').css('opacity','0.3');

                $('#xc_woo_refreshprinters').html('<?php
        echo $refreshing;
?>');

                $.post(ajaxurl, {

                    action: 'xc_woo_refresh_printers',

                    _wpnonce: '<?php
        echo $nonce;
?>'

                }, function(response) {

                    $('#xc_woo_cloud_print_options_printer_container').html(response);

                    $('.xc_woo_cloud_print_options_printer').css('opacity','1');

                    $('#xc_woo_refreshprinters').html('<?php
        echo $refresh;
?>');

                });

            });

        });

    </script>
<?php
    }
    /**
    
    * Register and add settings
    
    */
    public function page_init()
    {
        register_setting('xc_woo_cloud_print_options', 'xc_woo_cloud_print_options', array(
            $this,
            'options_validate'
        ));
        add_settings_section('xc_woo_cloud_print_options', 'Google Cloud Print', array(
            $this,
            'options_header'
        ), 'xc_woo_cloud_print');
        add_settings_field('xc_woo_cloud_print_options_clientid', __('Google Client ID', XC_WOO_CLOUD), array(
            $this,
            'xc_woo_cloud_print_options_clientid'
        ), 'xc_woo_cloud_print', 'xc_woo_cloud_print_options');
        add_settings_field('xc_woo_cloud_print_options_clientsecret', __('Google Client Secret', XC_WOO_CLOUD), array(
            $this,
            'xc_woo_cloud_print_options_clientsecret'
        ), 'xc_woo_cloud_print', 'xc_woo_cloud_print_options');
        add_settings_field('xc_woo_cloud_print_options_printer', __('Printer', XC_WOO_CLOUD), array(
            $this,
            'xc_woo_cloud_print_options_printer'
        ), 'xc_woo_cloud_print', 'xc_woo_cloud_print_options');
        if (current_user_can('manage_options')) {
            $opts = get_option('xc_woo_cloud_print_options');
            if (empty($opts['clientid']) && !empty($opts['username'])) {
                if (empty($_GET['page']) || 'xc_woo_cloud_print' != $_GET['page'])
                    add_action('all_admin_notices', array(
                        $this,
                        'show_admin_warning_changedgoogleauth'
                    ));
            } //empty($opts['clientid']) && !empty($opts['username'])
            else {
                $clientid = empty($opts['clientid']) ? '' : $opts['clientid'];
                $token    = empty($opts['token']) ? '' : $opts['token'];
                if (!empty($clientid) && empty($token))
                    add_action('all_admin_notices', array(
                        $this,
                        'show_admin_warning_googleauth'
                    ));
            }
        } //current_user_can('manage_options')
    }
    public function options_header()
    {
        if (!empty($_GET['error'])) {
            $this->show_admin_warning(htmlspecialchars($_GET['error']), 'error');
        } //!empty($_GET['error'])
        echo __('Google Cloud Print links:', XC_WOO_CLOUD) . ' ';
        echo '<a href="https://www.google.com/cloudprint/learn/">' . __('Learn about Google Cloud Print', XC_WOO_CLOUD) . '</a>';
        echo ' | ';
        echo '<a href="https://www.google.com/cloudprint/#printers">' . __('Your printers', XC_WOO_CLOUD) . '</a>';
        echo ' | ';
        echo '<a href="https://www.google.com/cloudprint/#jobs">' . __('Your print jobs', XC_WOO_CLOUD) . '</a>';
        if (current_user_can('manage_options')) {
            $opts = get_option('xc_woo_cloud_print_options');
            if (empty($opts['clientid']) && !empty($opts['username'])) {
                $this->show_admin_warning_changedgoogleauth(true);
            } //empty($opts['clientid']) && !empty($opts['username'])
            $clientid = empty($opts['clientid']) ? '' : $opts['clientid'];
            $token    = empty($opts['token']) ? '' : $opts['token'];
            if (!empty($clientid) && empty($token)) {
                $this->show_admin_warning_googleauth(true);
            } //!empty($clientid) && empty($token)
            elseif (!empty($clientid) && !empty($token)) {
                echo '<p><a href="' . admin_url('options-general.php') . '?page=' . $this->option_page . '&action=xc-woo-google-cloud-print-auth&gcpl_googleauth=doit">' . sprintf(__('You appear to be authenticated with Google Cloud Print, but if you are seeing authorisation errors, then you can click here to authenticate your %s account again.', XC_WOO_CLOUD), 'Google Cloud Print', 'Google Cloud Print') . '</a></p>';
            } //!empty($clientid) && !empty($token)
        } //current_user_can('manage_options')
    }
    public function options_validate($google)
    {
        $opts = get_option('xc_woo_cloud_print_options', array());
        // Remove legacy options
        unset($opts['username']);
        unset($opts['password']);
        if (!is_array($google))
            return $opts;
        $old_client_id = (empty($opts['clientid'])) ? '' : $opts['clientid'];
        if (!empty($opts['token']) && $old_client_id != $google['clientid']) {
            $this->googleauth_auth_revoke($opts['token'], false);
            $google['token'] = '';
            delete_transient('xc_woo_cloud_print_printers');
        } //!empty($opts['token']) && $old_client_id != $google['clientid']
        foreach ($google as $key => $value) {
            // Trim spaces - I got support requests from users who didn't spot the spaces they introduced when copy/pasting
            $opts[$key] = ('clientid' == $key || 'clientsecret' == $key) ? trim($value) : $value;
        } //$google as $key => $value
        return $opts;
    }
    public function xc_woo_cloud_print_options_clientid()
    {
        $options  = get_option('xc_woo_cloud_print_options', array());
        $clientid = (empty($options['clientid'])) ? '' : $options['clientid'];
        echo '<input id="xc_woo_cloud_print_options_clientid" name="xc_woo_cloud_print_options[clientid]" size="72" type="text" value="' . esc_attr($clientid) . '" />';
        echo '<br><em>' . __('See the instructions above to learn how to get this', XC_WOO_CLOUD) . '</em>';
    }
    public function xc_woo_cloud_print_options_clientsecret()
    {
        $options      = get_option('xc_woo_cloud_print_options', array());
        $clientsecret = (empty($options['clientsecret'])) ? '' : $options['clientsecret'];
        echo '<input id="xc_woo_cloud_print_options_clientsecret" name="xc_woo_cloud_print_options[clientsecret]" size="72" type="password" value="' . esc_attr($clientsecret) . '" /><br><em>';
    }
    // This function is both an options field printer, and called via AJAX
    public function xc_woo_cloud_print_options_printer()
    {
        if (defined('DOING_AJAX') && DOING_AJAX == true && (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'xc-woo-cloud-nonce')))
            die;
        $options              = get_option('xc_woo_cloud_print_options');
        $printers             = $this->get_printers();
        $this->printers_found = is_wp_error($printers) ? 0 : count($printers);
        $this->debug_output   = '';
        if (is_wp_error($printers)) {
            echo '<input type="hidden" name="xc_woo_cloud_print_options[printer]" value=""><em>' . strip_tags($printers->get_error_message()) . '</em>';
        } //is_wp_error($printers)
        elseif (count($printers) == 0) {
            echo '<input type="hidden" name="xc_woo_cloud_print_options[printer]" value=""><em>(' . __('Account either not connected, or no printers available)', XC_WOO_CLOUD) . '</em>';
        } //count($printers) == 0
        else {
            echo '<div id="xc_woo_cloud_print_options_printer_container">';
            // Make sure the option gets saved
            //echo '<input name="xc_woo_cloud_print_options[printer][]" type="hidden" value="">';
            foreach ($printers as $printer) {
                echo '<div style="float: left; clear: both; width:100%;">';
                echo '<input onchange="google_cloud_print_confirm_unload = true;" class="xc_woo_cloud_print_options_printer" id="xc_woo_cloud_print_options_printer_' . esc_attr($printer->id) . '" name="xc_woo_cloud_print_options[printer]" type="radio" ' . ((isset($options['printer']) && ((is_array($options['printer']) && in_array($printer->id, $options['printer'])) || (!is_array($options['printer']) && $options['printer'] == $printer->id))) ? 'checked="checked"' : '') . 'value="' . htmlspecialchars($printer->id) . '"><label for="xc_woo_cloud_print_options_printer_' . esc_attr($printer->id) . '">' . htmlspecialchars($printer->displayName) . '</label><br>';
                echo '</div>';
            } //$printers as $printer
            //             echo '</select>';
            echo '</div>';
            if (defined('DOING_AJAX') && DOING_AJAX == true)
                die;
            echo '<div style="clear:both;"> <a href="#" id="xc_woo_refreshprinters">(' . __('refresh', XC_WOO_CLOUD) . ')</a></div>';
        }
    }
    public function show_admin_warning_googleauth($suppress_title = false)
    {
        $warning = ($suppress_title) ? '' : '<strong>' . __('Google Cloud Print notice:', XC_WOO_CLOUD) . '</strong> ';
        $warning .= '<a href="' . admin_url('options-general.php') . '?page=' . $this->option_page . '&action=xc-woo-google-cloud-print-auth&gcpl_googleauth=doit">' . sprintf(__('Click here to authenticate your %s account (you will not be able to print via %s without it).', XC_WOO_CLOUD), 'Google Cloud Print', 'Google Cloud Print') . '</a>';
        $this->show_admin_warning($warning);
    }
    public function show_admin_warning($message, $class = "updated")
    {
        echo '<div class="' . $class . '">' . "<p>$message</p></div>";
    }
    public function handle_url_actions()
    {
        // First, basic security check: must be an admin page, with ability to manage options, with the right parameters
        // Also, only on GET because WordPress on the options page repeats parameters sometimes when POST-ing via the _wp_referer field
        if (isset($_SERVER['REQUEST_METHOD']) && 'GET' == $_SERVER['REQUEST_METHOD'] && isset($_GET['action']) && 'xc-woo-google-cloud-print-auth' == $_GET['action'] && current_user_can('manage_options')) {
            $_GET['page']     = $this->option_page;
            $_REQUEST['page'] = $this->option_page;
            if (isset($_GET['state'])) {
                if ('success' == $_GET['state'])
                    add_action('all_admin_notices', array(
                        $this,
                        'show_authed_admin_success'
                    ));
                elseif ('token' == $_GET['state'])
                    $this->googleauth_auth_token();
                elseif ('revoke' == $_GET['state'])
                    $this->googleauth_auth_revoke();
            } //isset($_GET['state'])
            elseif (isset($_GET['gcpl_googleauth'])) {
                $this->googleauth_auth_request();
            } //isset($_GET['gcpl_googleauth'])
        } //isset($_SERVER['REQUEST_METHOD']) && 'GET' == $_SERVER['REQUEST_METHOD'] && isset($_GET['action']) && 'xc-woo-google-cloud-print-auth' == $_GET['action'] && current_user_can('manage_options')
    }
    // Acquire single-use authorization code from Google OAuth 2.0
    public function googleauth_auth_request()
    {
        $opts = get_option('xc_woo_cloud_print_options', array());
        // First, revoke any existing token, since Google doesn't appear to like issuing new ones
        if (!empty($opts['token']))
            $this->googleauth_auth_revoke();
        // We use 'force' here for the approval_prompt, not 'auto', as that deals better with messy situations where the user authenticated, then changed settings
        $params = array(
            'response_type' => 'code',
            'client_id' => $opts['clientid'],
            'redirect_uri' => $this->redirect_uri(),
            'scope' => 'https://www.googleapis.com/auth/cloudprint',
            'state' => 'token',
            'access_type' => 'offline',
            'approval_prompt' => 'force'
        );
        if (headers_sent()) {
            add_action('all_admin_notices', array(
                $this,
                'admin_notice_something_breaking'
            ));
        } //headers_sent()
        else {
            header('Location: https://accounts.google.com/o/oauth2/auth?' . http_build_query($params, null, '&'));
        }
    }
    private function redirect_uri()
    {
        return admin_url('options-general.php') . '?action=xc-woo-google-cloud-print-auth';
    }
    // Revoke a Google account refresh token
    public function googleauth_auth_revoke($token = false, $unsetopt = true)
    {
        if (empty($token)) {
            $opts  = get_option('xc_woo_cloud_print_options', array());
            $token = empty($opts['token']) ? '' : $opts['token'];
        } //empty($token)
        if ($token)
            wp_remote_get('https://accounts.google.com/o/oauth2/revoke?token=' . $token);
        if ($unsetopt) {
            $opts          = get_option('xc_woo_cloud_print_options', array());
            $opts['token'] = '';
            update_option('xc_woo_cloud_print_options', $opts);
        } //$unsetopt
    }
    // Get a Google account refresh token using the code received from googleauth_auth_request
    public function googleauth_auth_token()
    {
        $opts = get_option('xc_woo_cloud_print_options', array());
        if (isset($_GET['code'])) {
            $post_vars                  = array(
                'code' => $_GET['code'],
                'client_id' => $opts['clientid'],
                'client_secret' => $opts['clientsecret'],
                'redirect_uri' => $this->redirect_uri(),
                'grant_type' => 'authorization_code'
            );
            $googleauth_request_options = apply_filters('google_cloud_print_googleauth_request_options', array(
                'timeout' => 25,
                'method' => 'POST',
                'body' => $post_vars
            ));
            $result                     = wp_remote_post('https://accounts.google.com/o/oauth2/token', $googleauth_request_options);
            if (is_wp_error($result)) {
                $add_to_url = "Bad response when contacting Google: ";
                foreach ($result->get_error_messages() as $message) {
                    error_log("Google Drive authentication error: " . $message);
                    $add_to_url .= $message . ". ";
                } //$result->get_error_messages() as $message
                header('Location: ' . admin_url('options-general.php') . '?page=' . $this->option_page . '&error=' . urlencode($add_to_url));
            } //is_wp_error($result)
            else {
                $json_values = json_decode($result['body'], true);
                if (isset($json_values['refresh_token'])) {
                    // Save token
                    $opts['token'] = $json_values['refresh_token'];
                    update_option('xc_woo_cloud_print_options', $opts);
                    if (isset($json_values['access_token'])) {
                        $opts['tmp_access_token'] = $json_values['access_token'];
                        update_option('xc_woo_cloud_print_options', $opts);
                        // We do this to clear the GET parameters, otherwise WordPress sticks them in the _wp_referer in the form and brings them back, leading to confusion + errors
                        header('Location: ' . admin_url('options-general.php') . '?action=xc-woo-google-cloud-print-auth&page=' . $this->option_page . '&state=success');
                    } //isset($json_values['access_token'])
                } //isset($json_values['refresh_token'])
                else {
                    $msg = __('No refresh token was received from Google. This often means that you entered your client secret wrongly, or that you have not yet re-authenticated (below) since correcting it. Re-check it, then follow the link to authenticate again. Finally, if that does not work, then use expert mode to wipe all your settings, create a new Google client ID/secret, and start again.', XC_WOO_CLOUD);
                    if (isset($json_values['error']))
                        $msg .= ' ' . sprintf(__('Error: %s', XC_WOO_CLOUD), $json_values['error']);
                    header('Location: ' . admin_url('options-general.php') . '?page=' . $this->option_page . '&error=' . urlencode($msg));
                }
            }
        } //isset($_GET['code'])
        else {
            $err_msg = __('Authorization failed', XC_WOO_CLOUD);
            if (!empty($_GET['error']))
                $err_msg .= ': ' . $_GET['error'];
            header('Location: ' . admin_url('options-general.php') . '?page=' . $this->option_page . '&error=' . urlencode($err_msg));
        }
    }
    public function show_authed_admin_success()
    {
        //         global $updraftplus_admin;
        $opts = get_option('xc_woo_cloud_print_options', array());
        if (empty($opts['tmp_access_token']))
            return;
        $tmp_access_token = $opts['tmp_access_token'];
        $message          = '';
        $this->show_admin_warning(__('Success', XC_WOO_CLOUD) . ': ' . sprintf(__('you have authenticated your %s account.', XC_WOO_CLOUD), __('Google Cloud Print', XC_WOO_CLOUD)) . ' ');
        unset($opts['tmp_access_token']);
        update_option('xc_woo_cloud_print_options', $opts);
    }
    public function get_printers()
    {
        if (!defined('DOING_AJAX') || DOING_AJAX != true) {
            $printers = get_transient('xc_woo_cloud_print_printers');
            if (is_array($printers))
                return $printers;
        } //!defined('DOING_AJAX') || DOING_AJAX != true
        // Wanted key: access token
        $options = apply_filters('xc_woo_cloud_print_options', array());
        // This should only be set if authenticated
        if (isset($options['token'])) {
            $post     = array();
            $printers = $this->process_request('https://www.google.com/cloudprint/interface/search', $post, $options);
            if (is_wp_error($printers))
                return $printers;
            if (is_string($printers))
                $printers = json_decode($printers);
            if (is_object($printers) && isset($printers->success) && $printers->success == true && isset($printers->printers) && is_array($printers->printers)) {
                foreach ($printers->printers as $index => $printer) {
                    $get_printer_result = $this->process_request('https://www.google.com/cloudprint/printer', array(
                        'use_cdd' => 'true',
                        'printerid' => $printer->id
                    ), $options);
                    if (false !== $get_printer_result && null !== ($printer_result = json_decode($get_printer_result))) {
                        if (isset($printer_result->success) && true == $printer_result->success && isset($printer_result->printers) && is_array($printer_result->printers)) {
                            $printer                                        = $printer_result->printers[0];
                            //                             $hashed_id = md5($printer->id);
                            //                             set_transient('gcpl_popts_'.$hashed_id, $printer_result, 86400);
                            $printers->printers[$index]->_gcpl_printer_opts = $printer;
                        } //isset($printer_result->success) && true == $printer_result->success && isset($printer_result->printers) && is_array($printer_result->printers)
                    } //false !== $get_printer_result && null !== ($printer_result = json_decode($get_printer_result))
                } //$printers->printers as $index => $printer
                if (false !== $get_printer_result) {
                    set_transient('xc_woo_cloud_print_printers', $printers->printers, 86400);
                } //false !== $get_printer_result
                return $printers->printers;
            } //is_object($printers) && isset($printers->success) && $printers->success == true && isset($printers->printers) && is_array($printers->printers)
        } //isset($options['token'])
        return array();
    }
    public function xc_woo_cloud_print_options($options)
    {
        if (!empty($options))
            return $options;
        return get_option('xc_woo_cloud_print_options', array());
    }
    public function process_request($url, $post_fields, $options = array(), $referer = '')
    {
        $ret          = "";
        $wp_post_opts = array(
            'user-agent' => "XC Woo Google Cloud Print" . XC_WOO_CLOUD_VERSION,
            'headers' => array(
                'X-CloudPrint-Proxy' => "xc-woo-cloud-print",
                'Referer' => $referer
            ),
            'sslverify' => true,
            'redirection' => 5,
            'body' => $post_fields,
            'timeout' => 25
        );
        if (!empty($options['username']) && empty($options['clientid'])) {
            // Legacy/deprecated - tokens from the now-removed ClientLogin API
            $wp_post_opts['headers']['Authorization'] = "GoogleLogin auth=$token";
        } //!empty($options['username']) && empty($options['clientid'])
        else {
            $access_token = $this->access_token($options);
            if (is_wp_error($access_token) || empty($access_token))
                return $access_token;
            $wp_post_opts['headers']['Authorization'] = "Bearer $access_token";
        }
        $wp_post_opts = apply_filters('xc_woo_cloud_print_process_request_options', $wp_post_opts);
        $post         = wp_remote_post($url, $wp_post_opts);
        if (is_wp_error($post)) {
            error_log('POST error: ' . $post->get_error_code() . ': ' . $post->get_error_message());
            return $post;
        } //is_wp_error($post)
        if (!is_array($post['response']) || !isset($post['response']['code'])) {
            error_log('POST error: Unexpected response: ' . serialize($post));
            return false;
        } //!is_array($post['response']) || !isset($post['response']['code'])
        if ($post['response']['code'] >= 400 && $post['response']['code'] < 500) {
            $extra = '';
            error_log('POST error: Unexpected response (code ' . $post['response']['code'] . '): ' . serialize($post));
            return new WP_Error('http_badauth', $extra . "Authentication failed (" . $post['response']['code'] . "): " . $post['body']);
        } //$post['response']['code'] >= 400 && $post['response']['code'] < 500
        if ($post['response']['code'] >= 400) {
            error_log('POST error: Unexpected response (code ' . $post['response']['code'] . '): ' . serialize($post));
            return new WP_Error('http_error', 'POST error: Unexpected response (code ' . $post['response']['code'] . '): ' . serialize($post));
        } //$post['response']['code'] >= 400
        return $post['body'];
    }
    private function access_token($options)
    {
        $refresh_token = $options['token'];
        $query_body    = array(
            'refresh_token' => $refresh_token,
            'client_id' => $options['clientid'],
            'client_secret' => $options['clientsecret'],
            'grant_type' => 'refresh_token'
        );
        $result        = wp_remote_post('https://accounts.google.com/o/oauth2/token', array(
            'timeout' => 15,
            'method' => 'POST',
            'body' => $query_body
        ));
        if (is_wp_error($result)) {
            return $result;
        } //is_wp_error($result)
        else {
            $json_values = json_decode(wp_remote_retrieve_body($result), true);
            if (isset($json_values['access_token'])) {
                //                 error_log("Google Drive: successfully obtained access token");
                return $json_values['access_token'];
            } //isset($json_values['access_token'])
            else {
                //                 error_log("Google Drive error when requesting access token: response does not contain access_token");
                if (!empty($json_values['error']) && !empty($json_values['error_description'])) {
                    return new WP_Error($json_values['error'], $json_values['error_description']);
                } //!empty($json_values['error']) && !empty($json_values['error_description'])
                return false;
            }
        }
    }
    private function generate_selector($printer_id, $cap_id, $capabilities, $title = '', $current_options)
    {
        if ('__google__docs' == $printer_id || !is_array($capabilities) || empty($capabilities[$cap_id]))
            return '';
        $cap = $capabilities[$cap_id];
        if (!is_object($cap) || !isset($cap->option) || !is_array($cap->option))
            return '';
        $option      = $cap->option;
        $selector_id = esc_attr('gcpl_capability_' . $printer_id . '_' . $cap_id);
        $output      = '<label for="' . $selector_id . '">' . htmlspecialchars($title) . ':</label> ';
        $output .= '<select name="google_cloud_print_library_options[printer_options][' . esc_attr($printer_id) . '][' . esc_attr($cap_id) . ']" onchange="google_cloud_print_confirm_unload = true;" id="' . $selector_id . '">' . "\n";
        $selected = null;
        if (isset($current_options['printer_options'][$printer_id][$cap_id])) {
            if (null !== ($decode_current_option = json_decode($current_options['printer_options'][$printer_id][$cap_id]))) {
                if (isset($decode_current_option->label))
                    $selected = $decode_current_option->label;
            } //null !== ($decode_current_option = json_decode($current_options['printer_options'][$printer_id][$cap_id]))
        } //isset($current_options['printer_options'][$printer_id][$cap_id])
        foreach ($option as $opt) {
            if ('fit_to_page' == $cap_id) {
                $values_to_save = array(
                    'type'
                );
                //                 $name = $opt->type;
                if ('NO_FITTING' == $opt->type) {
                    $label = __('Do not fit to page', XC_WOO_CLOUD);
                } //'NO_FITTING' == $opt->type
                elseif ('FIT_TO_PAGE' == $opt->type) {
                    $label = __('Fit to page', XC_WOO_CLOUD);
                } //'FIT_TO_PAGE' == $opt->type
                else {
                    $label = $opt->type;
                }
            } //'fit_to_page' == $cap_id
            elseif ('page_orientation' == $cap_id) {
                $values_to_save = array(
                    'type'
                );
                if ('PORTRAIT' == $opt->type) {
                    $label = __('Portrait', XC_WOO_CLOUD);
                } //'PORTRAIT' == $opt->type
                elseif ('LANDSCAPE' == $opt->type) {
                    $label = __('Landscape', XC_WOO_CLOUD);
                } //'LANDSCAPE' == $opt->type
                    elseif ('AUTO' == $opt->type) {
                    $label = __('Automatic', XC_WOO_CLOUD);
                } //'AUTO' == $opt->type
                else {
                    $label = $opt->type;
                }
            } //'page_orientation' == $cap_id
                elseif ('color' == $cap_id) {
                $values_to_save = array(
                    'type'
                );
                if ('STANDARD_MONOCHROME' == $opt->type) {
                    $label = __('Monochrome', XC_WOO_CLOUD);
                } //'STANDARD_MONOCHROME' == $opt->type
                elseif ('STANDARD_COLOR' == $opt->type) {
                    $label = __('Color', XC_WOO_CLOUD);
                } //'STANDARD_COLOR' == $opt->type
                    elseif ('AUTO' == $opt->type) {
                    $label = __('Automatic', XC_WOO_CLOUD);
                } //'AUTO' == $opt->type
                else {
                    // Drop CUSTOM_COLOR, CUSTOM_MONOCHROME
                    continue;
                }
            } //'color' == $cap_id
            else {
                $values_to_save = array(
                    'width_microns',
                    'height_microns',
                    'is_continuous_feed'
                );
                //                 $name = $opt->name;
                $label          = $opt->custom_display_name;
            }
            $value = array();
            foreach ($values_to_save as $key) {
                $value[$key] = isset($opt->$key) ? $opt->$key : false;
            } //$values_to_save as $key
            $value['label'] = $label;
            $output .= '<option value="' . esc_attr(json_encode($value)) . '"';
            if ((null === $selected && !empty($opt->is_default)) || (null !== $selected && $label == $selected))
                $output .= ' selected="selected"';
            $output .= '>';
            $output .= htmlspecialchars($label);
            $output .= '</option>';
        } //$option as $opt
        $output .= "</select><br>\n";
        return $output;
    }
    public function add_action_links($links)
    {
        $mylinks = array(
            '<a href="' . admin_url('options-general.php?page=xc_woo_cloud_settings') . '">Settings</a>',
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=xc_woo_cloud') . '">Cloud Print Options</a>'
        );
        return array_merge($links, $mylinks);
    }
}