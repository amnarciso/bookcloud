<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

use Application\Model\Mailler;
use Application\Model\Books;
use \DateTime;
use Application\Model\Users;


/**
 * ConsoleController
 *
 * @author
 *
 * @version
 *
 */
class ConsoleController extends AbstractActionController {

	public function sendmailAction() {
		//Initialize variables
        $sm = $this->getServiceLocator();
        $books = new Books($sm);
        $users = new Users($sm);
		$mailler = new Mailler($sm);

		$lastDate = date("o-m-d", date_sub(new DateTime(),date_interval_create_from_date_string("7 days"))->getTimestamp());
		$bookList = $books->listBooks(array("sql" => "date >= '$lastDate'"))->toArray();	
		
		$userList = $users->listUsers("subscribe = 1")->toArray();

//		if (count($bookList) > 0){
			$mailler->newsletterEmail($userList, $bookList, date("d/m/o"));
//		}
				
		die();
	}
}