<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;

use Application\Model\Users;

/**
 * User controller defines all actions for handling users
 * 
 * @author Andre
 */
class UserController extends AbstractActionController
{
    /**
     * Login page
     *     -Expect to receive email and password as Post
     *     -Redirect to home
     * 
     * @return Ambigous <\Zend\Http\Response, \Zend\Stdlib\ResponseInterface>
     */
    public function loginAction()
    {
        $sm = $this->getServiceLocator();
        $users = new Users($sm);
        
        $request = $this->getRequest($sm);
        $email = $request->getPost('email');
        $password = $request->getPost('password');
        
        $found = $users->login($email, $password);
        
        $user_session = new Container('user');
        $user_session->logged = (($found) ? true : false);
        $user_session->user = $found;
        
        return $this->redirect()->toRoute('application/default', array(
            'controller' => 'index',
            'action'     => 'index'
            ));
    }
    
    public function logoutAction()
    {
        $user_session = new Container('user');
        $user_session->logged = false;
        
        return $this->redirect()->toRoute('application/default', array(
        		'controller' => 'index',
        		'action'     => 'index'
        ));
    }
    
    public function encriptAction()
    {
    	$sm = $this->getServiceLocator();
    	$users = new Users($sm);
    	$users->encript();
    	
        return $this->redirect()->toRoute('application/default', array(
        		'controller' => 'index',
        		'action'     => 'index'
        ));
    }
}
