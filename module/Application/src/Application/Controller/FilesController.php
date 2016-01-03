<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Model\Books;
use Application\Model\Book;
use Application\Model\Users;
use Application\Model\Tokens;
use Zend\Session\Container;


class FilesController extends AbstractActionController
{
	protected $logged;
	protected $user;
	
	/**
	 * Controller Constructor
	 *
	 * Make login information available
	 */
	public function __construct()
	{
		$user_session = new Container('user');
		$this->logged = $user_session->logged;
		$this->user = $user_session->user;
	}	
    
    public function imageAction()
    {
        //Start variables
        $sm = $this->getServiceLocator();

        //Load arguments
        $size = $this->params()->fromRoute('arg1', 0);
        $book_id = $this->params()->fromRoute('arg2', 0);

        $attachment_location = $sm->get('config')['uploadFolder'] . $size . "/" . $book_id . ".jpeg";

        if (file_exists($attachment_location)) {
        	header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
        	header("Cache-Control: public"); // needed for i.e.
        	header("Content-Type: image/jpeg");
        	header("Content-Transfer-Encoding: Binary");
        	header("Content-Length:".filesize($attachment_location));
        	header("Content-Disposition: attachment; filename=" . $book_id . ".jpeg");
        	readfile($attachment_location);
        	die();
        } else {
        	die("Error: File not found. :" . $attachment_location);
        }        
    }
    
    public function epubAction()
    {
        //Start variables
    	$sm = $this->getServiceLocator();
        $books = new Books($sm);
        $tokens = new Tokens($sm);
        $users = new Users($sm);

        //Load arguments
        $arg1 = $this->params()->fromRoute('arg1', 0);
        $arg2 = $this->params()->fromRoute('arg2', 0);

        //Set variables according to hash existence
        if ($arg2)
        {
            $hash = $arg1;
            $book_id = $arg2;
        } else {
            $book_id = $arg1;
        }

        //Check hash validity
    	if ($hash)
    	{
            $token = $tokens->checkHash($hash);

            if ($token)
            {
    		    $this->logged = $token['enabled'];
                $this->user = $users->userDetails($token['user_id']);
            }
    	}
    	 
    	//Redirecting if not logged
    	if (!$this->logged)
        {
            die("You must be logged to download");
    	}

        //Set file location
    	$attachment_location = $sm->get('config')['uploadFolder'] . $book_id . ".epub";
            
        //Load book information
    	$filter = array('book_id' => $book_id);
    	$book = new Book($books->listBooks($filter)->current());
    
        //Return file or error
    	if (file_exists($attachment_location)) {
    		header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
    		header("Cache-Control: public"); // needed for i.e.
    		header("Content-Type: application/epub+zip");
    		header("Content-Transfer-Encoding: Binary");
    		header("Content-Length:".filesize($attachment_location));
    		header("Content-Disposition: attachment; filename=" . $book->title . " - " . $book->author . ".epub");
    		readfile($attachment_location);
    		die();
    	} else {
    		die("Error: File not found. :" . $attachment_location);
    	}
    }
}