<?php

function elgg_solr_add_update_entity($event, $type, $entity) {
	$debug = false;
	if (elgg_get_config('elgg_solr_debug')) {
		$debug = true;
	}
	
	
	if (!elgg_instanceof($entity)) {
		if ($debug) {
			elgg_solr_debug_log('Not a valid elgg entity');
		}
		return true;
	}
	
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		if ($debug) {
			elgg_solr_debug_log('Not a registered entity type');
		}
		return true;
	}
	
	$function = elgg_solr_get_solr_function($entity->type, $entity->getSubtype());
	
	if (is_callable($function)) {
		if ($debug) {
			elgg_solr_debug_log('processing entity with function - ' . $function);
		}
		
		$function($entity);
	}
	else {
		if ($debug) {
			elgg_solr_debug_log('Not a callable function - ' . $function);
		}
	}
}



function elgg_solr_delete_entity($event, $type, $entity) {
	
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		return true;
	}
	
	elgg_solr_push_doc('<delete><query>id:' . $entity->guid . '</query></delete>');

    return true;
}



function elgg_solr_metadata_update($event, $type, $metadata) {
	$guids = elgg_get_config('elgg_solr_sync');
	$guids[$metadata->entity_guid] = 1; // use key to keep it unique
	
	elgg_set_config('elgg_solr_sync', $guids);
}



function elgg_solr_add_update_annotation($event, $type, $annotation) {
	if (!($annotation instanceof ElggAnnotation)) {
		return true;
	}
	
	if ($annotation->name != 'generic_comment') {
		return true;
	}

    $description = elgg_solr_xml_format($annotation->value);
	$subtype = elgg_solr_xml_format($annotation->name);

    // Build the user document to be posted
    $doc = <<<EOF
        <add>
            <doc>
				<field name="id">annotation:{$annotation->id}</field>
				<field name="description">{$description}</field>
                <field name="type">annotation</field>
				<field name="subtype">{$subtype}</field>
				<field name="access_id">{$annotation->access_id}</field>
				<field name="container_guid">{$annotation->entity_guid}</field>
				<field name="owner_guid">{$annotation->owner_guid}</field>
				<field name="time_created">{$annotation->time_created}</field>
            </doc>
        </add>
EOF;

	elgg_solr_push_doc($doc);
}


function elgg_solr_delete_annotation($event, $type, $annotation) {

	if ($annotation->name != 'generic_comment') {
		return true;
	}

	elgg_solr_push_doc('<delete><query>id:annotation%%' . $annotation->id . '</query></delete>');

    return true;
}


// reindexes entities by guid
// happens after shutdown thanks to vroom
// entity guids stored in config
function elgg_solr_entities_sync() {
	$guids = elgg_get_config('elgg_solr_sync');
	
	if (!$guids) {
		return true;
	}
	
	$options = array(
		'guids' => array_keys($guids),
		'limit' => false
	);
	
	$batch_size = elgg_get_plugin_setting('reindex_batch_size', 'elgg_solr');
	$entities = new ElggBatch('elgg_get_entities', $options, null, $batch_size);
	
	foreach ($entities as $e) {
		elgg_solr_add_update_entity(null, null, $e);
	}
}



function elgg_solr_profile_update($event, $type, $entity) {
	elgg_solr_add_update_user($entity);
}