<?php
define('BGS_DIR', 'images/sprites/uploads/');
define('MAX_LAYERS', 5);
require_once('imageutils.php');
function handle_bg_upload($files,$options=array()) {
	global $language, $identifiants;
	$totalSize = file_total_size(isset($options['layer']) ? array('layer'=>$options['layer']):array());
	$layerFiles = array();
	foreach ($files['tmp_name'] as $i => $filePath) {
		$error = null;
		if (!$files['error'][$i]) {
			$poids = $files['size'][$i];
			if ($poids < 1000000) {
				$totalSize += file_total_size(isset($options['layer']) ? array('layer'=>$options['layer']):array());
				if ($totalSize < MAX_FILE_SIZE) {
					$ext = get_img_ext($filePath);
					$extensions = Array('png', 'gif', 'jpg', 'jpeg');
					if (in_array($ext, $extensions)) {
						$layerFiles[] = array(
							'path' => $filePath,
							'ext' => $ext
						);
					}
					else $error = $language ? 'Your image must have a png, gif, jpg or jpeg extension.':'Votre image doit &ecirc;tre au format png, gif, jpg ou jpeg.';
				}
				else $error = $language ? 'You have exceeded your quota of '.filesize_str(MAX_FILE_SIZE).'. Delete characters or circuits to free space.':'Vous avez d&eacute;pass&eacute; votre quota de '.filesize_str(MAX_FILE_SIZE).'. Supprimez des persos ou des circuits pour lib&eacute;rer de l\'espace disque.';
			}
			else $error = $language ? 'Your image mustn\'t exceed 1 Mo. Compress or reduce it if necessary.':'Votre image ne doit pas d&eacute;passer 1 Mo. Compressez-la ou r&eacute;duisez la taille si n&eacute;cessaire.';
		}
		else $error = $language ? 'An error occured during the image transfer. Please try again later.':'Une erreur est survenue lors de l\'envoi de l\'image. R&eacute;essayez ult&egrave;rieurement.';
	}
	unset($file);
	if ($error)
		return array('error' => $error);

	$ordering = 0;
	$nbLayers = count($layerFiles);
	$baseLayers = 0;
	if (isset($options['layer'])) {
		$getBgLayer = mysql_fetch_array(mysql_query('SELECT id,bg,filename FROM `mkbglayers` WHERE id="'. $options['layer'] .'"'));
		if (!$getBgLayer)
			return array('error' => 'Unknown error');
		$filePath = get_layer_path($getBgLayer['filename']);
		@unlink($filePath);
		$id = $getBgLayer['bg'];
	}
	elseif (isset($options['bg'])) {
		$id = $options['bg'];
		$getLayerStats = mysql_fetch_array(mysql_query('SELECT MAX(ordering) AS max, COUNT(*) AS nb FROM `mkbglayers` WHERE bg="'. $options['bg'] .'"'));
		$ordering = $getLayerStats['max']+1;
		$baseLayers = +$getLayerStats['nb'];
	}
	else {
		mysql_query('INSERT INTO `mkbgs` SET identifiant="'. $identifiants[0] .'"');
		$id = mysql_insert_id();
	}
	if (($baseLayers+$nbLayers) > MAX_LAYERS)
		$layerFiles = array_slice($layerFiles,0, MAX_LAYERS-$baseLayers);

	foreach ($layerFiles as $layerFile) {
		if (isset($options['layer']))
			$layerId = $options['layer'];
		else {
			mysql_query('INSERT INTO `mkbglayers` SET bg="'. $id .'",ordering="'. $ordering .'",filename=""');
			$layerId = mysql_insert_id();
		}
		$fileName = generate_layer_name($layerId, $layerFile['ext']);
		$filePath = get_layer_path($fileName);
		move_uploaded_file($layerFile['path'], $filePath);
		mysql_query('UPDATE mkbglayers SET filename="'. $fileName .'" WHERE id='. $layerId);
		$ordering++;
	}
	return array('id' => $id);
}
function generate_layer_name($id, $ext) {
	return 'bg-'.uniqid().'-'.$id.'.'.$ext;
}
function get_layer_path($fileName) {
	return BGS_DIR.$fileName;
}
function get_bg_layers($bgId) {
	$bgLayers = array();
	$getLayers = mysql_query('SELECT id,filename FROM mkbglayers WHERE bg="'. $bgId .'" ORDER BY ordering');
	while ($getLayer = mysql_fetch_array($getLayers)) {
		$bgLayers[] = array(
			'id' => $getLayer['id'],
			'path' => get_layer_path($getLayer['filename'])
		);
	}
	return $bgLayers;
}
function get_bg_payload($bg) {
	$bgPayload = array(
		'id' => $bg['id'],
		'layers' => get_bg_layers($bg['id'])
	);
	if (isset($bg['name']) && ($bg['name'] !== ''))
		$bgPayload['name'] = $bg['name'];
	return $bgPayload;
}
function print_bg_div($options) {
	if (isset($options['bg']))
		$bgLayers = get_bg_layers($options['bg']);
	elseif (isset($options['layers']))
		$bgLayers = $options['layers'];
	$customAttrs = isset($options['attrs']) ? ' '.$options['attrs'] : '';
	$layerStyles = array();
	foreach ($bgLayers as $bgLayer)
		$layerStyles[] = "url('".$bgLayer['path']."')";
		$layerStyles = array_reverse($layerStyles);
	echo '<div class="bg-preview" style="background-image: '. implode(', ', $layerStyles) .'"'. $customAttrs .'></div>';
}
?>