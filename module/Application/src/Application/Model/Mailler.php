<?php
namespace Application\Model;

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Http\Client;

class Mailler
{
    protected $transport;
    protected $sm;

    public function __construct($sm)
    {
        $this->sm = $sm;
        
        $this->transport = new SmtpTransport();
        $options   = new SmtpOptions($sm->get('config')['SmtpOptions']);
        $this->transport->setOptions($options);
    }
    
    function send($to, $subject, $html, $ical = null)
    {
        $message = new Message();
        $message->addTo($to)
            ->addFrom('bookcloud@narciso.ws')
            ->setSubject($subject);
        
        $bodyPart = new \Zend\Mime\Message();
        
        $bodyMessage = new \Zend\Mime\Part($html);
        $bodyMessage->type = 'text/html';
        $parts = array($bodyMessage);
        
        if ($ical)
        {
        	$icalMessage = new \Zend\Mime\Part($ical);
        	$icalMessage->type = 'text/calendar';
        	$parts = array_merge($parts, array($icalMessage));
        }
        
        $bodyPart->setParts($parts);
        
        $message->setBody($bodyPart);
        $message->setEncoding('UTF-8');
        
        $this->transport->send($message);
    }

    function newsletterEmail($userList, $bookList, $date)
    {
   	
    	$message  = "<html><body style=\"padding: 20px 20px\">\n";
		$message .= "	<div style=\"border-radius: 4px; border-color: #DDDDDD; border-style: solid; border-width: 1;\">\n";
		$message .= "		<div style=\"background-color: #F5F5F5; border-color: #DDDDDD; border-style: solid; border-width: 0px 0px 1px 0px; border-top-left-radius: 3px; border-top-right-radius: 3px; padding: 10px 15px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px;\">\n";
		$message .= "			New books on <b>BookCloud</b>\n";
		$message .= "		</div>\n";
		$message .= "		<div style=\"margin: 20px 20px; background-color: #EEEEEE;\"  align=\"center\">\n";
		
		foreach ($bookList as $book):
		$message .= "			<div style=\"background-color: #FFFFFF; padding: 20px 0px; margin: 10px 0px\">\n";
		$message .=	"							<img src=\"http://bookcloud.narciso.ws/files/image/reg/{$book[book_id]}\" style=\"max-height: 300px; border-radius: 4px; border-width: 1px; border-color: #DDDDDD; padding: 4px; border-style: solid\">\n";
		$message .=	"							<h3>{$book[title]}" . ($book[serie] ? " - {$book[serie]}" : "") . "</h3>\n";
		$message .=	"							<h4>{$book[author]}</h4>\n";
		$message .=	"							<p style=\"text-align: justify; max-width: 640px\">{$book[sinopse]}</p>\n";
		$message .= "			</div>\n";
		endforeach;
		
		$message .= "		</div>\n";
		$message .= "	</div>\n";
		$message .= "</body></html>";

		foreach ($userList as $user):
    		$this->send($user[email], "BookCloud Update - {$date}", $message);
		endforeach;
    }
}