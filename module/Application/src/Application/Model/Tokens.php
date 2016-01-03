<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Application;


/**
 * Handler of user_table
 * 
 * @author Andre
 */
class Tokens
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

    public function refresh($user_id)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($adapter);
    
        do {
            //Generate new hash
            $hash = Application\Model\Util::unique_id();

            //Check if hash is taken
            $select = $sql->select('tokens_table')->where(array('hash' => $hash));
            $selectString = $sql->getSqlStringForSqlObject($select);
            $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray()[0];   
        } while ($results);

        //Disable old hashs
        $update = $sql->update('tokens_table')->set(array(
                'enabled'   => 0
            ))->where(array(
                'user_id'   => $user_id)
            );
        $selectString = $sql->getSqlStringForSqlObject($update);
        $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);


        //Create new hash on table
        $insert = $sql->insert('tokens_table')->values(array(
                'user_id'   => $user_id,
                'hash'      => $hash,
                'enabled'   => true,
        ));   
        $selectString = $sql->getSqlStringForSqlObject($insert);
        $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    }

    public function lastToken($user_id)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($adapter);

        //search hash
        $select = $sql->select('tokens_table')->where(array('user_id' => $user_id))->order('id DESC');
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray()[0];   

        return $results;
    }

    public function checkHash($hash)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($adapter);

        //search hash
        $select = $sql->select('tokens_table')->where(array('hash' => $hash))->order('id DESC');
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray()[0];   

        return $results;
    }    
}

//        throw new \Exception($user_id);
