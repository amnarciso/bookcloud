<?php
namespace Application\Model;

/**
 * User class, carries all user info except by password
 * 
 * @author Andre
 */
class User
{
	public $user_id;
	public $email;
	public $nickname;
	public $status;
	public $weeklynews;
	
	/**
	 * Class constructor receives a row of user_table
	 * 
	 * @param unknown $data
	 */
	public function __construct($data)
	{
	    $this->exchangeArray($data);
	}
	
	/**
	 * Redefine all fields from a row of user_table
	 * 
	 * @param unknown $data
	 */
	public function exchangeArray($data)
	{
		$this->user_id  = (isset($data['user_id'])) ? $data['user_id'] : null;
		$this->email  = (isset($data['email'])) ? $data['email'] : null;
		$this->nickname  = (isset($data['nickname'])) ? $data['nickname'] : null;
		$this->status  = (isset($data['status'])) ? $data['status'] : null;
		$this->weeklynews  = (isset($data['weeklynews'])) ? $data['weeklynews'] : null;
	}

	public function toArray()
	{
		return array(
			'user_id' => $this->$user_id,
			'email' => $this->$email,
			'nickname' => $this->$nickname,
			'status' => $this->$status,
			'weeklynews' => $this->$weeklynews,
			);
	}
}