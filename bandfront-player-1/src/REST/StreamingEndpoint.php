<?php
declare(strict_types=1);

namespace Bandfront\REST;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class StreamingEndpoint extends WP_REST_Controller {
    
    protected string $namespace = 'bandfront-player/v1';
    protected string $base = 'stream';

    public function __construct() {
        $this->register_routes();
    }

    public function register_routes(): void {
        register_rest_route($this->namespace, '/' . $this->base . '/(?P<product_id>\d+)/(?P<track_index>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_stream_request'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_stream_request(WP_REST_Request $request): WP_REST_Response {
        $product_id = (int) $request->get_param('product_id');
        $track_index = (int) $request->get_param('track_index');

        // Logic to handle the streaming request
        // This should include validation and streaming logic

        return new WP_REST_Response([
            'status' => 'success',
            'message' => 'Streaming audio',
            'product_id' => $product_id,
            'track_index' => $track_index,
        ]);
    }
}