<?php

namespace ODS;

defined( 'ABSPATH' ) || exit;

/**
 * Updater plugin class inspired by Misha Rudrastyh
 * https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
 */
class Updater {

	/**
	 * Plugin slug
	 *
	 * @var string $plugin_slug Plugin slug.
	 */
	private $plugin_slug;

	/**
	 * Current theme or plugin version
	 *
	 * @var string $version Current theme or plugin version.
	 */
	private $version;

	/**
	 * Cache key
	 *
	 * @var string $cache_key Cache key.
	 */
	private $cache_key;

	/**
	 * Cache allowed
	 *
	 * @var bool $cache_allowed Cache allowed.
	 */
	private $cache_allowed;

	/**
	 * JSON URL
	 *
	 * @var string $json_url JSON URL for plugin information.
	 */
	private $json_url;

	private $license;

	/**
	 * Construct
	 *
	 * @param array $args Arguments.
	 *
	 * @return void
	 */
	public function __construct( $args ) {

		$this->plugin_slug   = $args['plugin_slug'];
		$this->version       = $args['version'];
		$this->cache_key     = $args['plugin_slug'] . '_updater';
		$this->json_url      = $args['json_url'];
		$this->cache_allowed = false;
		$this->license       = $args['license'] ? $args['license'] : '';

		add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update' ) );
		add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

	}

	/**
	 * The information on update popup
	 *
	 * @param object $response Response.
	 * @param string $action Action.
	 * @param object $args Arguments.
	 *
	 * @return object $response Response.
	 */
	public function info( $response, $action, $args ) {

		if ( 'plugin_information' !== $action ) {
			return $response;
		}

		if ( empty( $args->slug ) || $this->plugin_slug !== $args->slug ) {
			return $response;
		}

		$remote = $this->request();

		if ( ! $remote ) {
			return $response;
		}

		$response = new \stdClass();

		$response->name           = $remote->name;
		$response->slug           = $remote->slug;
		$response->version        = $remote->version;
		$response->tested         = $remote->tested;
		$response->requires       = $remote->requires;
		$response->author         = $remote->author;
		$response->author_profile = $remote->author_profile;
		$response->homepage       = $remote->homepage;
		$response->download_link  = $remote->download_url;
		$response->trunk          = $remote->download_url;
		$response->requires_php   = $remote->requires_php;
		$response->last_updated   = $remote->last_updated;

		$response->sections = array(
			'description'  => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog'    => $remote->sections->changelog,
		);

		if ( ! empty( $remote->banners ) ) {
			$response->banners = array(
				'low'  => $remote->banners->low,
				'high' => $remote->banners->high,
			);
		}

		return $response;

	}

	/**
	 * Get the remote json data
	 *
	 * @return object $remote Remote Json data.
	 */
	public function request() {

		$remote = get_transient( $this->cache_key );

		if ( false === $remote || ! $this->cache_allowed ) {

			$request_url = $this->license ? add_query_arg( array( 'license' => $this->license ), $this->json_url ) : $this->json_url;

			$remote = wp_remote_get(
				$request_url,
				array(
					'timeout' => 60,
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);

			if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
				return false;
			}

			set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );

		return $remote;

	}

	/**
	 * Update
	 *
	 * @param object $transient Transient.
	 *
	 * @return object $transient Transient.
	 */
	public function update( $transient ) {

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = $this->request();

		if ( $remote && version_compare( $this->version, $remote->version, '<' ) && version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) && version_compare( $remote->requires_php, PHP_VERSION, '<' ) ) {
			$response              = new \stdClass();
			$response->slug        = $this->plugin_slug;
			$response->plugin      = "{$this->plugin_slug}/{$this->plugin_slug}.php";
			$response->new_version = $remote->version;
			$response->tested      = $remote->tested;
			$response->package     = $remote->download_url;

			$transient->response[ $response->plugin ] = $response;

		}

		return $transient;

	}

	/**
	 * Purge cache
	 *
	 * @param object $upgrader Upgrader.
	 * @param array  $options Options.
	 *
	 * @return void
	 */
	public function purge( $upgrader, $options ) {
		if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			// just clean the cache when new plugin version is installed.
			delete_transient( $this->cache_key );
			do_action( 'ods_updater_after_purge', $upgrader, $options );
		}
	}
}
