<?php

require __DIR__ . '/api-google/vendor/autoload.php';
date_default_timezone_set('America/Asuncion');

//AUTENTICACION por Cuenta de Servicio
$pathCredentials = __DIR__ . '/download-from-drive-344514-ac9963e071a3.json';
$downloadPath = __DIR__ . "/downloads";

putenv("GOOGLE_APPLICATION_CREDENTIALS=$pathCredentials");

$client = new Google\Client();
$client->useApplicationDefaultCredentials();
$client->addScope("https://www.googleapis.com/auth/drive");

//para test
$fileId = '1PIBUACgCcMl6mCNXpSwnKNaoWCE6t4Oj';
$folderId = '1v7MA25xW1gwBp8rHIVLuGRCnFTnmH5aV';

$service = new Google\Service\Drive($client);


function downloadOneFile($service, $fileId, $path, $fileExtension){
  $response = $service->files->get($fileId, array('alt' => 'media'));
  $content = $response->getBody()->getContents();

  $file = fopen($path, "w+");

  fwrite($file, $content);
  fclose($file);
}

function listFilesFromFolder($service, $folderId){
  $parameters = array(
    'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
    'q' => "'".$folderId."' in parents"
  );
  
  $list = $service->files->listFiles($parameters);
  
  return $list;
}

function downloadListOfFiles($service, $list){
  echo '';
}