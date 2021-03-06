<?php
/**
 * REST API Data Download IP Controller
 *
 * Handles requests to /data/download-ips
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Data;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Controllers\Version4\Data as DataController;

/**
 * Data Download IP controller.
 */
class DownloadIPs extends DataController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'data/download-ips';

	/**
	 * Register routes.
	 *
	 * @since 3.5.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Return the download IPs matching the passed parameters.
	 *
	 * @since  3.5.0
	 * @param  \WP_REST_Request $request Request data.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		global $wpdb;

		if ( isset( $request['match'] ) ) {
			$downloads = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT( user_ip_address ) FROM {$wpdb->prefix}wc_download_log
					WHERE user_ip_address LIKE %s
					LIMIT 10",
					$request['match'] . '%'
				)
			);
		} else {
			return new \WP_Error( 'woocommerce_rest_data_download_ips_invalid_request', __( 'Invalid request. Please pass the match parameter.', 'woocommerce-rest-api' ), array( 'status' => 400 ) );
		}

		$data = array();

		if ( ! empty( $downloads ) ) {
			foreach ( $downloads as $download ) {
				$response = $this->prepare_item_for_response( (array) $download, $request );
				$data[]   = $this->prepare_response_for_collection( $response );
			}
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get data for this object in the format of this endpoint's schema.
	 *
	 * @param mixed            $object Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return mixed Array of data in the correct format.
	 */
	protected function get_data_for_response( $object, $request ) {
		return $object;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function prepare_links( $item, $request ) {
		$links = array(
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);
		return $links;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params            = array();
		$params['context'] = $this->get_context_param( array( 'default' => 'view' ) );
		$params['match']   = array(
			'description'       => __( 'A partial IP address can be passed and matching results will be returned.', 'woocommerce-rest-api' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}


	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'download_ip',
			'type'       => 'object',
			'properties' => array(
				'user_ip_address' => array(
					'type'        => 'string',
					'description' => __( 'IP address.', 'woocommerce-rest-api' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
