<?php

return array(
	'production' => array(
		'auth_db_r' => 'auth_db_r',
		'auth_db_w' => 'auth_db_w',
		'content_db_r' => 'content_db_r',
		'content_db_w' => 'content_db_w',
		'content_mc' => 'content_mc',
	),
	'dev' => array(
		'auth_db_r' => 'dev/auth_db_r',
		'auth_db_w' => 'dev/auth_db_w',
		'content_db_r' => 'dev/content_db_r',
		'content_db_w' => 'dev/content_db_w',
		'content_mc' => 'dev/content_mc',
	),
);
