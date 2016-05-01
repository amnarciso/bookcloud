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
	echo "********************\n";
	echo "sendmailAction START\n";
	echo date('Y-m-d H:i:s e') . "\n";
	echo "********************\n";

	public function sendmailAction() {
		//Initialize variables
        $sm = $this->getServiceLocator();
        $books = new Books($sm);
        $users = new Users($sm);
		$mailler = new Mailler($sm);

		//Get new books from last week
		$lastDate = date("o-m-d", date_sub(new DateTime(),date_interval_create_from_date_string("7 days"))->getTimestamp());
		$lastDate = '2016-02-01';
		$bookList = $books->listBooks(array("sql" => "date >= '$lastDate'"))->toArray();	
		
		//Print list of new books
		echo "\nLIST OF BOOKS:\n"
		foreach ($books as $book) {
			echo $book['title'];
		}

		//Get list of subscribed users
		$userList = $users->listUsers("subscribe = 1")->toArray();

		//Print list of subscribed users
		echo "\nLIST OF EMAILS:\n"
		foreach ($userList as $user) {
			echo $user['email'];
		}

		//Send mails if there is at least one new book
		if (count($bookList) > 0){
			$mailler->newsletterEmail($userList, $bookList, date("d/m/o"));
		}
		die();
	}
}