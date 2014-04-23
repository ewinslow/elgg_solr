<?php

if (elgg_get_plugin_setting('reindex_running', 'elgg_solr')) {
	register_error(elgg_echo('elgg_solr:error:index_running'));
	forward(REFERER);
}

$type = get_input('type');
switch ($type) {
	case 'comments':
		elgg_register_event_handler('shutdown', 'system', 'elgg_solr_comment_reindex');
		break;
	case '':
	case 'full':
		//vroomed
		elgg_register_event_handler('shutdown', 'system', 'elgg_solr_reindex');
		elgg_register_event_handler('shutdown', 'system', 'elgg_solr_comment_reindex');
		break;
	default:
		// set up options to use instead of all registered types
		$types = array($type => get_input('subtype', array()));
		elgg_set_config('elgg_solr_reindex_options', $types);
		elgg_register_event_handler('shutdown', 'system', 'elgg_solr_reindex');
		break;
}

system_message(elgg_echo('elgg_solr:success:reindex'));
forward(REFERER);
