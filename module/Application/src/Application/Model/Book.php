<?php
namespace Application\Model;

class Book
{
	public $book_id;
	public $user_id;
	public $title;
	public $author;
	public $serie;
	public $volume;
	public $year;
	public $date;
	public $language;
	public $sinopse;	
	public $status;
	
	public function exchangeArray($data)
	{
		$this->book_id  = (isset($data['book_id'])) ? $data['book_id'] : null;
		$this->user_id  = (isset($data['user_id'])) ? $data['user_id'] : null;
		$this->title  = (isset($data['title'])) ? $data['title'] : null;
		$this->author  = (isset($data['author'])) ? $data['author'] : null;
		$this->serie  = (isset($data['serie'])) ? $data['serie'] : null;
		$this->volume  = (isset($data['volume'])) ? $data['volume'] : null;
		$this->year  = (isset($data['year'])) ? $data['year'] : null;
		$this->date  = (isset($data['date'])) ? $data['date'] : null;
		$this->language  = (isset($data['language'])) ? $data['language'] : null;
		$this->sinopse  = (isset($data['sinopse'])) ? $data['sinopse'] : null;
		$this->status  = (isset($data['status'])) ? $data['status'] : null;
	}
	
	public function __construct($data = null)
	{
		if ($data)
			$this->exchangeArray($data);
	}
}