<?php
namespace Application\Model;

use Zend\Filter\Decompress;
use ZipArchive;

class EPUB
{
    protected $path;
    protected $id;
    protected $uploadPath;
    
    public function __construct($epub_file)
    {
    	$this->uploadPath = getenv('UP_FOLDER');
        $this->id = uniqid();
        
        $filter     = new Decompress(array(
        		'adapter' => 'Zip',
        		'options' => array(
        				'target' => $this->uploadPath . 'temp/' . $this->id,
        				'archive' => $epub_file
        		)
        ));
        deleteDir($this->uploadPath . 'temp/' . $this->id);
        mkdir($this->uploadPath . 'temp/' . $this->id);
        $filter->filter($epub_file);
    }
    
    public function __destruct() {
        deleteDir($this->uploadPath . 'temp/' . $this->id);
    }
    
    public function getBook($book)
    {
        $xmlfile = findFile($this->uploadPath . 'temp/' . $this->id, "*.opf");

        $values = simplexml_load_file($xmlfile);

        $book->title = current($values->metadata->children('dc', true)->title);
        $book->author = current($values->metadata->children('dc', true)->creator);
        $book->language = substr(current($values->metadata->children('dc', true)->language),0,2);
    }
    
    public function saveCover($book_id, $imagine)
    {
        $xmlfile = findFile($this->uploadPath . 'temp/' . $this->id, "*.opf");
//    	$xmlfile = findFile($this->uploadPath . 'temp/' . $this->id, 'content.opf');
//        if (!$xmlfile)
//        	$xmlfile = findFile($this->uploadPath . 'temp/' . $this->id, 'epb.opf');
        
        $values = simplexml_load_file($xmlfile);
        $values->registerXPathNamespace('ns', current($values->getNamespaces(true)));
        
        $cover = $values->xpath("//ns:metadata/ns:meta[@name='cover']");
        $cover = $cover[0];
        $cover = $cover['content'];
        $cover = current($cover);
//      $cover = current($values->xpath("//ns:metadata/ns:meta[@name='cover']")[0]['content']);
        
        if (!preg_match("/\w+\.\w{3,4}?/", $cover))
        {
        	$cover = $values->xpath("//ns:manifest/ns:item[@id='" . $cover . "']");
        	$cover = $cover[0];
        	$cover = $cover['href'];
        	$cover = current($cover);
        }
//      $cover = current($values->xpath("//ns:manifest/ns:item[@id='" . $cover . "']")[0]['href']);
        $cover = end(explode("/",$cover));
        $cover = findFile($this->uploadPath . 'temp/' . $this->id, $cover);
        
        if (file_exists($cover))
        {
        	$mode    = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;
        	 
        	$size    = new \Imagine\Image\Box(150, 150);
        	$imagine->open($cover)
        	->thumbnail($size, $mode)
        	->save($this->uploadPath . "thumb/" . $book_id . ".jpeg");
        
        	$size    = new \Imagine\Image\Box(550, 550);
        	$imagine->open($cover)
        	->thumbnail($size, $mode)
        	->save($this->uploadPath . "reg/" . $book_id . ".jpeg");
        }
    }
    
    public function changeCover($file_name)
    {
        $xmlfile = findFile($this->uploadPath . 'temp/' . $this->id, "*.opf");
//    	$xmlfile = findFile($this->uploadPath . 'temp/' . $this->id, 'content.opf');
//        if (!$xmlfile)
//        	$xmlfile = findFile($this->uploadPath . 'temp/' . $this->id, 'epb.opf');
        
        $values = simplexml_load_file($xmlfile);
        $values->registerXPathNamespace('ns', current($values->getNamespaces(true)));
        
        $cover = $values->xpath("//ns:metadata/ns:meta[@name='cover']");
        $cover = $cover[0];
        $cover = $cover['content'];
        $cover = current($cover);
//      $cover = current($values->xpath("//ns:metadata/ns:meta[@name='cover']")[0]['content']);

        if (!preg_match("/\w+\.\w{3,4}?/", $cover))
        {
        	$cover = $values->xpath("//ns:manifest/ns:item[@id='" . $cover . "']");
        	$cover = $cover[0];
        	$cover = $cover['href'];
        	$cover = current($cover);
        }
//      $cover = current($values->xpath("//ns:manifest/ns:item[@id='" . $cover . "']")[0]['href']);
        
        $cover = end(explode("/",$cover));
        $cover = findFile($this->uploadPath . 'temp/' . $this->id, $cover);
        
        unlink($cover);
        move_uploaded_file($file_name, $cover);
    }
    
    public function saveBook($book_id)
    {
        $zip = new ZipArchive();
        $res = $zip->open($this->uploadPath . $book_id . '.epub', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = listFiles($this->uploadPath . 'temp/' . $this->id);
        foreach ($files as $file){
            $zip->addFile($file, substr($file, strlen($this->uploadPath . 'temp/' . $this->id . '/')));
        }
        $zip->close();
    }
    
    public function delBook($book_id)
    {
    	if (file_exists($this->uploadPath . $book_id . ".epub"))
    		unlink($this->uploadPath . $book_id . ".epub");
    	 
    	if (file_exists($this->uploadPath . "reg/" . $book_id . ".jpeg"))
    		unlink($this->uploadPath . "reg/" . $book_id . ".jpeg");
    	 
    	if (file_exists($this->uploadPath . "thumb/" . $book_id . ".jpeg"))
    		unlink($this->uploadPath . "thumb/" . $book_id . ".jpeg");
    }
}


function deleteDir($dirPath) {
	if (is_dir($dirPath))
	{
		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				deleteDir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dirPath);
	}
}

function findFile($dirPath, $file_name) {
	$result = '';
	if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
		$dirPath .= '/';
	}
	$files = glob($dirPath . '*', GLOB_MARK);
	foreach ($files as $file) {
		if (is_dir($file)) {
			$temp = findFile($file, $file_name);
			if ($temp)
				$result = $temp;
		} else {
			$fileName = end(explode('/', $file));
			$pattern = str_replace(".", "\.", str_replace("*", "[a-zA-Z\_\-0-9]*", "/$file_name/"));
			
//			if ($regex)
//				preg_match($file_name, $fileName, $matches);
//			if (($matches) || ($file_name == $fileName))
//				$result = $file;
			if (preg_match($pattern, $fileName))
				$result = $file;
		}
	}

	return $result;;
}

function listFiles($dirPath) {
	$result = array();
	if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
		$dirPath .= '/';
	}
	$files = glob($dirPath . '*', GLOB_MARK);
	foreach ($files as $file) {
		if (is_dir($file)) {
			$temp = listFiles($file);
			$result = array_merge($result, $temp);
		} else {
		    array_push($result, $file);
		}
	}

	return $result;;
}
