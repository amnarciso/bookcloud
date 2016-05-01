<?php
namespace Application\Model;

use Application\Model\User;

use Zend\Db\Sql\Sql;
use Zend\Crypt\Password\Bcrypt;


/**
 * Handler of user_table
 * 
 * @author Andre
 */
class Users
{
    protected $sm;

    /**
     * Class constructor setup service manager
     * 
     * @param unknown $sm
     */
    public function __construct($sm)
    {
        $this->sm = $sm;
    }
	
    /**
     * Execute login:
     *     -return true if successful
     * 
     * @param String $email
     * @param String $password
     * @return boolean
     */
    public function login($email, $password)
    {
    	$bcrypt = new Bcrypt();
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        
    	$selectString = 'select * from users_table where email="' . $email . '"';
        $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray()[0];   
        
//        throw new \Exception($results['password']);
        $logged = $bcrypt->verify($password, $results['password']);
        
        if ($logged)
 //       	throw new \Exception(serialize($results));
            return new User($results);
        else
            return null;
    }

    public function setPassword($user_id, $newPassword)
    {
        //Initialize variables
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($adapter);
        $bcrypt = new Bcrypt();
            
        //create query
        $update = $sql->update('users_table')->set(array(
                'password'   => $bcrypt->create($newPassword)
            ))->where(array(
                'user_id' => $user_id));
            
        //execute query
        $sqlString = $sql->getSqlStringForSqlObject($update);
        $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Execute login:
     *     -return true if successful
     * 
     * @param String $user_id
     * @param String $password
     * @return boolean
     */
/*
    public function id_login($user_id, $password)
    {
    	$bcrypt = new Bcrypt();
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        
        $selectString = 'select * from users_table where user_id="' . $user_id . '"';
        $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray()[0];   
        
        $logged = $bcrypt->verify($password, $results['password']);
        
        if ($logged)
            return new User($results);
        else
            return null;
    }
*/

/*    
    public function encript()
    {
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
    	 
    	$selectString = 'select * from users_table';
    	$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    	
    	foreach ($results as $user)
    	{
    		$sql = new Sql($adapter);
    		$bcrypt = new Bcrypt();
    		
    		$update = $sql->update('users_table')->set(array(
    				'password'   => $bcrypt->create($user->password)
    		))->where(array('user_id' => $user->user_id));
    		
    		$selectString = $sql->getSqlStringForSqlObject($update);
    		$adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    	}
    }
*/ 

    public function listUsers($filter)
    {
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
    	 
    	$selectString = "select * from users_table" . ($filter ? " where {$filter}" : "");
    	$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    	return $results;
    }

    public function userDetails($user_id)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($adapter);

        $select = $sql->select('users_table')->where(array('user_id' => $user_id));
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray()[0];   

        return new User($results);
    }

    public function update($user)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($adapter, 'users_table');

        $fields = $user->toArray();
        $filter = array_shift($fieldsvalue);
        $update = $sql->update()->where($filter)->set($fieldsvalue));
        $sqlString = $sql->getSqlStringForSqlObject($update);
        $results = $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE);   

        return $results;        
    }
}