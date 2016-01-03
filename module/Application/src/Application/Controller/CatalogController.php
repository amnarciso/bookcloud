<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Model\Books;
use Application\Model\Book;
use Application\Model\Tokens;
use Application\Model\Users;
use Zend\Session\Container;
use SimpleXMLElement;
use Application;


class CatalogController extends AbstractActionController
{
	protected $logged;
	protected $user;
	public $titles = array('LIST' => 'Show books', 'author' => 'Authors', 'serie' => 'Series', 'genre' => 'Genres', 'language' => 'Languages');
	
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
	
	public function indexAction()
	{
		//Initialize variables
        $sm = $this->getServiceLocator();
        $books = new Books($sm);
        $tokens = new Tokens($sm);
        $users = new Users($sm);
        $renderer = $this->serviceLocator->get('Zend\View\Renderer\RendererInterface');
				
        //Load hash
		$hash = $this->params()->fromRoute('hash', 0);

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
    		die("Invalid token");
    	}


		//Setup path
		$page = $this->params()->fromRoute('page', 0);
		if (substr($this->params()->fromRoute('index', 0), 0, 6) == 'SEARCH')
			$index = 'opds:key:' . substr($this->params()->fromRoute('index', 0), 7);
		else 		
			$index = base64_decode(urldecode($this->params()->fromRoute('index', 0)));
		$path = explode(':', $index);
		$filter = array('status' => 'ok');
		
		//Identify type of page
		$type = 'LIST';
		if ($path[count($path)-1] != 'LIST')
			if (count($path) % 2 == 1)
				$type = 'FILTERS';
			else 
				$type = 'PARAMS';
			
		//Prepare XML
		$xmlStr = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:opds="http://opds-spec.org/2010/catalog"></feed>';
		$xml = new SimpleXMLElement($xmlStr);
		$xml->addChild('title', (array_key_exists($path[count($path)-1], $this->titles) ?  $this->titles[$path[count($path)-1]] : $renderer->escapehtml($path[count($path)-1])));
		$xml->addChild('id', $renderer->escapehtml($index));
		$xml->addChild('content')->addAttribute('type', 'text');
		
		$node = $xml->addChild('author');
		$node->addChild('name', 'bookcloud');
		$node->addChild('uri');
		$node->addChild('email', 'bookcloud@narciso.ws');
		
		//Add initial links (search, Main page, back)
		if (count($path) == 1)
		{
			$link = $xml->addChild('link');
			$link->addAttribute('rel', 'search');
			$link->addAttribute('title', 'Search Catalog');
			$link->addAttribute('type', 'application/atom+xml;type=feed;profile=opds-catalog');
			$link->addAttribute('href', $renderer->url('catalog', array('hash' => $hash, 'index' => 'SEARCH:')).'{searchTerms}');
		} else {
			$link = $xml->addChild('link');
			$link->addAttribute('rel', 'start');
			$link->addAttribute('title', 'Main Catalog');
			$link->addAttribute('type', 'application/atom+xml;type=feed;profile=opds-catalog');
			$link->addAttribute('href', $renderer->url('catalog', array('hash' => $hash)));

			$link = $xml->addChild('link');
			$link->addAttribute('rel', 'breadcrumb');
			$link->addAttribute('title', $renderer->escapehtml($path[count($path)-2]));
			$link->addAttribute('type', 'application/atom+xml;type=feed;profile=opds-catalog');
			$link->addAttribute('href', $renderer->url('catalog', array('hash' => $hash, 'index' => base64_encode(substr($index, 0, strrpos($index, ':'))))));
		}
		
		//Show list of available filters
		if ($type == 'FILTERS')
		{
			//Setup filter
			for ($i = 1; $i < count($path); $i += 2)
				$filter = Application\Model\Util::updateFilter($filter, array('a' . $path[$i] => $path[$i+1]));
			
			//List filters
			$filter_list = array(
				array('title' => 'Authors', 'name' => 'author', 'img' => 'data:image/png;base64,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'),
				array('title' => 'Series', 'name' => 'serie', 'img' => 'data:image/png;base64,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'),
				array('title' => 'Languages', 'name' => 'language', 'img' => 'data:image/png;base64,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'),
				array('title' => 'Genres', 'name' => 'genre', 'img' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAKVUlEQVR4nL1Xe5BVxZn/fV/3Oeeee+5c7p25M8O8X4zIKI9RQI0EV3yBlYiLD2KtEeMjGkOxWbPWWupSa2VJKtkkVkxBHq5m0Y1xNZBVWUhmERMVR0FQcIDhMcgMwzDMXGfu+95zzznd+8dIilQltbWbXb9/uvrrx+/rX3f/+mvgU7S3Hp9Rvenr7bFzffxpgfd+47zpDZXi+2/3T9z3oy+3hc/6xacBvmtdZ0eiQvyyqbZyadbVDfduON6rNE4BnwIDux5vvqqu0ni2YXrlAg1C+3SnpiUhZp9tl/9fwG/9Q2u3beCOrs7paxiCoAFigVlNkXhXc2TRnVdPe+6x54fKcirK1qahpBe6+Yenjv65wNseqm+vjlurWhritzl2qI00E5igiUFEsCybuxpD52/bMxEDMCYBwAPuuuTC+NceuaG4/orZie9dt+7I5LmT7v5WF7uB4kWP9fvn+l9b22lL1rWWKeu0V5wfseX1nW2J65gMQANEjEBrfTJZLJ3JBMXpUWE318ftpRdXdWx6N90KYIy2P9YhoyG18cLW+G2ur7w3D6Ze/mAg9+21m5J7zgK9/62W+8O2XGIImXN9lQOIiBDVQNyxQ3Umoy42za4jEkREAAgggVzJ9R/beHRfKl0sLpsfb1rYVVnd3tYYLmazQXTlWysfvaX1ZVko+lXxilATEcMy2Ljuouk3X9kdrKiNGRsTsdBLSnPv5l2pibuvrb2+JmrYpmkSNEEDgCaACUQCxEx+4OvhZKkUdSx5aiJb2vDq4OFLz49UGKF45NRofmw0w+aMgMMVsZhoiqJLCv0bqYCYYRoxIgFFDK0BAyavWjrjS8l06YajI7ndpoFDjzwzsLnMZryt2mxorTGi9QkrEgsLkXe1SmZ89+BQaWIw6U2UisUgETXpgWVVC79wRU3T3/zz4H/0nXR7mXBs1WdSKz97Udt9GoSOOrOJtHKkHZKVjklxYgEJhhYAiMEkUBOPVNVURpdePqdpqTAZhWyu/N6RyaGB04XJ/SfKp13PU4YkIxYxzKUL66rmdlbNzHjSffSHO7fNm9fhrHx87zupvN/z8I21iVXXNDzR2jJ9tvYVWAjM6Yg2HjyZq5BRx4g4IcMBCxAzhDAAArTS0CBoZrAGAg+wwzFzcXd8xuKLGUQ8td9EWn+iZ5oY+/YNp+urzBZoi+5Z2nD+Q9M6Hp7bXjXTNEMhYGrLNBgtteH4k1uHbJkt+BVO2LKPjaQKb3yYHO/7KJ0kJjW3Ixa7fHZdbWdzTVRpgmBAaQYTQymGYK1834UIShQoD0K5gPJw6MiZiYs7rSpMDuDa841GJaiRVR6BpyGMMAIyIYiQiIcrAETkvhNZe/GCegkBNa+rNqoJ+Y+zfiFRGZZuuQgtAghtgoghQIBBkF4SlBkm5FOAV4BUZUApkCmx7zBn7lxMLZQ+AwBgIoAlhLABGYYI1wJOPUKhkMmALcs+WeSxiDpR/uqG99/+7aHCZgCjFdZ44sn7Z6yePctZoLwiRGkMmjJ4Z+9oZsu+4GQyp9Kzm9m5tRszq20dYgFkVChI5kuZOXVcqQAwMxQA1j4CLwfh5xAUxyAmP4QsOAKAJVNFRSwYhmnQDZdW1V7fHV0QEArZgp+pi1s+ZQYgsicBdxLaDOmtB2horECpC5pD0coqg5/sSe+8/xr7kuaoG/n1+8GZeU0cdyxtaOkgmWOv4OqgKcEhUcoDRBBEgNbQekqrpCUpALFOxGxzzU3z5hPxfA1AFE9DpQ5DjaUBAEIAoxkq7zjo/u7kWLD5gxMiU1fBK59ePe2BTA5eQSn10m7/8I/udi7Zc1pN3vWTfM/+4WAPgLFqB/XfvSN661/OUxeEddlgBso+fAC+DBsoB5oCSUISGNAeKH0UQfo4WPkQhN+/mQ2RkvWrB8zbj31sd793XD/9nVfz61XJu6ejWsf2DJqTjunKlMf6q89kd+wfDp4BcOCbt1iTWhGv/lnmt31L7L/9zm3WCl12kSnB1UCJr5pTkS152iUSCLwscOoNqIkjYPgAAwERAg2AGUFAqHXcaZc1FT7ztWvU08eeiB6srSjHqFzCrz8IhpdfajW+8Ga5/92Pgpfuv9J4C8DI4vPYv7yTqzatiQTjWfXT53v1B4Ew9aERPQGgIPMlL1co66Lj5xxkDoC8LISYknNoghB6igIChKBPRIqhfQ82+TYCwDccved4fvyuqyvav/tK+l0A/csvMtTNC+zrGmqMO0KGnqF8v/bxmwzpkyVhMfpOpEYB5GUu5yYnk6cnrPFjsZ5j1plb58oG5fsQmgHWCLQAoCFYIFDnloRACwhD4HvbvP7lFxuNDdXSGTgdDN17hXVhd7v9i+po0AW/qAmYug1EULoEFYR171HvYGuC05zKlSdSyRMTsbCS2/cXB57fa+1l0wJYA8QQDAg+GwRNiZlkwAxBCI2PUrLwTr87+oXFdju8AEsuMOatvcX5fk3EnUW+p4kJYAHmKVpZMHoP+5MFH4NLumRG3LTAKEnmxZ1VpTllbdHtPy48eDRppFrqQm2xMIcMU7JmgKQEpMRwxij1njBTjQkjbAiNda+qvf/ypvvcnEazpr3BrFs21+iotsuRQDGYgUAJMDMCTWAG8soMHvm3fO+pieCFS2bIYXn7jzPes/dW7NYs/mpZt6yfFkI1af8bK55wd89uMf5iTgPmNlXJqNbAwLif3vtRcPKLV4QvsIWfOD5hFn66feI1Dex88LnM/sGk+seHbrSu0qVPzhDEJ0wSQFOMvj1AY6/3ld9rr+ETP/iNGwgAsIU/cv18Z7WtC0Z1IlI7lvJ71i63Xh9M+j27jqutL+4qb9u8x9t6YizYsXpZ5LK7P4uF4wWj/KWfZF/rP6023nel2fd6fzAYd9SWF3d6FY014fqmGhllBogBEIHYQN+okV7x7fEXxnPYdFmnONY/orQAgC8uktlska85b7pu7qgRtfuGqLpv2NuyfkfgDk/qVMnDyJqrjWD10uhXbruUbhUGiX/a4h14/s3SrxoqafuOg0EOAA6cUqXmBN7d0FPsPz6u4gFkeJpjmCNp4a7vcY+s/UXm9aEJ/csrZ4l92/YHJZwlCgBWX2Xe9Pc3mk9V2+UYTAu7j/PBwyPeZtsSp5riPKurxVjlGOVpvmL9wi49tGpD+gf1MXp1JKUH/lhyOr+NK4+OqvZ0ER0ATADJlioamNcqBl/e47tn+/0+gEc/ZyRaakNP3bvIW64DgCSTZgMaADEBga9JSry4Cyf/+mfpl0cz+qnPdRuHt7zvlf9YAADw8OdDNJbR0gu00EDQkuBg3b+X1Ll96NzKPYtl44rLnK1LZuouk3wGSCuAIASSRaO88Q3v2N/9PPMsgJ7Lz5N9O4/4f5Al/2/sD75mewdVZufh8qGP81S/cGa4NRSx2FWG2v6hHnvwufwb//q7wiu+xisL2sTAOwPBn1z5n213LpJxACtsgYcZ+DqAOypsXHRhI8fX3WLTfzf+f2J/crI115rSVwiXfUhfwXUsKq3/Tzf4vwQHgP8Cr1SKQawgpFoAAAAASUVORK5CYII=')
			);

			//Create menu items
			foreach ($filter_list as $filter_item)
				if (!array_key_exists($filter_item['name'] , $filter))
				{
					$listParams = $books->listParams($filter, $filter_item['name'], true, true);
					if (count($listParams) > 1)
					{
						$node = $xml->addChild('entry');
						$node->addChild('title', $filter_item['title']);
						$node->addChild('id', $renderer->escapehtml($index . ':' . $filter_item['name']));
						$node->addChild('content', 'Filter books by ' . $filter_item['name'])->addAttribute('type', 'text');

						$link = $node->addChild('link');
						$link->addAttribute('href', $renderer->url('catalog', array('hash' => $hash, 'index' => base64_encode($index . ':' . $filter_item['name']))));
						$link->addAttribute('type', 'application/atom+xml;type=feed;profile=opds-catalog');
		
						$link = $node->addChild('link');
						$link->addAttribute('href', $filter_item['img']);
						$link->addAttribute('type', 'image/png');			
						$link->addAttribute('rel', 'http://opds-spec.org/thumbnail');
					}
				}
				
			//Show_all menu
			$node = $xml->addChild('entry');
			$node->addChild('title', 'Show books');
			$node->addChild('id', $renderer->escapehtml($index . ':LIST'));
			$node->addChild('content', 'Show books')->addAttribute('type', 'text');
				
			$link = $node->addChild('link');
			$link->addAttribute('href', $renderer->url('catalog', array('hash' => $hash, 'index' => base64_encode($index . ':LIST'))));
			$link->addAttribute('type', 'application/atom+xml;type=feed;profile=opds-catalog');
				
			$link = $node->addChild('link');
			$link->addAttribute('href', 'data:image/png;base64,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');
			$link->addAttribute('type', 'image/png');
			$link->addAttribute('rel', 'http://opds-spec.org/thumbnail');
		}
		
		//Show list of available params
		if ($type == 'PARAMS')
		{
			//Setup filter
			for ($i = 1; $i < count($path) - 1; $i += 2)
				$filter = Application\Model\Util::updateFilter($filter, array('a' . $path[$i] => $path[$i+1]));
			$param_name = $path[count($path) - 1];
			
			//List params
			$params = $books->listParams($filter, $param_name, false, true);
			
			//Create menu items
			foreach ($params as $param)
			{
				$node = $xml->addChild('entry');
				$node->addChild('title', $renderer->escapehtml($param[$param_name]));
				$node->addChild('id', $renderer->escapehtml($index . ':' . $param[$param_name]));
				$node->addChild('content', $param['count'] . ' books available');
			
				$link = $node->addChild('link');
				$link->addAttribute('href', $renderer->url('catalog', array('hash' => $hash, 'index' => base64_encode($index . ':' . $param[$param_name]))));
				$link->addAttribute('type', 'application/atom+xml;type=feed;profile=opds-catalog');
			}
		}
		
		
		//Show list of books
		if ($type == 'LIST')
		{
			//Setup filter
			for ($i = 1; $i < count($path) - 1; $i += 2)
				$filter = Application\Model\Util::updateFilter($filter, array('a' . $path[$i] => $path[$i+1]));
			
			//List books
			$bookList = $books->listBooks($filter);
			$size = count($bookList);
			
			//Setup pages
			$pageSize = 10;
			$page = (!$page ? 1 : $page);

			//Filter results for the page
			$pageMax = ceil($size / $pageSize);
			$page = ($page > $pageMax ? $pageMax : $page);
			$pageBookList = array_slice($bookList->toArray(), (($page - 1) * $pageSize), $pageSize);

			//Add next and previous page link
			if ($page > 1)
			{
				$link = $xml->addChild('link');
				$link->addAttribute('rel', 'previous');
				$link->addAttribute('title', 'Previous Page');
				$link->addAttribute('type', 'application/atom+xml;type=feed;profile=opds-catalog');
				$link->addAttribute('href', $renderer->url('catalog', array('hash' => $hash, 'index' => base64_encode($index), 'page' => $page - 1)));
			}
			if ($page < $pageMax)
			{
				$link = $xml->addChild('link');
				$link->addAttribute('rel', 'next');
				$link->addAttribute('title', 'Next Page');
				$link->addAttribute('type', 'application/atom+xml;type=feed;profile=opds-catalog');
				$link->addAttribute('href', $renderer->url('catalog', array('hash' => $hash, 'index' => base64_encode($index), 'page' => $page + 1)));
			}
				
			
			//Create menu items
			foreach ($pageBookList as $book)
			{
				$node = $xml->addChild('entry');
				$node->addChild('title', $renderer->escapehtml($book['title']));
				$node->addChild('id', $renderer->escapehtml('FILE:' . $book['book_id']));
				$node->addChild('updated', $book['date']);
				$node->addChild('author', $renderer->escapehtml($book['author']));
				$node->addChild('summary', $renderer->escapehtml($book['sinopse']));

				$link = $node->addChild('link');
				$link->addAttribute('href', $renderer->url('files', array('action' => 'image', 'arg1' => 'thumb', 'arg2' => $book['book_id'])));
				$link->addAttribute('type', 'image/jpeg');
				$link->addAttribute('rel', 'http://opds-spec.org/thumbnail');
				
				$link = $node->addChild('link');
				$link->addAttribute('href', $renderer->url('files', array('action' => 'image', 'arg1' => 'reg', 'arg2' => $book['book_id'])));
				$link->addAttribute('type', 'image/jpeg');
				$link->addAttribute('rel', 'http://opds-spec.org/cover');
				
				$link = $node->addChild('link');
				$link->addAttribute('href', $renderer->url('files', array('action' => 'epub', 'arg1' => $hash, 'arg2' => $book['book_id'])));
				$link->addAttribute('type', 'application/epub+zip');
				$link->addAttribute('rel', 'http://opds-spec.org/acquisition');
				$link->addAttribute('title', 'Download this ebook as EPUB');
			}
		}
		
		//Return XML
		header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
		header("Content-Type: application/atom+xml; profile=opds-catalog; kind=navigation");
		header("Content-Length:".strlen($xml->asXML()));
		print ($xml->asXML());
		die();
	}
}