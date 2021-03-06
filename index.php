<?php

require __DIR__ . '/api-google/vendor/autoload.php';
date_default_timezone_set('America/Asuncion');

//AUTENTICACION por Cuenta de Servicio
$pathCredentials = __DIR__ . '/download-from-drive-344514-ac9963e071a3.json';
$downloadPath = __DIR__.'/Imagenes';
$csvPath = __DIR__.'/links.csv';

putenv("GOOGLE_APPLICATION_CREDENTIALS=$pathCredentials");

$client = new Google\Client();
$client->useApplicationDefaultCredentials();
$client->addScope("https://www.googleapis.com/auth/drive");

$service = new Google\Service\Drive($client);

// displayEchoWhileExecuting();

$links = getLinksFromCsv($csvPath);

if ($links === null){
  die("Error al leer csv");
}
$remaining = count($links);
echo "Total: $remaining\n";

foreach($links as $link){
  
  if(strpos($link, 'folders/')===false){
    echo "\n$remaining- Link: $link\n";
    $fileId = getFileId($link);
    $result = $service->files->get($fileId, array('fields' => 'fileExtension, name'));
    $name = $result['name'];
    $fileExtension = $result['fileExtension'];
    if(imageType($fileExtension)){
      $path = "$downloadPath/$fileId";
      if(!is_dir($path)){
        mkdir ($path);
        downloadOneFile($service, $fileId, $path, $fileExtension, time());
      }else{
        echo "\nDuplicado";
      }
    }
  }else{
    echo "\n$remaining- Link: $link\n";
    $folderId = getFolderId($link);
    $list = listFilesFromFolder($service, $folderId);
    if(isset($list)){
      downloadListOfFiles($service, $list, $downloadPath, $folderId); 
    }
  }
  $remaining--;
}

function downloadOneFile($service, $fileId, $path, $fileExtension, $name){
  try{
    $response = $service->files->get($fileId, array('alt' => 'media'));
    $content = $response->getBody()->getContents();
    
    $file = fopen("$path/$name.$fileExtension", "w+");
  
    fwrite($file, $content);
    fclose($file);
    echo "$fileId \n";
  }catch(Exception $e){
    $msg = "Ocurrió un error: '$fileId'\n"; 
    echo $msg;
    $e = $msg.$e;
    logError($fileId.'\n'.$e);
  }
  
}

function listFilesFromFolder($service, $folderId){
  $parameters = array(
    'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
    'q' => "'".$folderId."' in parents"
  );
  
  try{
    $list = $service->files->listFiles($parameters);
    return $list;
  }catch(Exception $e){
    $msg = "Ocurrió un error: '$folderId'\n"; 
    echo $msg;
    $e = $msg.$e;
    logError($e);
  }
  
}

function downloadListOfFiles($service, $list, $downloadPath, $folderId){
  
  $length = count($list);
  if($length <= 0) {
    echo "VACIO\n"; 
    return;
  };
  
  $name = time();
  $path = "$downloadPath/$folderId";
  if(!is_dir($path)){
    mkdir ($path);
    for($i = 0; $i < $length; $i++){
      if(imageType($list[$i]['fileExtension'])){
        downloadOneFile($service, $list[$i]['id'], $path, $list[$i]['fileExtension'], $name+$i);
      }
    }
  }else{
    echo "Duplicado\n";
  }
    
}

function getFolderId($folderLink){
  $needle = 'folders/';
  $folderId = substr($folderLink, stripos($folderLink, $needle) + strlen($needle));
  if(strpos($folderId,'?')!==false){
    $folderId =  substr($folderId, 0, strpos($folderId, '?'));
  }
  return $folderId;
}

function displayEchoWhileExecuting(){
  ob_implicit_flush(true);
  ob_end_flush();
}

function getLinksFromCsv($csvPath){
  
  $csv = fopen($csvPath, "r");
  $links = fgetcsv($csv);
  fclose($csv);
  return $links;
    
}

function getFolderName($service, $folderId){
  $result = $service->files->get($folderId);
  $name = $result->getName();
  return $name;
}

function writeOnFolderNames($folderName, $folderId){
  $file = __DIR__.'/FolderNames.txt';
  file_put_contents($file, "$folderName ; $folderId\n", FILE_APPEND);
}

function getFileId($fileLink){
  $needle = 'file/d/';
  $fileId = substr($fileLink, stripos($fileLink, $needle) + strlen($needle));
  $fileId =  substr($fileId, 0, stripos($fileId, '/view'));
  return $fileId;
}

function imageType($fileExtension){
  $fileExtension = strtolower($fileExtension);
  $extensions = array('jpg','jpeg','png','heic');
  if(in_array($fileExtension, $extensions)){
    return true;
  }else{
    return false;
  }
  
}

function logError($e){
  $file = __DIR__.'/log.log';
  $e = "********************************************\n$e\n********************************************";
  file_put_contents($file, $e, FILE_APPEND);
}