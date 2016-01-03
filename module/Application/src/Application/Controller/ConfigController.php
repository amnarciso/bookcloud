<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

use Application\Model\Tokens;

/**
 * ConfigController
 *
 * @author
 *
 * @version
 *
 */
class ConfigController extends AbstractActionController {
    protected $logged;
    protected $user;
    
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
	
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {       
        //Redirecting if not logged
        if (!$this->logged)
            return $this->redirect()->toRoute('application/default', array(
                'controller' => 'index',
                'action'     => 'index'
                ));
		
        //Get last hash
        $sm = $this->getServiceLocator();
        $tokens = new Tokens($sm); 
        $lastToken = $tokens->lastToken($this->user->user_id);

        //Load view
        return new ViewModel(array(
                'nav'      => 3,
                'token'    =>  $lastToken
        ));
	}
    public function refreshTokenAction() {
        $sm = $this->getServiceLocator();
        $tokens = new Tokens($sm);
 
        $tokens->refresh($this->user->user_id);


        return $this->redirect()->toRoute('application/default', array(
            'controller' => 'Config',
            'action'     => 'index'
            ));       
    }
}