<?php
/**
 * Plugin Name: SportMonks Middleware Connector
 * Plugin URI: https://github.com/yourrepo/sportmonks-middleware
 * Description: เชื่อมต่อกับ SportMonks Middleware API เพื่อแสดงผลคะแนนบอลสด, ตารางแข่งขัน, และตารางคะแนน
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sportmonks-middleware
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// ป้องกันการเข้าถึงโดยตรง
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SMM_VERSION', '1.0.0');
define('SMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SMM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class SportMonks_Middleware_Connector {
    
    private static $instance = null;
    private $api_base_url;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->api_base_url = get_option('smm_api_url', 'http://localhost:3000/api');
        
        // Hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Shortcodes
        add_shortcode('live_scores', array($this, 'live_scores_shortcode'));
        add_shortcode('league_fixtures', array($this, 'league_fixtures_shortcode'));
        add_shortcode('league_standings', array($this, 'league_standings_shortcode'));
        add_shortcode('team_fixtures', array($this, 'team_fixtures_shortcode'));
        add_shortcode('season_schedule', array($this, 'season_schedule_shortcode'));
        add_shortcode('team_schedule', array($this, 'team_schedule_shortcode'));
        add_shortcode('team_season_schedule', array($this, 'team_season_schedule_shortcode'));
        add_shortcode('fixtures_by_date', array($this, 'fixtures_by_date_shortcode'));
        
        // Widgets
        add_action('widgets_init', array($this, 'register_widgets'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'SportMonks Middleware Settings',
            'SportMonks API',
            'manage_options',
            'sportmonks-middleware',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('smm_settings', 'smm_api_url');
        register_setting('smm_settings', 'smm_cache_duration');
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>SportMonks Middleware Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smm_settings'); ?>
                <?php do_settings_sections('smm_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="smm_api_url">API Base URL</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="smm_api_url" 
                                   name="smm_api_url" 
                                   value="<?php echo esc_attr(get_option('smm_api_url', 'http://localhost:3000/api')); ?>" 
                                   class="regular-text" />
                            <p class="description">ใส่ URL ของ Middleware API (เช่น http://localhost:3000/api หรือ https://api.yourdomain.com/api)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="smm_cache_duration">Cache Duration (วินาที)</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="smm_cache_duration" 
                                   name="smm_cache_duration" 
                                   value="<?php echo esc_attr(get_option('smm_cache_duration', '300')); ?>" 
                                   min="60" 
                                   class="small-text" />
                            <p class="description">ระยะเวลา Cache ข้อมูลใน WordPress (แนะนำ 300 วินาที = 5 นาที)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2>การใช้งาน Shortcodes</h2>
            <h3>1. Live Scores</h3>
            <code>[live_scores]</code>
            <p>แสดงคะแนนสดของวันนี้</p>
            
            <h3>2. League Fixtures (ตารางแข่งขัน)</h3>
            <code>[league_fixtures league_id="8"]</code>
            <p>แสดงตารางแข่งขันของลีก (league_id="8" = Premier League)</p>
            
            <h3>3. League Standings (ตารางคะแนน)</h3>
            <code>[league_standings league_id="8"]</code>
            <p>แสดงตารางคะแนนของลีก</p>
            
            <h3>4. Team Fixtures</h3>
            <code>[team_fixtures team_id="1"]</code>
            <p>แสดงแมตช์ของทีม</p>
            
            <h3>5. Season Schedule (ตารางการแข่งขันทั้งซีซัน)</h3>
            <code>[season_schedule season_id="23538"]</code>
            <p>แสดงตารางการแข่งขันทั้งซีซัน</p>
            
            <h3>6. Team Schedule (โปรแกรมการแข่งของทีม)</h3>
            <code>[team_schedule team_id="1"]</code>
            <p>แสดงตารางการแข่งขันของทีม (ซีซันปัจจุบัน)</p>
            
            <h3>7. Team Season Schedule (ตารางแข่งของทีมในซีซันที่เลือก)</h3>
            <code>[team_season_schedule season_id="23538" team_id="1"]</code>
            <p>แสดงตารางการแข่งขันของทีมในซีซันที่เลือก</p>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'smm-styles',
            SMM_PLUGIN_URL . 'assets/css/style.css',
            array(),
            SMM_VERSION
        );
        
        wp_enqueue_script(
            'smm-scripts',
            SMM_PLUGIN_URL . 'assets/js/script.js',
            array('jquery'),
            SMM_VERSION,
            true
        );
    }
    
    /**
     * API Request Helper
     */
    private function api_request($endpoint, $params = array()) {
        $cache_key = 'smm_' . md5($endpoint . serialize($params));
        $cache_duration = get_option('smm_cache_duration', 300);
        
        // Check cache
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }
        
        // Build URL
        $url = trailingslashit($this->api_base_url) . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        // Make request
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Debug: บันทึก response
        error_log('SportMonks API Response: ' . print_r($data, true));
        error_log('SportMonks API URL: ' . $url);
        
        if (!isset($data['success']) || !$data['success']) {
            $error_msg = isset($data['message']) ? $data['message'] : 'API returned error';
            return array('error' => $error_msg . ' (URL: ' . $url . ')');
        }
        
        // Cache result
        set_transient($cache_key, $data, $cache_duration);
        
        return $data;
    }
    
    /**
     * Live Scores Shortcode
     */
    public function live_scores_shortcode($atts) {
        $atts = shortcode_atts(array(
            'date' => 'today'
        ), $atts);
        
        $data = $this->api_request('livescores', array('date' => $atts['date']));
        
        if (isset($data['error'])) {
            return '<p class="smm-error">ไม่สามารถโหลดข้อมูลได้: ' . esc_html($data['error']) . '</p>';
        }
        
        ob_start();
        include SMM_PLUGIN_DIR . 'templates/live-scores.php';
        return ob_get_clean();
    }
    
    /**
     * League Fixtures Shortcode
     */
    public function league_fixtures_shortcode($atts) {
        $atts = shortcode_atts(array(
            'league_id' => '',
            'season' => ''
        ), $atts);
        
        if (empty($atts['league_id'])) {
            return '<p class="smm-error">กรุณาระบุ league_id</p>';
        }
        
        $params = array();
        if (!empty($atts['season'])) {
            $params['season'] = $atts['season'];
        }
        
        $data = $this->api_request('fixtures/league/' . $atts['league_id'], $params);
        
        if (isset($data['error'])) {
            return '<p class="smm-error">ไม่สามารถโหลดข้อมูลได้: ' . esc_html($data['error']) . '</p>';
        }
        
        ob_start();
        include SMM_PLUGIN_DIR . 'templates/fixtures.php';
        return ob_get_clean();
    }
    
    /**
     * League Standings Shortcode
     */
    public function league_standings_shortcode($atts) {
        $atts = shortcode_atts(array(
            'league_id' => '',
            'season' => ''
        ), $atts);
        
        if (empty($atts['league_id'])) {
            return '<p class="smm-error">กรุณาระบุ league_id</p>';
        }
        
        $params = array();
        if (!empty($atts['season'])) {
            $params['season'] = $atts['season'];
        }
        
        $data = $this->api_request('standings/league/' . $atts['league_id'], $params);
        
        if (isset($data['error'])) {
            return '<p class="smm-error">ไม่สามารถโหลดข้อมูลได้: ' . esc_html($data['error']) . '</p>';
        }
        
        ob_start();
        include SMM_PLUGIN_DIR . 'templates/standings.php';
        return ob_get_clean();
    }
    
    /**
     * Team Fixtures Shortcode
     */
    public function team_fixtures_shortcode($atts) {
        $atts = shortcode_atts(array(
            'team_id' => '',
            'date' => ''
        ), $atts);
        
        if (empty($atts['team_id'])) {
            return '<p class="smm-error">กรุณาระบุ team_id</p>';
        }
        
        $params = array();
        if (!empty($atts['date'])) {
            $params['date'] = $atts['date'];
        }
        
        $data = $this->api_request('fixtures/team/' . $atts['team_id'], $params);
        
        if (isset($data['error'])) {
            return '<p class="smm-error">ไม่สามารถโหลดข้อมูลได้: ' . esc_html($data['error']) . '</p>';
        }
        
        ob_start();
        include SMM_PLUGIN_DIR . 'templates/fixtures.php';
        return ob_get_clean();
    }
    
    /**
     * Season Schedule Shortcode
     */
    public function season_schedule_shortcode($atts) {
        $atts = shortcode_atts(array(
            'season_id' => ''
        ), $atts);
        
        if (empty($atts['season_id'])) {
            return '<p class="smm-error">กรุณาระบุ season_id</p>';
        }
        
        $data = $this->api_request('schedules/season/' . $atts['season_id']);
        
        if (isset($data['error'])) {
            return '<p class="smm-error">ไม่สามารถโหลดข้อมูลได้: ' . esc_html($data['error']) . '</p>';
        }
        
        ob_start();
        include SMM_PLUGIN_DIR . 'templates/schedules.php';
        return ob_get_clean();
    }
    
    /**
     * Team Schedule Shortcode
     */
    public function team_schedule_shortcode($atts) {
        $atts = shortcode_atts(array(
            'team_id' => ''
        ), $atts);
        
        if (empty($atts['team_id'])) {
            return '<p class="smm-error">กรุณาระบุ team_id</p>';
        }
        
        $data = $this->api_request('schedules/team/' . $atts['team_id']);
        
        if (isset($data['error'])) {
            return '<p class="smm-error">ไม่สามารถโหลดข้อมูลได้: ' . esc_html($data['error']) . '</p>';
        }
        
        ob_start();
        include SMM_PLUGIN_DIR . 'templates/schedules.php';
        return ob_get_clean();
    }
    
    /**
     * Team Season Schedule Shortcode
     */
    public function team_season_schedule_shortcode($atts) {
        $atts = shortcode_atts(array(
            'season_id' => '',
            'team_id' => ''
        ), $atts);
        
        if (empty($atts['season_id']) || empty($atts['team_id'])) {
            return '<p class="smm-error">กรุณาระบุ season_id และ team_id</p>';
        }
        
        $data = $this->api_request('schedules/season/' . $atts['season_id'] . '/team/' . $atts['team_id']);
        
        if (isset($data['error'])) {
            return '<p class="smm-error">ไม่สามารถโหลดข้อมูลได้: ' . esc_html($data['error']) . '</p>';
        }
        
        ob_start();
        include SMM_PLUGIN_DIR . 'templates/schedules.php';
        return ob_get_clean();
    }
    
    /**
     * Fixtures by Date Shortcode (แมตช์ตามวันที่ แบ่งกลุ่มตามลีก)
     */
    public function fixtures_by_date_shortcode($atts) {
        $atts = shortcode_atts(array(
            'date' => 'today' // today หรือ YYYY-MM-DD
        ), $atts);
        
        $endpoint = ($atts['date'] === 'today') 
            ? 'fixtures/today' 
            : 'fixtures/date/' . $atts['date'];
        
        $data = $this->api_request($endpoint);
        
        if (isset($data['error'])) {
            return '<p class="smm-error">ไม่สามารถโหลดข้อมูลได้: ' . esc_html($data['error']) . '</p>';
        }
        
        ob_start();
        include SMM_PLUGIN_DIR . 'templates/fixtures-by-date.php';
        return ob_get_clean();
    }
    
    /**
     * Register Widgets
     */
    public function register_widgets() {
        // Widget registration will be added here
    }
}

// Initialize plugin
function sportmonks_middleware_init() {
    return SportMonks_Middleware_Connector::get_instance();
}
add_action('plugins_loaded', 'sportmonks_middleware_init');
