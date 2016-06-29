<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;

use Application\Model\Books;
use Application\Model\Book;
use Application\Model\EPUB;
use Application;
use Zend\Filter\File\UpperCase;

class IndexController extends AbstractActionController
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
    
    /** 
     * Index Action
     * 
     * Show index (login) if not logged, redirect to mybook otherwise
     */
    public function indexAction()
    {
        if ($this->logged)
            return $this->redirect()->toRoute('application/default', array(
                'controller' => 'index',
                'action'     => 'allbooks'
                ));
                    
        $view = new ViewModel(array(
            'user' => $this->user,
        ));
        $view->setTerminal(true);
        return $view;
    }
    
    /** 
     * BookEdit Action
     * 
     * Return form to edit book details
     */
    public function bookeditAction()
    {
        //Redirecting if not logged
        if (!$this->logged)
            return $this->redirect()->toRoute('application/default', array(
                'controller' => 'index',
                'action'     => 'index'
                ));
        
        //Load book information
        $sm = $this->getServiceLocator();
    	$books = new Books($sm);
    
    	$book = new Book();
    	$id = (int) $this->params()->fromRoute('id', 0);
    	$filter = array('book_id' => $id, 'user_id' => $this->user->user_id);
    	$book = $books->listBooks($filter)->current();

    	//Redirect if not the owner of the book
		if (!$book->book_id)
    		return $this->redirect()->toRoute('application/default', array(
    				'controller' => 'index',
    				'action'     => 'index'
    		));
    	
    	//Show form
    	return new ViewModel(array(
    			'genres'      => $books->listParams(null, 'genre', false),
    			'authors'     => $books->listParams(null, 'author', false),
    			'series'      => $books->listParams(null, 'serie', false, true),
    			'languages'   => $books->listParams(null, 'language', true, true),
    			'book'        => $book,
    			'book_genres' => $books->listParams($filter, 'genre'),
    	));
    }
    
    /**
     * BookView Action
     *
     * Show book details
     */
    public function bookviewAction()
    {
    	//Redirecting if not logged
    	if (!$this->logged)
    		return $this->redirect()->toRoute('application/default', array(
    				'controller' => 'index',
    				'action'     => 'index'
    		));
    
        //Initiate variables
        $sm = $this->getServiceLocator();
    	$books = new Books($sm);
    	$id = (int) $this->params()->fromRoute('id', 0);
    	
        //Load book information
    	$filter = array('book_id' => $id);
    	$book = new Book($books->listBooks($filter)->current());

    	//Redirect if not the owner of the book
    	if (!$book->book_id)
    		throw new \Exception('This book does not exist on our database');
    	 
    	//Show form
    	return new ViewModel(array(
    			'book'        => $book,
    			'book_genres' => $books->listParams($filter, 'genre'),
    	));
    }
    
    /**
     * Book Submit
     * 
     * Expect details about the book to save on the database
     * 
     * @return Ambigous <\Zend\Http\Response, \Zend\Stdlib\ResponseInterface>|\Zend\View\Model\ViewModel
     */
    public function booksubmitAction()
    {
        //Redirecting if not logged
        if (!$this->logged)
            return $this->redirect()->toRoute('application/default', array(
                'controller' => 'index',
                'action'     => 'index'
                ));
    	
        //Initiate variables
        $sm = $this->getServiceLocator();
    	$books = new Books($sm);
    	$request = $this->getRequest();
    	$fields = $request->getPost();
    	
        //Load book information
    	$filter = array('book_id' => $fields['book_id'], 'user_id' => $this->user->user_id);
    	$book = new Book($books->listBooks($filter)->current());

  		//Redirect if not the owner of the book
		if (!$book->book_id)
    		return $this->redirect()->toRoute('application/default', array(
    				'controller' => 'index',
    				'action'     => 'index'
    		));
    	
    	//Update values
    	if ($request->isPost()) {
    		$book = $fields;
    	
    		$book->author = ($book->author == "-1" ? $fields->other_author : $book->author);
    		$book->serie = ($book->serie == "-1" ? $fields->other_serie : $book->serie);
    		$book->language = ($book->language == "-1" ? $fields->other_language : $book->language);
    		$book->date = date("Y-m-d");
    		$book->status = 'ok';
    	
    		$genres = $fields->genre;
    	
    		$file_sent = (!empty($_FILES['file']['name']));
    		$file_ok = (($_FILES["file"]["error"] > 0) ? false : true);
    		$file_ext = end(explode(".", $_FILES["file"]["name"]));
    		$file_ok = (($file_ext == "jpg") || ($file_ext == "jpeg") ? $file_ok : false);
    	}
    	
    	if (($file_sent) && (!$file_ok))
    	{
    		$message = "Erro: Problems with the file";
    	} else {
    		$message = 'The book "' . $book->title . '" was successfully saved';
    	
    		//Save updated values
    		$book = $books->editBook($book, $genres);
    	
    		//Change Picture
    		if ($file_sent)
    		{
    			$epub = new EPUB($this->getServiceLocator()->get('config')['uploadFolder'] . $book->book_id . ".epub", $this->getServiceLocator()->get('config')['uploadFolder']);
    			$epub->changeCover($_FILES["file"]["tmp_name"]);
    			$imagine = $this->getServiceLocator()->get('my_image_service');
    			$epub->saveCover($book->book_id, $imagine);
    			$epub->saveBook($book->book_id);
    		}
    	}
    	
    	return new ViewModel(array(
    			'message' => $message
    	));
    }
    
    
    public function bookdeleteAction()
    {
    	//Redirecting if not logged
    	if (!$this->logged)
    		return $this->redirect()->toRoute('application/default', array(
    				'controller' => 'index',
    				'action'     => 'index'
    		));
    	 
    	//Initiate variables
    	$sm = $this->getServiceLocator();
    	$books = new Books($sm);
        $book_id = (int) $this->params()->fromRoute('id', 0);
    	    	 
    	//Load book information
    	$filter = array('book_id' => $book_id, 'user_id' => $this->user->user_id);
    	$book = new Book($books->listBooks($filter)->current());
    	
    	//Redirect if not the owner of the book
    	if (!$book->book_id)
    		return $this->redirect()->toRoute('application/default', array(
    				'controller' => 'index',
    				'action'     => 'index'
    		));
    	
    	//Delete on database
    	$message = $books->delBook($book_id);
    	
    	//Delete files
    	EPUB::delBook($book_id);
    	
    	//Return messsage
    	return new ViewModel(array(
    			'message' => $message
    	));
    } 
    
    
    /** 
     * Authors Action
     * 
     * Return list of Authors based on key words sent as query (GET)
     * NOT A STAND ALONE PAGE
     */
    public function authorsAction()
    {
    	$key = $this->getRequest()->getQuery()->key;

    	$sm = $this->getServiceLocator();
		$books = new Books($sm);
    	
		$view = new ViewModel(array(
            'authors' => $books->listAuthors($key),
        ));
		$view->setTerminal(true);
		return $view;
    }
    
    /** 
     * BookAdd Action
     * 
     * Save new EPUBS sent as POST and redirect to BookEdit
     * @throws \Exception
     * @return Ambigous <\Zend\Http\Response, \Zend\Stdlib\ResponseInterface>
     */
    public function bookaddAction()
    {
        //Redirecting if not logged
        if (!$this->logged)
            return $this->redirect()->toRoute('application/default', array(
                'controller' => 'index',
                'action'     => 'index'
                ));
        
        $book = new Book();
    
        //Verify validity of the file
    	$file_ok = (!empty($_FILES['fileInput']['name']));
    	$file_ok = (($_FILES["fileInput"]["error"] > 0) ? false : $file_ok);
    	$file_ok = ((end(explode(".", $_FILES["fileInput"]["name"])) != "epub") ? false : $file_ok);
    	 
    	if ($file_ok)
    	{
    		//Get EPUB information
    		$epub = new EPUB($_FILES["fileInput"]["tmp_name"], $this->getServiceLocator()->get('config')['uploadFolder']);
    		$book = new Book();
    		$epub->getBook($book);
    
    		//Complement book information
    		$sm = $this->getServiceLocator();
    		$books = new Books($sm);
    		$book->user_id = $this->user->user_id;
    		$book->date = date("Y-m-d");
    		$book->status = 'tmp';
    		$book = $books->addBook($book);
    
    		//Save book cover
    		$imagine = $this->getServiceLocator()->get('my_image_service');
    		$epub->saveCover($book->book_id, $imagine);
    
    		//Move EPUB to proper location 
    		if (file_exists($this->getServiceLocator()->get('config')['uploadFolder'] . $book->book_id . ".epub"))
    			unlink($this->getServiceLocator()->get('config')['uploadFolder'] . $book->book_id . ".epub");
    		move_uploaded_file($_FILES["fileInput"]["tmp_name"], $this->getServiceLocator()->get('config')['uploadFolder'] . $book->book_id . ".epub");
    	
    	    //Redirect to BookEdit action
    	    return $this->redirect()->toRoute('application/default', array(
    			'controller' => 'index',
    			'action' => 'bookedit',
    	    	'id' => $book->book_id
    	    ));
    	} else {
    		//Throw excpetion if file have a problem
    	    throw new \Exception("The uploaded file must be a valid EPUB");
    	}
    }    
    
    /**
     * MyBooks Action
     * 
     * Show list of my books and handle Filters and Navigation
     * @return Ambigous <\Zend\Http\Response, \Zend\Stdlib\ResponseInterface>|\Zend\View\Model\ViewModel
     */
    public function mybooksAction()
    {
        //Redirect if not logged
        if (!$this->logged)
            return $this->redirect()->toRoute('application/default', array(
                'controller' => 'index',
                'action'     => 'index'
                ));
        
        //Get query and parameters
        $query = $this->getRequest()->getQuery()->getArrayCopy();
        $params = $this->params()->fromRoute();
        
        //Update filters
        if (array_key_exists('id', $params))
            $filters = unserialize(base64_decode(urldecode($params['id'])));
        else
            $filters = array();
        $filters = Application\Model\Util::updateFilter($filters, $query);
        $userFilters = Application\Model\Util::updateParam($filters, array('user_id' => $this->user->user_id));
        $url = $this->url()->fromRoute('application/default', array('controller' => 'index', 'action' => 'mybooks', 'id' => (($filters) ? urldecode(base64_encode(serialize($filters))) : null)));
        
        //Setup pages
        $pageSize = 10;
    	$page = (array_key_exists('page', $query) ? $query['page'] : 1);
    	$page = (is_numeric($page) ? $page : 1);
    	$page = ($page <= 0 ? 1 : $page);
    	 
        //Apply filters on database
        $sm = $this->getServiceLocator();
        $books = new Books($sm);
        $bookList = $books->listBooks($userFilters);
        $size = count($bookList);
                
        //List available filters
        $availParams = array();
        if (!array_key_exists('author' , $filters))
            $availParams = array_merge($availParams, array('Author'   => $books->listParams($userFilters, 'author')));
        if (!array_key_exists('serie'   , $filters))
            $availParams = array_merge($availParams, array('Serie'    => $books->listParams($userFilters, 'serie')));
        if (!array_key_exists('language', $filters))
            $availParams = array_merge($availParams, array('Language' => $books->listParams($userFilters, 'language')));
        $availParams = array_merge($availParams, array('Genre' => $books->listParams($userFilters, 'genre')));
        
        
        //Filter non-useful filter params
        foreach ($availParams as $key => $value):
            for ($i = count($availParams[$key]) - 1; $i >= 0; $i--):
                if (($availParams[$key][$i]['count'] == $size) || ($availParams[$key][$i][strtolower($key)] == ''))
                    unset($availParams[$key][$i]);
            endfor;
            if (count($availParams[$key]) == 0)
                unset($availParams[$key]);
        endforeach;
        
        //Filter results for the page
        $pageMax = ceil($size / $pageSize);
        $page = ($page > $pageMax ? $pageMax : $page);
        $pageBookList = array_slice($bookList->toArray(), (($page - 1) * $pageSize), $pageSize);
        
        //Configure pagination
        $pages = array(array('class' => ($page == 1 ? 'disabled' : ''), 'name' => '&laquo;', 'href' => Application\Model\Util::addQuery($url, array('page' => 1))));
        for ($i = max(1, min(ceil($size / $pageSize) - 6, $page - 3)); $i <= min($pageMax, max(1, $page - 3) + 6); $i++)
            array_push($pages, array('class' => ($page == $i ? 'active' : ''), 'name' => $i, 'href' => Application\Model\Util::addQuery($url, array('page' => $i))));
        array_push($pages, array('class' => ($page == $pageMax ? 'disabled' : ''), 'name' => '&raquo;', 'href' => Application\Model\Util::addQuery($url, array('page' => $pageMax))));
                
        //Return results
        return new ViewModel(array(
            'nav'      => 1,
            'user'     => $this->user,
            'url'      => $url,
            'books'    => $pageBookList,
            'avParams' => $availParams,
            'filters'  => $filters,
            'pages'    => $pages,
            'size'     => $size,
        ));
    }   
    
    
    /**
     * AllBooks Action
     *
     * Show list of all books and handle Filters and Navigation
     * @return Ambigous <\Zend\Http\Response, \Zend\Stdlib\ResponseInterface>|\Zend\View\Model\ViewModel
     */
    public function allbooksAction()
    {
    	//Redirect if not logged
    	if (!$this->logged)
    		return $this->redirect()->toRoute('application/default', array(
    				'controller' => 'index',
    				'action'     => 'index'
    		));
    
    	//Get query and parameters
    	$query = $this->getRequest()->getQuery()->getArrayCopy();
    	$params = $this->params()->fromRoute();
    
    	//Update filters
    	if (array_key_exists('id', $params))
    		$filters = unserialize(base64_decode(urldecode($params['id'])));
    	else
    		$filters = array();
    	$filters = Application\Model\Util::updateFilter($filters, $query);
    	$userFilters = Application\Model\Util::updateParam($filters, array('status' => 'ok'));
    	$url = $this->url()->fromRoute('application/default', array('controller' => 'index', 'action' => 'allbooks', 'id' => (($filters) ? urldecode(base64_encode(serialize($filters))) : null)));
    
    	//Setup pages
    	$pageSize = 10;
    	$page = (array_key_exists('page', $query) ? $query['page'] : 1);
    	$page = (is_numeric($page) ? $page : 1);
    	$page = ($page <= 0 ? 1 : $page);
    
    	//Apply filters on database
    	$sm = $this->getServiceLocator();
    	$books = new Books($sm);
    	$bookList = $books->listBooks($userFilters);
    	$size = count($bookList);
    
    	//List available filters
    	$availParams = array();
    	if (!array_key_exists('author' , $filters))
    		$availParams = array_merge($availParams, array('Author'   => $books->listParams($userFilters, 'author')));
    	if (!array_key_exists('serie'   , $filters))
    		$availParams = array_merge($availParams, array('Serie'    => $books->listParams($userFilters, 'serie')));
    	if (!array_key_exists('language', $filters))
    		$availParams = array_merge($availParams, array('Language' => $books->listParams($userFilters, 'language')));
    	$availParams = array_merge($availParams, array('Genre' => $books->listParams($userFilters, 'genre')));
    
    
    	//Filter non-useful filter params
    	foreach ($availParams as $key => $value):
    	for ($i = count($availParams[$key]) - 1; $i >= 0; $i--):
    	if (($availParams[$key][$i]['count'] == $size) || ($availParams[$key][$i][strtolower($key)] == ''))
    		unset($availParams[$key][$i]);
    	endfor;
    	if (count($availParams[$key]) == 0)
    		unset($availParams[$key]);
    	endforeach;
    
    	//Filter results for the page
    	$pageMax = ceil($size / $pageSize);
    	$page = ($page > $pageMax ? $pageMax : $page);
    	$pageBookList = array_slice($bookList->toArray(), (($page - 1) * $pageSize), $pageSize);
    
    	//Configure pagination
    	$pages = array(array('class' => ($page == 1 ? 'disabled' : ''), 'name' => '&laquo;', 'href' => Application\Model\Util::addQuery($url, array('page' => 1))));
    	for ($i = max(1, min(ceil($size / $pageSize) - 6, $page - 3)); $i <= min($pageMax, max(1, $page - 3) + 6); $i++)
    		array_push($pages, array('class' => ($page == $i ? 'active' : ''), 'name' => $i, 'href' => Application\Model\Util::addQuery($url, array('page' => $i))));
    		array_push($pages, array('class' => ($page == $pageMax ? 'disabled' : ''), 'name' => '&raquo;', 'href' => Application\Model\Util::addQuery($url, array('page' => $pageMax))));
    
    	//Return results
    	return new ViewModel(array(
    	'nav'      => 2,
    	'user'     => $this->user,
    	'url'      => $url,
    	'books'    => $pageBookList,
    	'avParams' => $availParams,
    			'filters'  => $filters,
    			'pages'    => $pages,
    			'size'     => $size,
    	));
    }
    
}