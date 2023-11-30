<?php

/**
 * Plugin Name:       WP simple form
 * Plugin URI:
 * Description:       Plugin Description
 * Version:           1.0.0
 * Author:            Raimundo Yabar
 * Author URI:
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires PHP: 7.4
 * Text Domain:       text-domain
 */

declare(strict_types=1);

namespace WPSimpleForm\Core;

use WP_REST_Response;
use WP_REST_Request;
use Exception;
use WP_REST_Server;
use WP_Error;
use stdClass;
use Error;

if (!defined('WPINC')) {
	die;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @author     Raimundo <raimundo.yabar@gmail.com>
 * phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * phpcs:disable PSR1.Files.SideEffects
 */
final class wpsimpleform
{

	/**
	 * The only instance of the class
	 *
	 * @var Object
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * The Plug-in version.
	 *
	 * @var string
	 * @since 1.0
	 */
	private $version = '1.0.0';

	/**
	 * The minimal required version of WordPress for this plug-in to function correctly.
	 *
	 * @var string
	 * @since 1.0
	 */
	private $wp_version = '5.0';

	/**
	 * The minimal required version of WordPress for this plug-in to function correctly.
	 *
	 * @var string
	 * @since 1.0
	 */
	private $php_version = '7.2';

	/**
	 * Class name
	 *
	 * @var string
	 * @since 1.0
	 */
	private $class_name;

	/**
	 * Create a new instance of the main class
	 *
	 * @since 1.0
	 * @static
	 * @return Object
	 */
	public static function instance(): object
	{
		$class_name = get_class();
		if (!isset(self::$instance) && !(self::$instance instanceof $class_name)) {
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Begins execution of the plugin.
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @return	void
	 */
	public function run(): void
	{
		// Save the class name for later use
		$this->class_name = get_class();

		// Plug-in requirements
		$this->define_constants();

		register_activation_hook(__FILE__, [$this, 'activate']);
		register_deactivation_hook(__FILE__, [$this, 'deactivate']);

		if (!$this->check_requirements()) {
			return;
		}

		try {
			add_action('plugins_loaded', [$this, 'initialize']);
		} catch (exception $e) {
			$error = $e->getMessage() . "\n";
			error_log($error);
		}
	}

	/**
	 * initialize
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @return	void
	 */
	public function initialize(): void
	{
		//add shortcodes
		add_shortcode('my_form', [$this, 'addNewFormShortcode']);
		add_shortcode('my_list', [$this, 'displayDataShortcode']);

		//add script
		add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);

		//add styles
		add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);

		// Register Rest Routes
		add_action('rest_api_init', [$this, 'registerRestRoute']);
	}

	/**
	 * register Rest Route.
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Thursday, November 30th, 2023.
	 * @access	public
	 * @return	void
	 */
	public function registerRestRoute(): void
	{
		register_rest_route('simpleform/v1', '/insert_data/', [
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => [$this, 'insertDataRestEndpoint'],
			'permission_callback' => '__return_true',
			'args'                => [
				'fullname'          => [
					'required'          => true,
					'description'       => __('Full name', 'simpleform'),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'username'          => [
					'required'          => true,
					'description'       => __('username', 'simpleform'),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'email'          => [
					'required'          => true,
					'description'       => __('email', 'simpleform'),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'address'          => [
					'required'          => true,
					'description'       => __('address', 'simpleform'),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'phone'          => [
					'required'          => true,
					'description'       => __('phone', 'simpleform'),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'website'          => [
					'required'          => true,
					'description'       => __('website', 'simpleform'),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
				'company'          => [
					'required'          => true,
					'description'       => __('company', 'simpleform'),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
			],
		]);

		register_rest_route('simpleform/v1', '/list_data/', [
			'methods' => WP_REST_Server::READABLE,
			'callback' =>  [$this, 'listDataRestEndpoint'],
			'permission_callback' => '__return_true',
			'args'                => [
				'search'          => [
					'required'          => false,
					'description'       => __('search', 'simpleform'),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				],
			],
		]);

		register_rest_route('simpleform/v1', '/list_data/(?P<id>\d+)', [
			'methods' => WP_REST_Server::READABLE,
			'callback' =>  [$this, 'obtainSingleRestEndpoint'],
			'permission_callback' => '__return_true',
			'args'                => [
				'id' => [
					'required' => true,
					'type'     => 'integer',
				],
			],
		]);
	}

	/**
	 * Obtain Records via Rest Endpoint.
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Thursday, November 30th, 2023.
	 * @access	public
	 * @param	wp_rest_request $request
	 * @return	mixed
	 */
	public function listDataRestEndpoint(WP_REST_Request $request): ?object
	{
		try {
			$search = strval($request->get_param('search')) ?? '';
			$response = $this->repoReadAll($search);
		} catch (Exception $e) {
			return new WP_Error('error', $e->getMessage());
		}

		return new WP_REST_Response($response, 200);
	}

	/**
	 * Obtain Single Record via Rest Endpoint.
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Thursday, November 30th, 2023.
	 * @access	public
	 * @param	wp_rest_request $request
	 * @return	mixed
	 */
	public function obtainSingleRestEndpoint(WP_REST_Request $request): ?object
	{
		try {
			$id   = intval($request->get_param('id'));
			$response = $this->repoReadById($id);
		} catch (Exception $e) {
			return new WP_Error('error', $e->getMessage());
		}

		return new WP_REST_Response($response, 200);
	}

	/**
	 * insert Data via Rest Endpoint.
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Thursday, November 30th, 2023.
	 * @access	public
	 * @param	wp_rest_request $request
	 * @return	mixed
	 */
	public function insertDataRestEndpoint(WP_REST_Request $request): ?object
	{
		try {
			$response = $this->repoCreate(
				[
					"fullname" => strval($request->get_param('fullname')),
					"username" => strval($request->get_param('username')),
					"email" => strval($request->get_param('email')),
					"address" => strval($request->get_param('address')),
					"phone" => strval($request->get_param('phone')),
					"website" => strval($request->get_param('website')),
					"company" => strval($request->get_param('company')),
				]
			);
		} catch (Exception $e) {
			return new WP_Error('error', $e->getMessage());
		}

		return new WP_REST_Response($response, 200);
	}

	/**
	 * Read all records
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Thursday, November 30th, 2023.
	 * @access	private
	 * @return	array
	 */
	private function repoReadAll(string $search = ''): array
	{
		global $wpdb;
		$sql = "SELECT * FROM `{$wpdb->prefix}my_custom_table` ";
		if (!empty($search)) {
			$sql .= " WHERE fullname LIKE '%$search%' OR ";
			$sql .= "username LIKE '%$search%' OR ";
			$sql .= "email LIKE '%$search%' OR ";
			$sql .= "address LIKE '%$search%' OR ";
			$sql .= "phone LIKE '%$search%' OR ";
			$sql .= "website LIKE '%$search%' OR ";
			$sql .= "company LIKE '%$search%'";
		}
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$queryResult = $wpdb->get_results($sql);
		if (empty($queryResult)) return [];
		return $queryResult;
	}

	/**
	 *  Read record By Id
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Thursday, November 30th, 2023.
	 * @access	private
	 * @param	int $id
	 * @return	mixed
	 */
	private function repoReadById(int $id): object
	{
		global $wpdb;
		$queryResult = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}my_custom_table` WHERE id = %d;", $id));
		if (empty($queryResult)) return new stdClass();
		return $queryResult;
	}

	/**
	 * repo create record
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Thursday, November 30th, 2023.
	 * @access	private
	 * @param	array   $data
	 * @return	mixed
	 */
	private function repoCreate(array $data): object
	{
		global $wpdb;

		$ok   = $wpdb->insert($wpdb->prefix . 'my_custom_table', $data);
		if (empty($ok)) throw new Error('Creating Record');
		$id = intval($wpdb->insert_id);
		return $this->repoReadById($id);
	}

	/**
	 * Register the stylesheets
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @return	void
	 */
	public function enqueueStyles(): void
	{
		wp_enqueue_style(
			WPSF_PLUGIN_NAME,
			plugin_dir_url(__FILE__) . 'style.css',
			[],
			WPSF_PLUGIN_DEBUG ? date('Ymdgis') : $this->version,
			'all'
		);

		wp_enqueue_style(
			'bootstrapcdn',
			'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css',
			[],
			$this->version,
			'all'
		);

		wp_enqueue_style(
			'fontawesome',
			'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.13.1/css/all.min.css',
			[],
			date('Ymdgis'),
			'all'
		);
	}

	/**
	 * Register JavaScript script
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @return	void
	 */
	public function enqueueScripts(): void
	{
		wp_enqueue_script(
			WPSF_PLUGIN_NAME,
			plugin_dir_url(__FILE__) . 'script.js',
			['jquery', 'wp-i18n'],
			WPSF_PLUGIN_DEBUG ? date('Ymdgis') : $this->version,
			false
		);

		$variables = [
			'api_url' => site_url('wp-json'),
		];

		wp_localize_script(WPSF_PLUGIN_NAME, 'wpsf_vars', $variables);

		wp_enqueue_script(
			'bootstrapcdn',
			'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js',
			['jquery'],
			$this->version,
			false
		);
	}

	/**
	 * Add new form
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @return	mixed
	 */
	public function addNewFormShortcode(): string
	{
		ob_start();
        ?>

		<form id="wpsf-add-new-form" class="wpsf-add-record alert alert-warning row gy-2 gx-3 align-items-center">
			<div class="col-auto">
				<input type="text" class="form-control" id="autoSizingInput" name="fullname" placeholder="<?php esc_attr_e('Full name', 'simpleform'); ?>">
			</div>
			<div class="col-auto">
				<div class="input-group">
					<div class="input-group-text">@</div>
					<input type="text" class="form-control" id="autoSizingInputGroup" name="username" placeholder="<?php esc_attr_e('Username', 'simpleform'); ?>">
				</div>
			</div>
			<div class="col-auto">
				<input type="text" class="form-control" id="autoSizingInput2" name="email" placeholder="<?php esc_attr_e('Email', 'simpleform'); ?>">
			</div>
			<div class="col-auto">
				<input type="text" class="form-control" id="autoSizingInput3" name="address" placeholder="<?php esc_attr_e('Address', 'simpleform'); ?>">
			</div>
			<div class="col-auto">
				<input type="text" class="form-control" id="autoSizingInput4" name="phone" placeholder="<?php esc_attr_e('Phone', 'simpleform'); ?>">
			</div>
			<div class="col-auto">
				<input type="text" class="form-control" id="autoSizingInput5" name="website" placeholder="<?php esc_attr_e('Website', 'simpleform'); ?>">
			</div>
			<div class="col-auto">
				<input type="text" class="form-control" id="autoSizingInput6" name="company" placeholder="<?php esc_attr_e('Company', 'simpleform'); ?>">
			</div>
			<div class="col-auto">
				<button type="submit" id="wpsf-button-submit" class="btn btn-primary btn-lg"><?php esc_html_e('Add new record', 'simpleform'); ?></button>
			</div>
		</form>
        <?php
		return ob_get_clean();
	}

	/**
	 * Display form
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @return	mixed
	 */
	public function displayDataShortcode(): string
	{
		ob_start();
        ?>
		<div class="wpsf-search-container alert alert-info">
			<div class="input-group rounded">
				<input type="search" id="wpsf-search-content" class="form-control rounded" placeholder="<?php esc_attr_e('Search record', 'simpleform'); ?>" aria-label="Search" aria-describedby="search-addon" />
				<span class="input-group-text border-0" id="wpsf-search-addon">
					<i class="fas fa-search"></i>
				</span>
			</div>
		</div>
		<div id="wpsf-show-data"></div>
		<!-- The Modal -->
		<div class="modal" id="myModal">
			<div class="modal-dialog">
				<div class="modal-content">

					<!-- Modal Header -->
					<div class="modal-header">
						<h4 class="modal-title"><?php esc_html_e('Record Details', 'simpleform'); ?></h4>
						<button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
					</div>

					<!-- Modal body -->
					<div class="modal-body">

					</div>

					<!-- Modal footer -->
					<div class="modal-footer">
						<button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo esc_html(__('Close', 'simpleform')); ?></button>
					</div>

				</div>
			</div>
		</div>
        <?php
		return ob_get_clean();
	}

	/**
	 * Prepares sites to use the plugin during single activation
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @param	boolean	$network_wide	Default: false
	 * @return	boolean
	 */
	public function activate(bool $network_wide = false): bool
	{
		//creating table

		global $wpdb;

		$table_name = $wpdb->prefix . 'my_custom_table';

		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE  TABLE IF NOT EXISTS $table_name (
		        id mediumint(9) NOT NULL AUTO_INCREMENT,
		        fullname VARCHAR(200) NOT NULL,
		        username VARCHAR(100) NOT NULL,
		        email VARCHAR(100) NOT NULL,
		        address VARCHAR(200) NOT NULL,
		        phone VARCHAR(100) NOT NULL,
		        website VARCHAR(100) NOT NULL,
		        company VARCHAR(100) NOT NULL,
		        PRIMARY KEY (id)
		    ) $charsetCollate;";
		$wpdb->query($sql);
		return true;
	}

	/**
	 * Rolls back activation procedures when de-activating the plugin
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @return	boolean
	 */
	public function deactivate(): bool
	{

		return true;
	}

	/**
	 * Checks that the WordPress setup meets the plugin requirements
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	private
	 * @return	boolean
	 */
	private function check_requirements(): bool
	{
		global $wp_version;
		if (!version_compare($wp_version, $this->wp_version, '>=')) {
			add_action('admin_notices', [$this, 'wpDisplayNotice'], 1);
			return false;
		}

		if (version_compare(PHP_VERSION, $this->php_version, '<')) {
			add_action('admin_notices', [$this, 'phpDisplayNotice'], 2);
			return false;
		}

		return true;
	}

	/**
	 * display requirement for PHP.
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @return	void
	 */
	public function phpDisplayNotice(): void
	{

		echo "<div class='notice notice-error is-dismissible'>";
		/* translators: 1: Opening <p> HTML element 2: Opening <strong> HTML element 3: Closing <strong> HTML element 4: Closing <p> HTML element  */
		echo sprintf(esc_html__('%1$s%2$s WP Simple Form NOTICE:%3$s PHP version too low to use this plugin. Please change to at least PHP 7.4. You can contact your web host for assistance in updating your PHP version.%4$s', 'text-domain'), '<p>', '<strong>', '</strong>', '</p>');
		echo  '</div>';
	}

	/**
	 * display requirement for WP
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	public
	 * @return	void
	 */
	public function wpDisplayNotice(): void
	{
		echo "<div class='notice notice-error is-dismissible'>";
		/* translators: 1: Opening <p> HTML element 2: Opening <strong> HTML element 3: Closing <strong> HTML element 4: Closing <p> HTML element  */
		echo sprintf(esc_html__('%1$s%2$s WP Simple Form NOTICE:%3$s WP version too low to use this plugin. Please change to at least WP 5.0. You can contact your web host for assistance in updating your PHP version.%4$s', 'text-domain'), '<p>', '<strong>', '</strong>', '</p>');
		echo  '</div>';
	}

	/**
	 * Define constants needed across the plug-in.
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Wednesday, November 29th, 2023.
	 * @access	private
	 * @return	void
	 */
	private function define_constants(): void
	{
		define('WPSF_BASE_FILE', basename(plugin_dir_path(__FILE__)));
		define('WPSF_PLUGIN_NAME', 'wp_simple_form');
		define('WPSF_PLUGIN_DIR', __DIR__ . '/');
		define('WPSF_PLUGIN_PATH_URL', plugin_dir_url(__FILE__));
		define('WPSF_PLUGIN_DEBUG', true);
	}
}

$pluginInstance = wpsimpleform::instance();
$pluginInstance->run();
