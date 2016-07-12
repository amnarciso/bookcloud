<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Application;


/**
 * Handler of user_table
 * 
 * @author Andre
 */
class Newpass
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

    public function generate($user_id)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($adapter);
    
        do {
            //Generate new hash
            $hash = Application\Model\Util::unique_id(16);

            //Check if hash is taken
            $select = $sql->select('newpass_table')->where(array('hash' => $hash));
            $selectString = $sql->getSqlStringForSqlObject($select);
            $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray()[0];   
        } while ($results);


        //Create new hash on table
        $insert = $sql->insert('newpass_table')->values(array(
                'user_id'   => $user_id,
                'hash'      => $hash,
                'created_at'=> date("Y-m-d H:i:s"),
                'used'      => 0,
        ));   
        $selectString = $sql->getSqlStringForSqlObject($insert);
        $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
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
