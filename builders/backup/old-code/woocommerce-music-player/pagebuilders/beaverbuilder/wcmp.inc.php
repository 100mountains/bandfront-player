<?php
require_once dirname( __FILE__ ) . '/wcmp/wcmp.pb.php';

FLBuilder::register_module(
	'WCMPBeaver',
	array(
		'wcmp-tab' => array(
			'title'    => __( 'Enter the products ids and the rest of shortcode attributes', 'music-player-for-woocommerce' ),
			'sections' => array(
				'wcmp-section' => array(
					'title'  => __( 'Playlist Shortcode', 'music-player-for-woocommerce' ),
					'fields' => array(
						'products_ids' => array(
							'type'  => 'text',
							'label' => __( 'Products ids separated by comma (* represents all products)', 'music-player-for-woocommerce' ),
						),
						'attributes'   => array(
							'type'  => 'text',
							'label' => __( 'Additional attributes', 'music-player-for-woocommerce' ),
						),
					),
				),
			),
		),
	)
);
