<?php

return [
	
	'provider' => [

		'google' => [

			'route'			=> 'route',
			'street_number'	=> 'street_number',
			'postal_code'	=> 'postal_code',
			'city'			=> 'locality',
			'state'			=> 'administrative_area_level_1',
			'region'		=> 'administrative_area_level_2',
			'country'		=> 'country',

		],

		'algolia' => [

			'route'			=> 'locale_names',
			'street_number'	=> '',
			'postal_code'	=> 'postcode',
			'city'			=> 'city',
			'state'			=> 'administrative',
			'region'		=> 'county',
			'country'		=> 'country',

		],

	],

];
